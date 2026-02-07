<?php

namespace WPMigrations\Cli;

use Throwable;
use WP_CLI;
use WP_CLI\ExitException;
use WP_CLI_Command;
use WPMigrations\Migrations\MigrationRunner;

class MigrateCommand extends WP_CLI_Command {
	/**
	 * Run pending migrations.
	 *
	 * ## OPTIONS
	 *
	 * [<name>]
	 * : Optional migration name. If provided, only this migration
	 *   will be executed (if pending).
	 *
	 * [--pretend]
	 * : Show which migrations would be executed
	 *   without running them.
	 *
	 * ## EXAMPLES
	 *
	 *     # Run all pending migrations
	 *     wp migrations migrate
	 *
	 *     # Run a specific migration
	 *     wp migrations migrate create_users_table
	 *
	 *     # Preview pending migrations
	 *     wp migrations migrate --pretend
	 *
	 * @throws ExitException
	 */
	public function __invoke( $args, $assoc_args ) {
		$name = $args[0] ?? null;
		$pretend = isset($assoc_args['pretend']);
		
		$runner = new MigrationRunner();
		try {
			$pending = $runner->pending($name);
			if ( empty($pending) ) {
				WP_CLI::success('Nothing to migrate.');
				return;
			}
			
			if ( $pretend ) {
				WP_CLI::log('Would run migrations:');
				WP_CLI::log('');
				foreach ( array_keys($pending) as $migration ) {
					WP_CLI::log($migration);
				}
				return;
			}
			
			foreach ( array_keys($pending) as $migration ) {
				WP_CLI::log("Migrating: {$migration}");
			}
			$count = $runner->migrate($name);
			WP_CLI::success("Migrations executed: {$count}");
			
		} catch ( Throwable $e ) {
			WP_CLI::error($e->getMessage());
		}
	}
	
	
}
