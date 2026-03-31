<?php

namespace WPMigrations\Database\Grammar;

use InvalidArgumentException;

final class MySqlGrammar implements GrammarInterface {
	public function wrapTable( string $name ): string {
		return $this->wrapIdentifier($name);
	}

	public function wrapIdentifier( string $name ): string {
		if ( $name === '' ) {
			throw new InvalidArgumentException('SQL identifier cannot be empty.');
		}

		if ( strpos($name, '`') !== false ) {
			throw new InvalidArgumentException("Unsafe SQL identifier [{$name}] contains backtick.");
		}

		if ( preg_match('/[\x00-\x1F\x7F]/', $name) ) {
			throw new InvalidArgumentException("Unsafe SQL identifier [{$name}] contains control characters.");
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
