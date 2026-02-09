<?php

namespace WPMigrations\Schema;

final class ForeignKey {
	protected string $column;
	protected string $references;
	protected string $on;
	protected ?string $onDelete = null;
	protected ?string $onUpdate = null;
	protected ?string $name = null;
	
	public function __construct( string $column ) {
		$this->column = $column;
	}
	
	/* -------- setters -------- */
	
	public function references( string $column ): self {
		$this->references = $column;
		return $this;
	}
	
	public function on( string $table ): self {
		$this->on = $table;
		return $this;
	}
	
	public function onDelete( string $action ): self {
		$this->onDelete = $action;
		return $this;
	}
	
	public function onUpdate( string $action ): self {
		$this->onUpdate = $action;
		return $this;
	}
	
	public function name( string $name ): self {
		$this->name = $name;
		return $this;
	}
	
	/* -------- getters -------- */
	
	public function getColumn(): string {
		return $this->column;
	}
	
	public function getReferences(): string {
		return $this->references;
	}
	
	public function getOn(): string {
		return $this->on;
	}
	
	public function getOnDelete(): ?string {
		return $this->onDelete;
	}
	
	public function getOnUpdate(): ?string {
		return $this->onUpdate;
	}
	
	public function getName(): ?string {
		return $this->name;
	}
}
