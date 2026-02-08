<?php

namespace WPMigrations\Cli;

use WP_CLI;
use WP_CLI\ExitException;
use WPMigrations\Migrations\MigrationRunner;

class InstallCommand {
	
	/**
	 * Create a migrations table.
	 *
	 * @return void
	 * @throws ExitException
	 */
	public function __invoke() {
		$runner = new MigrationRunner();
		try {
			$runner->install();
			WP_CLI::success('Migrations table created.');
		} catch (\Throwable $e) {
			WP_CLI::error($e->getMessage());
		}
	}
	
}
