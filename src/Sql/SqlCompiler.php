<?php

namespace WPMigrations\Sql;

use RuntimeException;
use WPMigrations\Database\ConnectionInterface;
use WPMigrations\Database\Grammar\GrammarInterface;
use WPMigrations\Database\Grammar\MySqlGrammar;
use WPMigrations\Schema\Blueprint;
use WPMigrations\Schema\Column;
use WPMigrations\Schema\Expression;
use WPMigrations\Schema\ForeignKey;
use WPMigrations\Schema\Index;
use WPMigrations\Schema\TableContext;

final class SqlCompiler {
	private GrammarInterface $grammar;
	private ?ConnectionInterface $connection;

	public function __construct( ?GrammarInterface $grammar = null, ?ConnectionInterface $connection = null ) {
		$this->grammar = $grammar ?? new MySqlGrammar();
		$this->connection = $connection;
	}

	public function compile( Blueprint $blueprint ): array {
		if ( $blueprint->getContext()->getMode() === Blueprint::MODE_CREATE ) {
			return $this->compileCreate($blueprint);
		}

		return $this->compileAlter($blueprint);
	}

	protected function compileCreate( Blueprint $blueprint ): array {
		$context = $blueprint->getContext();

		if ( $blueprint->getDroppedColumns() || $blueprint->getRenamedColumns() ) {
			throw new RuntimeException('Error! CREATE TABLE does not support drop/rename/change operations.');
		}

		foreach ( $blueprint->getColumns() as $column ) {
			if ( $column->isChange() ) {
				throw new RuntimeException('Error! CREATE TABLE does not support change operations.');
			}
		}

		$table = $this->grammar->wrapTable($context->getPrefixedName());
		$columns = $blueprint->getColumns();
		if ( !$columns ) {
			throw new RuntimeException('Error! CREATE TABLE requires at least one column.');
		}

		$definitions = [];
		foreach ( $columns as $column ) {
			$definitions[] = $this->compileCreateColumn($column);
		}

		$indexDefinitions = $this->compileCreateIndexes($blueprint, $context);
		$allDefinitions = array_merge($definitions, $indexDefinitions);

		$sql = sprintf(
			"CREATE TABLE %s (\n  %s\n)%s;",
			$table,
			implode(",\n  ", $allDefinitions),
			$this->compileTableOptions($context)
		);

		return [ $sql ];
	}

	protected function compileAlter( Blueprint $blueprint ): array {
		$sql = [];
		$context = $blueprint->getContext();
		$table = $this->grammar->wrapTable($context->getPrefixedName());

		foreach ( $blueprint->getRenamedColumns() as $rename ) {
			$sql[] = sprintf(
				'ALTER TABLE %s RENAME COLUMN %s TO %s;',
				$table,
				$this->grammar->wrapIdentifier($rename['from']),
				$this->grammar->wrapIdentifier($rename['to'])
			);
		}

		$dropped = $blueprint->getDroppedColumns();
		if ( !empty($dropped) ) {
			$clauses = [];
			foreach ( $dropped as $column ) {
				$clauses[] = 'DROP COLUMN ' . $this->grammar->wrapIdentifier($column);
			}

			$sql[] = sprintf("ALTER TABLE %s\n%s;", $table, implode(",\n", $clauses));
		}

		foreach ( $blueprint->getColumns() as $column ) {
			if ( $column->isChange() ) {
				continue;
			}

			$sql[] = sprintf("ALTER TABLE %s\nADD COLUMN %s;", $table, $this->compileAlterAddColumn($column));
		}

		foreach ( $blueprint->getColumns() as $column ) {
			if ( !$column->isChange() ) {
				continue;
			}

			$sql[] = sprintf("ALTER TABLE %s\nMODIFY %s;", $table, $this->compileAlterChangeColumn($column));
		}

		if ( $blueprint->shouldDropPrimary() ) {
			$sql[] = sprintf("ALTER TABLE %s\nDROP PRIMARY KEY;", $table);
		}

		foreach ( $blueprint->getDroppedIndexes() as $indexName ) {
			$sql[] = sprintf("ALTER TABLE %s\nDROP INDEX %s;", $table, $this->grammar->wrapIdentifier($indexName));
		}

		foreach ( $blueprint->getDroppedForeignKeys() as $name ) {
			$sql[] = sprintf("ALTER TABLE %s\nDROP FOREIGN KEY %s;", $table, $this->grammar->wrapIdentifier($name));
		}

		if ( $primary = $blueprint->getPrimary() ) {
			$sql[] = sprintf("ALTER TABLE %s\nADD %s;", $table, $this->compilePrimaryKey($primary));
		}

		foreach ( $blueprint->getUniqueIndexes() as $index ) {
			$sql[] = sprintf("ALTER TABLE %s\nADD %s;", $table, $this->compileUniqueKey($index));
		}

		foreach ( $blueprint->getIndexes() as $index ) {
			$sql[] = sprintf("ALTER TABLE %s\nADD %s;", $table, $this->compileIndex($index));
		}

		if ( $context->getCharset() !== null || $context->getCollation() !== null ) {
			$clauses = [];
			if ( $context->getCharset() ) {
				$clauses[] = 'DEFAULT CHARSET=' . $context->getCharset();
			}
			if ( $context->getCollation() ) {
				$clauses[] = 'COLLATE=' . $context->getCollation();
			}

			$sql[] = sprintf("ALTER TABLE %s\n%s;", $table, implode("\n", $clauses));
		}

		foreach ( $blueprint->getForeignKeys() as $fk ) {
			$sql[] = sprintf("ALTER TABLE %s\nADD %s;", $table, $this->compileForeignKey($fk, $context));
		}

		return $sql;
	}

	protected function compileCreateColumn( Column $column ): string {
		$sql = [];
		$sql[] = $this->grammar->wrapIdentifier($column->getName());
		$sql[] = $this->compileColumnType($column);

		if ( $column->isUnsigned() ) {
			$sql[] = 'UNSIGNED';
		}

		$sql[] = $column->isNullable() ? 'NULL' : 'NOT NULL';

		if ( $column->getDefault() !== null ) {
			$sql[] = 'DEFAULT ' . $this->compileDefault($column->getDefault());
		}

		if ( $column->isAutoIncrement() ) {
			$sql[] = 'AUTO_INCREMENT';
		}

		$sql = array_merge($sql, $this->compileColumnExtras($column));
		return implode(' ', $sql);
	}

	protected function compileColumnType( Column $column ): string {
		$type = $column->getType();
		$args = $column->getArgs();

		switch ( $type ) {
			case 'tinyInteger':
				return 'TINYINT';
			case 'smallInteger':
				return 'SMALLINT';
			case 'mediumInteger':
				return 'MEDIUMINT';
			case 'integer':
				return 'INT';
			case 'bigInteger':
				return 'BIGINT';
			case 'decimal':
				$precision = $args[0] ?? 8;
				$scale = $args[1] ?? 2;
				return "DECIMAL({$precision},{$scale})";
			case 'float':
				return 'FLOAT';
			case 'double':
				return 'DOUBLE';
			case 'boolean':
				return 'TINYINT(1)';
			case 'char':
				$length = $args[0] ?? 1;
				return "CHAR({$length})";
			case 'string':
				$length = $args[0] ?? 255;
				return "VARCHAR({$length})";
			case 'text':
				return 'TEXT';
			case 'mediumText':
				return 'MEDIUMTEXT';
			case 'longText':
				return 'LONGTEXT';
			case 'binary':
				return 'BLOB';
			case 'enum':
				$values = $args[0] ?? [];
				if ( empty($values) ) {
					throw new RuntimeException('ENUM column requires at least one value.');
				}
				$escaped = array_map(static fn ( $v ) => "'" . addslashes((string)$v) . "'", $values);
				return 'ENUM(' . implode(', ', $escaped) . ')';
			case 'json':
				return 'JSON';
			case 'macAddress':
				return 'VARCHAR(17)';
			case 'ipAddress':
				return 'VARCHAR(45)';
			case 'uuid':
				return 'CHAR(36)';
			case 'ulid':
				return 'CHAR(26)';
			case 'date':
				return 'DATE';
			case 'time':
				return 'TIME';
			case 'dateTime':
				return 'DATETIME';
			case 'year':
				return 'YEAR';
			case 'timestamp':
				return 'TIMESTAMP';
			default:
				throw new RuntimeException("Unsupported column type [{$type}]");
		}
	}

	protected function compileDefault( $value ): string {
		if ( $value instanceof Expression ) {
			return (string)$value;
		}

		if ( is_string($value) ) {
			return "'" . addslashes($value) . "'";
		}

		if ( is_bool($value) ) {
			return $value ? '1' : '0';
		}

		if ( $value === null ) {
			return 'NULL';
		}

		return (string)$value;
	}

	protected function compileTableOptions( TableContext $context ): string {
		if ( $context->getCharset() || $context->getCollation() ) {
			$parts = [];
			if ( $context->getCharset() ) {
				$parts[] = 'DEFAULT CHARSET=' . $context->getCharset();
			}
			if ( $context->getCollation() ) {
				$parts[] = 'COLLATE=' . $context->getCollation();
			}
			return ' ' . implode(' ', $parts);
		}

		if ( $this->connection ) {
			return ' ' . $this->connection->charsetCollation();
		}

		global $wpdb;
		return ' ' . $wpdb->get_charset_collate();
	}

	protected function compileCreateIndexes( Blueprint $blueprint, TableContext $context ): array {
		$sql = [];

		if ( $primary = $blueprint->getPrimary() ) {
			$sql[] = $this->compilePrimaryKey($primary);
		}

		foreach ( $blueprint->getUniqueIndexes() as $index ) {
			$sql[] = $this->compileUniqueKey($index);
		}

		foreach ( $blueprint->getIndexes() as $index ) {
			$sql[] = $this->compileIndex($index);
		}

		foreach ( $blueprint->getForeignKeys() as $fk ) {
			$sql[] = $this->compileForeignKey($fk, $context);
		}

		return $sql;
	}

	protected function compileAlterAddColumn( Column $column ): string {
		$parts = [];
		$parts[] = $this->grammar->wrapIdentifier($column->getName());
		$parts[] = $this->compileColumnType($column);

		if ( $column->isUnsigned() ) {
			$parts[] = 'UNSIGNED';
		}

		$parts[] = $column->isNullable() ? 'NULL' : 'NOT NULL';

		if ( $column->getDefault() !== null ) {
			$parts[] = 'DEFAULT ' . $this->compileDefault($column->getDefault());
		}

		if ( $column->isAutoIncrement() ) {
			$parts[] = 'AUTO_INCREMENT';
		}

		if ( $column->isFirst() ) {
			$parts[] = 'FIRST';
		} elseif ( $column->getAfter() ) {
			$parts[] = 'AFTER ' . $this->grammar->wrapIdentifier($column->getAfter());
		}

		$parts = array_merge($parts, $this->compileColumnExtras($column));
		return implode(' ', $parts);
	}

	protected function compileAlterChangeColumn( Column $column ): string {
		$sql = [];
		$sql[] = $this->grammar->wrapIdentifier($column->getName());
		$sql[] = $this->compileColumnType($column);

		if ( $column->isUnsigned() ) {
			$sql[] = 'UNSIGNED';
		}

		$sql[] = $column->isNullable() ? 'NULL' : 'NOT NULL';

		if ( $column->getDefault() !== null ) {
			$sql[] = 'DEFAULT ' . $this->compileDefault($column->getDefault());
		}

		if ( $column->isAutoIncrement() ) {
			$sql[] = 'AUTO_INCREMENT';
		}

		if ( $column->isFirst() ) {
			$sql[] = 'FIRST';
		} elseif ( $column->getAfter() ) {
			$sql[] = 'AFTER ' . $this->grammar->wrapIdentifier($column->getAfter());
		}

		$sql = array_merge($sql, $this->compileColumnExtras($column));
		return implode(' ', $sql);
	}

	protected function compilePrimaryKey( Index $index ): string {
		return sprintf('PRIMARY KEY (%s)', $this->grammar->wrapIdentifierList($index->getColumns()));
	}

	protected function compileUniqueKey( Index $index ): string {
		$columns = $this->grammar->wrapIdentifierList($index->getColumns());
		if ( $index->getName() ) {
			return sprintf('CONSTRAINT %s UNIQUE (%s)', $this->grammar->wrapIdentifier($index->getName()), $columns);
		}

		return sprintf('UNIQUE (%s)', $columns);
	}

	protected function compileIndex( Index $index ): string {
		$columns = $this->grammar->wrapIdentifierList($index->getColumns());
		if ( $index->getName() ) {
			return sprintf('INDEX %s (%s)', $this->grammar->wrapIdentifier($index->getName()), $columns);
		}

		return sprintf('INDEX (%s)', $columns);
	}

	protected function compileColumnExtras( Column $column ): array {
		$sql = [];

		if ( $column->getCharset() ) {
			$sql[] = 'CHARACTER SET ' . $column->getCharset();
		}

		if ( $column->getCollation() ) {
			$sql[] = 'COLLATE ' . $column->getCollation();
		}

		if ( $column->getComment() ) {
			$sql[] = "COMMENT '" . addslashes($column->getComment()) . "'";
		}

		return $sql;
	}

	protected function compileForeignKey( ForeignKey $fk, TableContext $context ): string {
		$name = $fk->getName() ?? $context->getName() . '_' . $fk->getColumn() . '_foreign';

		$prefix = $this->connection ? $this->connection->tablePrefix() : '';
		if ( !$this->connection ) {
			global $wpdb;
			if ( isset($wpdb) && isset($wpdb->prefix) ) {
				$prefix = (string)$wpdb->prefix;
			}
		}

		$referencedTable = $this->grammar->wrapTable($prefix . $fk->getOn());
		$sql = sprintf(
			'CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s(%s)',
			$this->grammar->wrapIdentifier($name),
			$this->grammar->wrapIdentifier($fk->getColumn()),
			$referencedTable,
			$this->grammar->wrapIdentifier($fk->getReferences())
		);

		if ( $fk->getOnDelete() ) {
			$sql .= ' ON DELETE ' . strtoupper($fk->getOnDelete());
		}

		if ( $fk->getOnUpdate() ) {
			$sql .= ' ON UPDATE ' . strtoupper($fk->getOnUpdate());
		}

		return $sql;
	}
}
