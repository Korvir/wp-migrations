<?php

namespace WPMigrations\Tests;

use PHPUnit\Framework\TestCase;

class StubTemplatesTest extends TestCase {
	public function test_all_default_stubs_implement_migration_interface(): void {
		$stubDir = dirname(__DIR__) . '/stubs';
		$files = [ 'create.stub.php', 'update.stub.php', 'rename.stub.php', 'drop.stub.php', 'default.stub.php' ];

		foreach ( $files as $file ) {
			$content = file_get_contents($stubDir . '/' . $file);
			$this->assertStringContainsString('implements MigrationInterface', $content, $file . ' must implement MigrationInterface');
		}
	}
}
