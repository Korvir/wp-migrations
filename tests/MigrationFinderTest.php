<?php

namespace WPMigrations\Tests;

use PHPUnit\Framework\TestCase;
use WPMigrations\Migrations\MigrationFinder;
use WPMigrations\Migrations\MigrationRepository;

class MigrationFinderTest extends TestCase {
	public function test_except_filter_excludes_match_at_zero_position(): void {
		$dir = sys_get_temp_dir() . '/wp_migrations_' . uniqid('', true);
		mkdir($dir, 0777, true);

		file_put_contents($dir . '/2026_01_01_000000_create_users_table.php', "<?php return null;");
		file_put_contents($dir . '/2026_01_01_000001_create_posts_table.php', "<?php return null;");

		$repository = new class extends MigrationRepository {
			public function __construct() {}
			public function ensureTable(): void {}
			public function has( string $migration ): bool { return false; }
		};

		$finder = new MigrationFinder($dir, $repository);
		$pending = $finder->pending(null, null, [ '2026_01_01_000000' ]);

		$this->assertArrayNotHasKey('2026_01_01_000000_create_users_table', $pending);
		$this->assertArrayHasKey('2026_01_01_000001_create_posts_table', $pending);
	}
}
