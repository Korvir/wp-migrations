<?php

namespace WPMigrations\Schema;

class Expression {
	
	protected string $value;
	
	public function __construct( string $value ) {
		$this->value = $value;
	}
	
	public function getValue(): string {
		return $this->value;
	}
	
	public function __toString(): string {
		return $this->value;
	}
	
}
