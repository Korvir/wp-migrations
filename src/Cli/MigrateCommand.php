<?php

namespace WPMigrations\Cli;

use WPMigrations\MigrationRunner;
use WP_CLI;
use WP_CLI_Command;

class MigrateCommand extends WP_CLI_Command
{
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
	public function __invoke($args, $assoc_args)
	{
		$name = $args[0] ?? null;
		
		$runner = new MigrationRunner();
		
		$count = $runner->migrate($name);
		
		if ($count === 0) {
			WP_CLI::success('Nothing to migrate.');
			return;
		}
		
		WP_CLI::success("Migrations executed: {$count}");
	}
}
