<?php

namespace WPMigrations\Cli;

use Throwable;
use WP_CLI_Command;
use WPMigrations\MigrationRunner;

class MigrateCommand extends WP_CLI_Command {
	/**
	 * Run migrations.
	 *
	 * ## OPTIONS
	 *
	 * [<name>]
	 * : Optional migration name.
	 *
	 * ## EXAMPLES
	 *
	 *     wp migrations migrate
	 *     wp migrations migrate 2026_02_05_create_users_table
	 */
	public function __invoke( $args, $assoc_args ) {
		$name = $args[0] ?? null;
		$runner = new MigrationRunner();
		
		try {
			$pending = $runner->pending($name);
			if ( empty($pending) ) {
				WP_CLI::success('Nothing to migrate.');
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
