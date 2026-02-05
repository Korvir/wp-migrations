<?php

namespace WPMigrations\Cli;

use WPMigrations\MigrationRunner;
use WP_CLI;
use WP_CLI_Command;

class RollbackCommand extends WP_CLI_Command
{
	/**
	 * Rollback last migration batch.
	 *
	 * ## EXAMPLES
	 *
	 *     wp migrations rollback
	 *
	 * @throws \Exception
	 */
	public function __invoke($args, $assoc_args)
	{
		$runner = new MigrationRunner();
		
		$list = $runner->rollbackList();
		
		if (empty($list)) {
			WP_CLI::success('Nothing to rollback.');
			return;
		}
		
		$batch = current($list)['batch'];
		
		WP_CLI::log("Rolling back batch: {$batch}");
		WP_CLI::log('');
		
		foreach (array_keys($list) as $migration) {
			WP_CLI::log("Rolling back: {$migration}");
		}
		
		$count = $runner->rollback();
		
		WP_CLI::log('');
		WP_CLI::success("Rolled back: {$count}");
	}
}
