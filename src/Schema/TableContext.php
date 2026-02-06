<?php

namespace WPMigrations\Schema;

final class TableContext
{
	protected string $name;
	protected string $mode;
	
	protected ?string $charset = null;
	protected ?string $collation = null;
	
	public function __construct(string $name, string $mode)
	{
		$this->name = $name;
		$this->mode = $mode;
	}
	
	public function setCharset(string $charset): void
	{
		$this->charset = $charset;
	}
	
	public function setCollation(string $collation): void
	{
		$this->collation = $collation;
	}
	
	public function getName(): string { return $this->name; }
	public function getMode(): string { return $this->mode; }
	
	public function getCharset(): ?string { return $this->charset; }
	public function getCollation(): ?string { return $this->collation; }
}

