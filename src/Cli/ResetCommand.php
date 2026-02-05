<?php

namespace WPMigrations\Cli;

use WP_CLI;
use WP_CLI_Command;
use WPMigrations\MigrationRunner;

class ResetCommand extends WP_CLI_Command {
	/**
	 * Rollback all executed migrations.
	 *
	 * This command rolls back all migrations in reverse order
	 * until the migration history is empty.
	 *
	 * ## OPTIONS
	 *
	 * [--pretend]
	 * : Show which migrations would be rolled back
	 *   without executing them.
	 *
	 * ## EXAMPLES
	 *
	 *     # Rollback all migrations
	 *     wp migrations reset
	 *
	 *     # Preview full rollback
	 *     wp migrations reset --pretend
	 */
	public function __invoke() {
		$pretend = isset($assoc_args['pretend']);
		
		$runner = new MigrationRunner();
		
		$list = $runner->resetList();
		if ( empty($list) ) {
			WP_CLI::success('Nothing to reset.');
			return;
		}
		
		if ( $pretend ) {
			WP_CLI::log('Would rollback all migrations:');
			WP_CLI::log('');
			foreach ( $list as $row ) {
				WP_CLI::log(
					sprintf('%s (batch %d)', $row['migration'], $row['batch'])
				);
			}
			return;
		}
		
		WP_CLI::log('Rolling back all migrations:');
		WP_CLI::log('');
		
		foreach ( $list as $row ) {
			WP_CLI::log(
				sprintf(
					'Rolling back: %s (batch %d)',
					$row['migration'],
					$row['batch']
				)
			);
		}
		
		WP_CLI::log('');
		$count = $runner->reset();
		WP_CLI::success("Database reset complete. Rolled back: {$count}");
	}
}
