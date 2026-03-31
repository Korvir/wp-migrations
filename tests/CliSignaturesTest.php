<?php

namespace WPMigrations\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use WPMigrations\Cli\FreshCommand;
use WPMigrations\Cli\ResetCommand;

class CliSignaturesTest extends TestCase {
	public function test_fresh_and_reset_commands_accept_wp_cli_args_and_assoc_args(): void {
		$fresh = new ReflectionMethod(FreshCommand::class, '__invoke');
		$reset = new ReflectionMethod(ResetCommand::class, '__invoke');

		$this->assertSame(2, $fresh->getNumberOfParameters());
		$this->assertSame(2, $reset->getNumberOfParameters());
	}
}
