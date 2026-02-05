<?php

namespace WPMigrations;

use WPMigrations\Cli\MigrationsCommand;
use WPMigrations\Cli\AddMigrationCommand;
use WPMigrations\Cli\MigrateCommand;

if (defined('WP_CLI') && WP_CLI) {
	
	\WP_CLI::add_command('migrations', MigrationsCommand::class);
	
	\WP_CLI::add_command('migrations add', AddMigrationCommand::class);
	
	\WP_CLI::add_command('migrations migrate', MigrateCommand::class);
	
}
