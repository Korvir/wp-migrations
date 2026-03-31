<?php

namespace WPMigrations\Database\Grammar;

interface GrammarInterface {
	public function wrapTable( string $name ): string;

	public function wrapIdentifier( string $name ): string;

	public function wrapIdentifierList( array $names ): string;

	public function qualifyTable( string $name, string $prefix ): string;
}
