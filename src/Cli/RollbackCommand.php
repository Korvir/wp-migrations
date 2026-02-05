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
	 */
	public function __invoke($args, $assoc_args)
	{
		$runner = new MigrationRunner();
		
		$count = $runner->rollback();
		
		if ($count === 0) {
			WP_CLI::success('Nothing to rollback.');
			return;
		}
		
		WP_CLI::success("Rolled back: {$count}");
	}
}
