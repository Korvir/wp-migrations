<?php

namespace WPMigrations\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WPMigrations\Database\Grammar\MySqlGrammar;

class MySqlGrammarTest extends TestCase {
	public function test_wrap_identifier_quotes_safe_identifier(): void {
		$grammar = new MySqlGrammar();
		$this->assertSame('`users`', $grammar->wrapIdentifier('users'));
	}

	public function test_wrap_identifier_rejects_backtick_in_identifier(): void {
		$grammar = new MySqlGrammar();
		$this->expectException(InvalidArgumentException::class);
		$grammar->wrapIdentifier('bad`name');
	}
}
