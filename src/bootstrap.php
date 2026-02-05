<?php

namespace WPMigrations;

use WPMigrations\Cli\MigrationsCommand;
use WPMigrations\Cli\AddMigrationCommand;
use WPMigrations\Cli\MigrateCommand;
use WPMigrations\Cli\RollbackCommand;

if (defined('WP_CLI') && WP_CLI) {
	
	\WP_CLI::add_command('migrations', MigrationsCommand::class);
	
	\WP_CLI::add_command('migrations add', AddMigrationCommand::class);
	
	\WP_CLI::add_command('migrations migrate', MigrateCommand::class);
	
	\WP_CLI::add_command('migrations rollback', RollbackCommand::class);
	
}
