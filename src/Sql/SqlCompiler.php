<?php

namespace WPMigrations\Sql;

use WPMigrations\Schema\Blueprint;
use WPMigrations\Schema\Column;
use WPMigrations\Schema\TableContext;

final class SqlCompiler {
	
	public function compile( Blueprint $blueprint ): array {
		if ( $blueprint->getContext()->getMode() === Blueprint::MODE_CREATE ) {
			return $this->compileCreate($blueprint);
		}
		return $this->compileAlter($blueprint);
	}
	
	
	protected function compileCreate(Blueprint $blueprint): array
	{
		$context = $blueprint->getContext();
		
		// Restricted operations with "CREATE"
		if (
			$blueprint->getDroppedColumns() ||
			$blueprint->getRenamedColumns() ||
			$blueprint->getChangedColumns()
		) {
			throw new \RuntimeException('Error! CREATE TABLE does not support drop/rename/change operations.');
		}
		
		$columns = $blueprint->getColumns();
		if (! $columns) {
			throw new \RuntimeException('Error! CREATE TABLE requires at least one column.');
		}
		
		// 2) собрать определения колонок
		$definitions = [];
		foreach ($columns as $column) {
			$definitions[] = $this->compileCreateColumn($column);
		}
		
		// TODO 3) Indexes
		
		// 4) table options (charset / collation)
		$table = $context->getPrefixedName();
		
		$sql = sprintf(
			"CREATE TABLE %s (\n  %s\n)%s;",
			$table,
			implode(",\n  ", $definitions),
			$this->compileTableOptions($context)
		);
		
		return [$sql];
	}
	
	protected function compileAlter( Blueprint $blueprint ): array {
		// TODO
		return [];
	}
	
	
	protected function compileCreateColumn(Column $column): string
	{
		$sql = [];
		
		// name
		$sql[] = $column->getName();
		
		// type
		$sql[] = $this->compileColumnType($column);
		
		// unsigned
		if ($column->isUnsigned()) {
			$sql[] = 'UNSIGNED';
		}
		
		// nullability
		$sql[] = $column->isNullable() ? 'NULL' : 'NOT NULL';
		
		// default
		if ($column->getDefault() !== null) {
			$sql[] = 'DEFAULT ' . $this->compileDefault($column->getDefault());
		}
		
		// auto increment
		if ($column->isAutoIncrement()) {
			$sql[] = 'AUTO_INCREMENT';
		}
		
		return implode(' ', $sql);
	}
	
	
	protected function compileColumnType(Column $column): string
	{
		$type = $column->getType();
		$args = $column->getArgs();
		
		switch ($type) {
			case 'string':
				$length = $args[0] ?? 255;
				return "VARCHAR({$length})";
			
			case 'integer':
				return 'INT';
			
			case 'bigInteger':
				return 'BIGINT';
			
			default:
				throw new \RuntimeException("Unsupported column type [{$type}]");
		}
	}
	
	
	protected function compileDefault($value): string
	{
		if (is_string($value)) {
			return "'" . addslashes($value) . "'";
		}
		
		if (is_bool($value)) {
			return $value ? '1' : '0';
		}
		
		if ($value === null) {
			return 'NULL';
		}
		
		return (string) $value;
	}
	
	
	protected function compileTableOptions(TableContext $context): string
	{
		if ($context->getCharset() || $context->getCollation()) {
			$parts = [];
			
			if ($context->getCharset()) {
				$parts[] = 'DEFAULT CHARSET=' . $context->getCharset();
			}
			
			if ($context->getCollation()) {
				$parts[] = 'COLLATE=' . $context->getCollation();
			}
			
			return ' ' . implode(' ', $parts);
		}
		
		global $wpdb;
		return ' ' . $wpdb->get_charset_collate();
	}
	
}
