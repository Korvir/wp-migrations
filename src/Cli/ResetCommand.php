<?php

namespace WPMigrations\Cli;

use WPMigrations\MigrationRunner;

class ResetCommand extends \WP_CLI_Command
{
	/**
	 * Rollback all migrations.
	 *
	 * ## EXAMPLES
	 *
	 *     wp migrations reset
	 */
	public function __invoke()
	{
		$runner = new MigrationRunner();
		
		$list = $runner->resetList();
		
		if (empty($list)) {
			\WP_CLI::success('Nothing to reset.');
			return;
		}
		
		\WP_CLI::log('Rolling back all migrations:');
		\WP_CLI::log('');
		
		foreach ($list as $row) {
			\WP_CLI::log(
				sprintf(
					'Rolling back: %s (batch %d)',
					$row['migration'],
					$row['batch']
				)
			);
		}
		
		\WP_CLI::log('');
		$count = $runner->reset();
		\WP_CLI::success("Database reset complete. Rolled back: {$count}");
	}
}
