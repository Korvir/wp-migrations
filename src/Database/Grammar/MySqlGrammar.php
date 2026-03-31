<?php

namespace WPMigrations\Database\Grammar;

use InvalidArgumentException;

final class MySqlGrammar implements GrammarInterface {
	public function wrapTable( string $name ): string {
		return $this->wrapIdentifier($name);
	}

	public function wrapIdentifier( string $name ): string {
		if ( !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) ) {
			throw new InvalidArgumentException("Unsafe SQL identifier [{$name}].");
		}

		return '`' . $name . '`';
	}

	public function wrapIdentifierList( array $names ): string {
		if ( empty($names) ) {
			throw new InvalidArgumentException('Identifier list cannot be empty.');
		}

		return implode(', ', array_map([$this, 'wrapIdentifier'], $names));
	}

	public function qualifyTable( string $name, string $prefix ): string {
		return $prefix . $name;
	}
}
