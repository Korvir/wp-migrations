<?php

namespace WPMigrations\Schema;

final class Index {
	
	protected string $type; // primary | unique | index
	protected array $columns;
	protected ?string $name;
	
	public function __construct( string $type, array $columns, ?string $name = null ) {
		$this->type = $type;
		$this->columns = $columns;
		$this->name = $name;
	}
	
	
	public function getType(): string {
		return $this->type;
	}
	
	public function getColumns(): array {
		return $this->columns;
	}
	
	public function getName(): ?string {
		return $this->name;
	}
}

