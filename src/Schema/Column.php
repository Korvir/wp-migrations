<?php

namespace WPMigrations\Schema;

final class Column {
	protected string $name;
	protected string $type;
	protected array $args;
	
	protected bool $nullable = false;
	protected bool $unsigned = false;
	protected bool $autoIncrement = false;
	protected bool $isChange = false;
	
	protected mixed $default = null;
	
	protected ?string $after = null;
	protected bool $first = false;
	
	public function __construct( string $name, string $type, array $args = [] ) {
		$this->name = $name;
		$this->type = $type;
		$this->args = $args;
	}
	
	public function nullable(): self {
		$this->nullable = true;
		return $this;
	}
	
	public function notNullable(): self {
		$this->nullable = false;
		return $this;
	}
	
	public function unsigned(): self {
		$this->unsigned = true;
		return $this;
	}
	
	public function default( mixed $value ): self {
		$this->default = $value;
		return $this;
	}
	
	public function autoIncrement(): self {
		$this->autoIncrement = true;
		return $this;
	}
	
	public function after( string $column ): self {
		$this->after = $column;
		return $this;
	}
	
	public function first(): self {
		$this->first = true;
		return $this;
	}
	
	public function change(): self {
		$this->isChange = true;
		return $this;
	}
	
	// --- getters for compiler ---
	
	public function isChange(): bool { return $this->isChange; }
	
	public function getName(): string { return $this->name; }
	
	public function getType(): string { return $this->type; }
	
	public function getArgs(): array { return $this->args; }
	
	public function isNullable(): bool { return $this->nullable; }
	
	public function isUnsigned(): bool { return $this->unsigned; }
	
	public function isAutoIncrement(): bool { return $this->autoIncrement; }
	
	public function getDefault(): mixed { return $this->default; }
	
	public function getAfter(): ?string { return $this->after; }
	
	public function isFirst(): bool { return $this->first; }
}

