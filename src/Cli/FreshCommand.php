<?php

namespace WPMigrations\Cli;

use WP_CLI;
use WP_CLI_Command;
use WPMigrations\MigrationRunner;

class FreshCommand extends WP_CLI_Command {
	/**
	 * Reset and re-run all migrations.
	 *
	 * This command is equivalent to running:
	 * - wp migrations reset
	 * - wp migrations migrate
	 *
	 * ## OPTIONS
	 *
	 * [--pretend]
	 * : Show which migrations would be rolled back and re-run
	 *   without executing them.
	 *
	 * ## EXAMPLES
	 *
	 *     # Rebuild database schema from scratch
	 *     wp migrations fresh
	 *
	 *     # Preview full rebuild
	 *     wp migrations fresh --pretend
	 */
	public function __invoke() {
		$pretend = isset($assoc_args['pretend']);
		
		$runner = new MigrationRunner();
		$resetList = $runner->resetList();
		
		
		// --- PRETEND RESET ---
		if ( $pretend && !empty($resetList) ) {
			WP_CLI::log('Would reset database:');
			WP_CLI::log('');
			foreach ( $resetList as $row ) {
				WP_CLI::log(
					sprintf('%s (batch %d)', $row['migration'], $row['batch'])
				);
			}
			WP_CLI::log('');
		}
		
		
		// --- RESET ---
		if ( !empty($resetList) ) {
			
			WP_CLI::log('Resetting database...');
			WP_CLI::log('');
			
			foreach ( $resetList as $row ) {
				WP_CLI::log(
					sprintf(
						'Rolling back: %s (batch %d)',
						$row['migration'],
						$row['batch']
					)
				);
			}
			
			WP_CLI::log('');
			$runner->reset();
		}
		
		
		// --- PRETEND MIGRATE ---
		$pending = $runner->pending();
		
		if ( $pretend && !empty($pending) ) {
			WP_CLI::log('Would run migrations:');
			WP_CLI::log('');
			foreach ( array_keys($pending) as $migration ) {
				WP_CLI::log($migration);
			}
			return;
		}
		
		
		// --- MIGRATE ---
		if ( empty($pending) ) {
			WP_CLI::success('Database fresh. No migrations to run.');
			return;
		}
		
		WP_CLI::log('Running migrations:');
		WP_CLI::log('');
		
		foreach ( array_keys($pending) as $migration ) {
			WP_CLI::log("Migrating: {$migration}");
		}
		
		$count = $runner->migrate();
		
		WP_CLI::log('');
		WP_CLI::success("Database fresh. Migrations executed: {$count}");
	}
}
