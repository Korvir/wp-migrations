<?php

namespace WPMigrations\Schema;

final class ForeignIdDefinition {
	private Blueprint $blueprint;
	private Column $column;
	private string $columnName;
	private ForeignKey $foreignKey;

	public function __construct( Blueprint $blueprint, Column $column, string $columnName ) {
		$this->blueprint = $blueprint;
		$this->column = $column;
		$this->columnName = $columnName;
		$this->foreignKey = $this->blueprint->foreign($columnName);
	}

	public function constrained( ?string $table = null, string $column = 'id' ): self {
		$table = $table ?? $this->guessTableName();

		$this->foreignKey
			->references($column)
			->on($table);

		return $this;
	}

	public function cascadeOnDelete(): self {
		$this->foreignKey->onDelete('cascade');
		return $this;
	}

	public function cascadeOnUpdate(): self {
		$this->foreignKey->onUpdate('cascade');
		return $this;
	}

	public function restrictOnDelete(): self {
		$this->foreignKey->onDelete('restrict');
		return $this;
	}

	public function nullOnDelete(): self {
		$this->foreignKey->onDelete('set null');
		return $this;
	}

	public function column(): Column {
		return $this->column;
	}

	public function __call( string $method, array $args ) {
		$result = $this->column->{$method}(...$args);

		if ( $result === $this->column ) {
			return $this;
		}

		return $result;
	}

	private function guessTableName(): string {
		if ( substr($this->columnName, -3) === '_id' ) {
			return substr($this->columnName, 0, -3) . 's';
		}

		return $this->columnName;
	}
}
