<?php

namespace WPMigrations\Cli;

use WP_CLI;
use WP_CLI_Command;
use WPMigrations\MigrationRunner;

class RollbackCommand extends WP_CLI_Command {
	/**
	 * Rollback migrations.
	 *
	 * By default, rolls back the last migration batch.
	 *
	 * ## OPTIONS
	 *
	 * [--step=<number>]
	 * : Rollback the given number of migrations.
	 *   This option ignores batch boundaries and rolls back
	 *   migrations in reverse execution order.
	 *
	 * [--pretend]
	 * : Show which migrations would be rolled back
	 *   without executing them.
	 *
	 * ## EXAMPLES
	 *
	 *     # Rollback last batch
	 *     wp migrations rollback
	 *
	 *     # Rollback last migration only
	 *     wp migrations rollback --step=1
	 *
	 *     # Preview rollback of last 3 migrations
	 *     wp migrations rollback --step=3 --pretend
	 */
	public function __invoke( $args, $assoc_args ) {
		$runner = new MigrationRunner();
		
		$pretend = isset($assoc_args['pretend']);
		$step = isset($assoc_args['step'])
			? (int)$assoc_args['step']
			: null;
		
		
		// ---- STEP ROLLBACK ----
		if ( $step !== null ) {
			
			$list = $runner->rollbackStepList($step);
			if ( empty($list) ) {
				WP_CLI::success('Nothing to rollback.');
				return;
			}
			
			
			if ( $pretend ) {
				WP_CLI::log("Would rollback {$step} migration(s):");
				WP_CLI::log('');
				foreach ( array_keys($list) as $migration ) {
					WP_CLI::log($migration);
				}
				return;
			}
			
			WP_CLI::log("Rolling back {$step} migration(s):");
			WP_CLI::log('');
			
			foreach ( array_keys($list) as $migration ) {
				WP_CLI::log("Rolling back: {$migration}");
			}
			
			$count = $runner->rollbackSteps($step);
			WP_CLI::log('');
			WP_CLI::success("Rolled back: {$count}");
			return;
		}
		
		
		// ---- DEFAULT (BATCH) ROLLBACK ----
		$list = $runner->rollbackList();
		if ( empty($list) ) {
			WP_CLI::success('Nothing to rollback.');
			return;
		}
		
		if ( $pretend ) {
			$batch = current($list)['batch'];
			WP_CLI::log("Would rollback batch: {$batch}");
			WP_CLI::log('');
			foreach ( array_keys($list) as $migration ) {
				WP_CLI::log($migration);
			}
			return;
		}
		
		
		$batch = current($list)['batch'];
		WP_CLI::log("Rolling back batch: {$batch}");
		WP_CLI::log('');
		
		foreach ( array_keys($list) as $migration ) {
			WP_CLI::log("Rolling back: {$migration}");
		}
		
		$count = $runner->rollback();
		WP_CLI::log('');
		WP_CLI::success("Rolled back: {$count}");
	}
	
}
