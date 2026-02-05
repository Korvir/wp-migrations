<?php

namespace WPMigrations\Cli;

use WPMigrations\MigrationRunner;
use WP_CLI;
use WP_CLI_Command;

class StatusCommand extends WP_CLI_Command
{
	/**
	 * Show migrations status.
	 *
	 * ## EXAMPLES
	 *
	 *     wp migrations status
	 */
	public function __invoke($args, $assoc_args)
	{
		$runner = new MigrationRunner();
		
		$status = $runner->status();
		
		if (empty($status)) {
			WP_CLI::success('No migrations found.');
			return;
		}
		
		$table = [];
		
		foreach ($status as $name => $row) {
			
			$table[] = [
				'Migration' => $name,
				'Batch'     => $row['batch'] ?? '-',
				'Status'    => $row['status'],
			];
		}
		
		\WP_CLI\Utils\format_items(
			'table',
			$table,
			['Migration', 'Batch', 'Status']
		);
	}
}
