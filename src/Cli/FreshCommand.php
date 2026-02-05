<?php

namespace WPMigrations\Cli;

use WPMigrations\MigrationRunner;

class FreshCommand extends \WP_CLI_Command
{
	/**
	 * Reset and re-run all migrations.
	 *
	 * ## EXAMPLES
	 *
	 *     wp migrations fresh
	 */
	public function __invoke()
	{
		$runner = new MigrationRunner();
		
		// --- RESET ---
		$resetList = $runner->resetList();
		
		if (! empty($resetList)) {
			
			\WP_CLI::log('Resetting database...');
			\WP_CLI::log('');
			
			foreach ($resetList as $row) {
				\WP_CLI::log(
					sprintf(
						'Rolling back: %s (batch %d)',
						$row['migration'],
						$row['batch']
					)
				);
			}
			
			\WP_CLI::log('');
			$runner->reset();
		}
		
		// --- MIGRATE ---
		$pending = $runner->pending();
		if (empty($pending)) {
			\WP_CLI::success('Database fresh. No migrations to run.');
			return;
		}
		
		\WP_CLI::log('Running migrations:');
		\WP_CLI::log('');
		
		foreach (array_keys($pending) as $migration) {
			\WP_CLI::log("Migrating: {$migration}");
		}
		
		$count = $runner->migrate();
		
		\WP_CLI::log('');
		\WP_CLI::success("Database fresh. Migrations executed: {$count}");
	}
}
