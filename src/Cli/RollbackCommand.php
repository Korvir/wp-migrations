<?php

namespace WPMigrations\Cli;

use Exception;
use WP_CLI;
use WP_CLI_Command;
use WPMigrations\MigrationRunner;

class RollbackCommand extends WP_CLI_Command {
	/**
	 * Rollback migrations.
	 *
	 * ## OPTIONS
	 *
	 * [--step=<number>]
	 * : Rollback the given number of migrations.
	 *
	 * ## EXAMPLES
	 *
	 *     wp migrations rollback
	 *     wp migrations rollback --step=1
	 *     wp migrations rollback --step=3
	 */
	public function __invoke($args, $assoc_args)
	{
		$runner = new MigrationRunner();
		
		$step = isset($assoc_args['step'])
			? (int) $assoc_args['step']
			: null;
		
		// ---- STEP ROLLBACK ----
		if ($step !== null) {
			
			$list = $runner->rollbackStepList($step);
			if (empty($list)) {
				\WP_CLI::success('Nothing to rollback.');
				return;
			}
			
			\WP_CLI::log("Rolling back {$step} migration(s):");
			\WP_CLI::log('');
			
			foreach (array_keys($list) as $migration) {
				\WP_CLI::log("Rolling back: {$migration}");
			}
			
			$count = $runner->rollbackSteps($step);
			\WP_CLI::log('');
			\WP_CLI::success("Rolled back: {$count}");
			return;
		}
		
		
		// ---- DEFAULT (BATCH) ROLLBACK ----
		$list = $runner->rollbackList();
		if (empty($list)) {
			\WP_CLI::success('Nothing to rollback.');
			return;
		}
		
		$batch = current($list)['batch'];
		\WP_CLI::log("Rolling back batch: {$batch}");
		\WP_CLI::log('');
		
		foreach (array_keys($list) as $migration) {
			\WP_CLI::log("Rolling back: {$migration}");
		}
		
		$count = $runner->rollback();
		\WP_CLI::log('');
		\WP_CLI::success("Rolled back: {$count}");
	}

}
