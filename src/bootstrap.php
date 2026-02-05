<?php

namespace WPMigrations;

use WPMigrations\Cli\AddMigrationCommand;

if (defined('WP_CLI') && WP_CLI) {
	
	\WP_CLI::add_command(
		'migrations add',
		AddMigrationCommand::class
	);
}
