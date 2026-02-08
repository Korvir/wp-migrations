<?php

namespace WPMigrations\Sql;

use RuntimeException;
use WPMigrations\Schema\Blueprint;
use WPMigrations\Schema\Column;
use WPMigrations\Schema\Index;
use WPMigrations\Schema\TableContext;


final class SqlCompiler {
	
	public function compile( Blueprint $blueprint ): array {
		if ( $blueprint->getContext()->getMode() === Blueprint::MODE_CREATE ) {
			return $this->compileCreate($blueprint);
		}
		return $this->compileAlter($blueprint);
	}
	
	
	protected function compileCreate( Blueprint $blueprint ): array {
		$context = $blueprint->getContext();
		
		// Restricted operations with "CREATE"
		if (
			$blueprint->getDroppedColumns() ||
			$blueprint->getRenamedColumns() ||
			$blueprint->getChangedColumns()
		) {
			throw new RuntimeException('Error! CREATE TABLE does not support drop/rename/change operations.');
		}
		
		// 1) Table options (charset / collation)
		$table = $context->getPrefixedName();
		
		
		// 2) Columns options
		$columns = $blueprint->getColumns();
		if ( !$columns ) {
			throw new RuntimeException('Error! CREATE TABLE requires at least one column.');
		}
		$definitions = [];
		foreach ( $columns as $column ) {
			$definitions[] = $this->compileCreateColumn($column);
		}
		
		// 3) Indexes
		$indexDefinitions = $this->compileCreateIndexes($blueprint);
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
		$table = $context->getPrefixedName();
		
		// 1) RENAME COLUMN
		foreach ( $blueprint->getRenamedColumns() as $rename ) {
			$sql[] = sprintf(
				'ALTER TABLE %s RENAME COLUMN %s TO %s;',
				$table,
				$rename['from'],
				$rename['to']
			);
		}
		
		// 2) DROP COLUMN (batch)
		$dropped = $blueprint->getDroppedColumns();
		if ( !empty($dropped) ) {
			$clauses = [];
			foreach ( $dropped as $column ) {
				$clauses[] = 'DROP COLUMN ' . $column;
			}
			
			$sql[] = sprintf(
				"ALTER TABLE %s\n%s;",
				$table,
				implode(",\n", $clauses)
			);
		}
		
		// 3) ADD COLUMN
		foreach ( $blueprint->getColumns() as $column ) {
			
			if ( $column->isChange() )
				continue;
			
			$sql[] = sprintf(
				"ALTER TABLE %s\nADD COLUMN %s;",
				$table,
				$this->compileAlterAddColumn($column)
			);
		}
		
		// CHANGE COLUMN
		foreach ( $blueprint->getColumns() as $column ) {
			if ( !$column->isChange() ) {
				continue;
			}
			
			$sql[] = sprintf(
				"ALTER TABLE %s\nMODIFY %s;",
				$table,
				$this->compileAlterChangeColumn($column)
			);
		}
		
		
		// ===== INDEXES =====
		
		// DROP PRIMARY KEY (must be first)
		if ( $blueprint->shouldDropPrimary() ) {
			$sql[] = sprintf(
				"ALTER TABLE %s\nDROP PRIMARY KEY;",
				$table
			);
		}
		
		// DROP INDEX / UNIQUE
		foreach ( $blueprint->getDroppedIndexes() as $indexName ) {
			$sql[] = sprintf(
				"ALTER TABLE %s\nDROP INDEX %s;",
				$table,
				$indexName
			);
		}
		
		// ADD PRIMARY KEY (must be after drop)
		if ( $primary = $blueprint->getPrimary() ) {
			$sql[] = sprintf(
				"ALTER TABLE %s\nADD %s;",
				$table,
				$this->compilePrimaryKey($primary)
			);
		}
		
		// ADD UNIQUE
		foreach ( $blueprint->getUniqueIndexes() as $index ) {
			$sql[] = sprintf(
				"ALTER TABLE %s\nADD %s;",
				$table,
				$this->compileUniqueKey($index)
			);
		}
		
		// ADD INDEX
		foreach ( $blueprint->getIndexes() as $index ) {
			$sql[] = sprintf(
				"ALTER TABLE %s\nADD %s;",
				$table,
				$this->compileIndex($index)
			);
		}
		
		// UPDATE TABLE CHARSET / COLLATION
		if (
			$context->getCharset() !== null ||
			$context->getCollation() !== null
		) {
			$clauses = [];
			if ( $context->getCharset() ) {
				$clauses[] = 'DEFAULT CHARSET=' . $context->getCharset();
			}
			if ( $context->getCollation() ) {
				$clauses[] = 'COLLATE=' . $context->getCollation();
			}
			
			$sql[] = sprintf(
				"ALTER TABLE %s\n%s;",
				$table,
				implode("\n", $clauses)
			);
		}
		
		return $sql;
	}
	
	
	protected function compileCreateColumn( Column $column ): string {
		$sql = [];
		
		// name
		$sql[] = $column->getName();
		
		// type
		$sql[] = $this->compileColumnType($column);
		
		// unsigned
		if ( $column->isUnsigned() ) {
			$sql[] = 'UNSIGNED';
		}
		
		// nullability
		$sql[] = $column->isNullable() ? 'NULL' : 'NOT NULL';
		
		// default
		if ( $column->getDefault() !== null ) {
			$sql[] = 'DEFAULT ' . $this->compileDefault($column->getDefault());
		}
		
		// auto increment
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
			
			// numeric
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
			
			// string / binary
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
			
			// date / time
			case 'date':
				return 'DATE';
			
			case 'time':
				return 'TIME';
			
			case 'dateTime':
				return 'DATETIME';
			
			case 'timestamp':
				return 'TIMESTAMP';
			
			default:
				throw new RuntimeException("Unsupported column type [{$type}]");
		}
	}
	
	
	protected function compileDefault( $value ): string {
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
		
		global $wpdb;
		return ' ' . $wpdb->get_charset_collate();
	}
	
	
	protected function compileCreateIndexes( Blueprint $blueprint ): array {
		$sql = [];
		
		// PRIMARY KEY
		if ( $primary = $blueprint->getPrimary() ) {
			$sql[] = $this->compilePrimaryKey($primary);
		}
		
		// UNIQUE
		foreach ( $blueprint->getUniqueIndexes() as $index ) {
			$sql[] = $this->compileUniqueKey($index);
		}
		
		// INDEX
		foreach ( $blueprint->getIndexes() as $index ) {
			$sql[] = $this->compileIndex($index);
		}
		
		return $sql;
	}
	
	
	protected function compileAlterAddColumn( Column $column ): string {
		$parts = [];
		
		// column name
		$parts[] = $column->getName();
		
		// type
		$parts[] = $this->compileColumnType($column);
		
		// unsigned
		if ( $column->isUnsigned() ) {
			$parts[] = 'UNSIGNED';
		}
		
		// nullability
		$parts[] = $column->isNullable() ? 'NULL' : 'NOT NULL';
		
		// default
		if ( $column->getDefault() !== null ) {
			$parts[] = 'DEFAULT ' . $this->compileDefault($column->getDefault());
		}
		
		// auto increment
		if ( $column->isAutoIncrement() ) {
			$parts[] = 'AUTO_INCREMENT';
		}
		
		// position
		if ( $column->isFirst() ) {
			$parts[] = 'FIRST';
		}
		elseif ( $column->getAfter() ) {
			$parts[] = 'AFTER ' . $column->getAfter();
		}
		
		$parts = array_merge($parts, $this->compileColumnExtras($column));
		
		return implode(' ', $parts);
	}
	
	
	protected function compileAlterChangeColumn( Column $column ): string {
		$sql = [];
		
		// name
		$sql[] = $column->getName();
		
		// type
		$sql[] = $this->compileColumnType($column);
		
		// unsigned
		if ( $column->isUnsigned() ) {
			$sql[] = 'UNSIGNED';
		}
		
		// nullability
		$sql[] = $column->isNullable() ? 'NULL' : 'NOT NULL';
		
		// default
		if ( $column->getDefault() !== null ) {
			$sql[] = 'DEFAULT ' . $this->compileDefault($column->getDefault());
		}
		
		// auto increment
		if ( $column->isAutoIncrement() ) {
			$sql[] = 'AUTO_INCREMENT';
		}
		
		// position
		if ( $column->isFirst() ) {
			$sql[] = 'FIRST';
		}
		elseif ( $column->getAfter() ) {
			$sql[] = 'AFTER ' . $column->getAfter();
		}
		
		$sql = array_merge($sql, $this->compileColumnExtras($column));
		
		return implode(' ', $sql);
	}
	
	
	protected function compilePrimaryKey( Index $index ): string {
		return sprintf(
			'PRIMARY KEY (%s)',
			implode(', ', $index->getColumns())
		);
	}
	
	protected function compileUniqueKey( Index $index ): string {
		if ( $index->getName() ) {
			return sprintf(
				'CONSTRAINT %s UNIQUE (%s)',
				$index->getName(),
				implode(', ', $index->getColumns())
			);
		}
		
		return sprintf(
			'UNIQUE (%s)',
			implode(', ', $index->getColumns())
		);
	}
	
	protected function compileIndex( Index $index ): string {
		if ( $index->getName() ) {
			return sprintf(
				'INDEX %s (%s)',
				$index->getName(),
				implode(', ', $index->getColumns())
			);
		}
		
		return sprintf(
			'INDEX (%s)',
			implode(', ', $index->getColumns())
		);
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
	
}
