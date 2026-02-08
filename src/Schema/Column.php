<?php

namespace WPMigrations\Schema;

final class Column {
	protected string $name;
	protected string $type;
	protected array $args;
	
	protected bool $nullable = false;
	protected bool $unsigned = false;
	protected bool $isChange = false;
	
	protected $default = null;
	
	protected ?string $after = null;
	protected bool $first = false;
	
	protected bool $autoIncrement = false;
	protected bool $dropAutoIncrement = false;
	
	protected ?string $comment = null;
	protected ?string $charset = null;
	protected ?string $collation = null;
	
	
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
	
	public function default( $value ): self {
		$this->default = $value;
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
	
	public function autoIncrement(): self {
		$this->autoIncrement = true;
		return $this;
	}
	
	public function removeAutoIncrement(): self {
		$this->autoIncrement = false;
		$this->dropAutoIncrement = true;
		return $this;
	}
	
	public function comment( string $comment ): self {
		$this->comment = $comment;
		return $this;
	}
	
	public function charset( string $charset ): self {
		$this->charset = $charset;
		return $this;
	}
	
	public function collation( string $collation ): self {
		$this->collation = $collation;
		return $this;
	}
	
	// --- getters for compiler ---
	
	public function isChange(): bool {
		return $this->isChange;
	}
	
	public function getName(): string {
		return $this->name;
	}
	
	public function getType(): string {
		return $this->type;
	}
	
	public function getArgs(): array {
		return $this->args;
	}
	
	public function isNullable(): bool {
		return $this->nullable;
	}
	
	public function isUnsigned(): bool {
		return $this->unsigned;
	}
	
	public function getDefault() {
		return $this->default;
	}
	
	public function getAfter(): ?string {
		return $this->after;
	}
	
	public function isFirst(): bool {
		return $this->first;
	}
	
	public function isAutoIncrement(): bool {
		return $this->autoIncrement;
	}
	
	public function getComment(): ?string {
		return $this->comment;
	}
	
	public function getCharset(): ?string {
		return $this->charset;
	}
	
	public function getCollation(): ?string {
		return $this->collation;
	}
	
	public function shouldDropAutoIncrement(): bool {
		return $this->dropAutoIncrement;
	}

}

