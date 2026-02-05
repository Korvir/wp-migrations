<?php

namespace WPMigrations;

use WP_CLI;
use WPMigrations\Cli\AddMigrationCommand;
use WPMigrations\Cli\MigrateCommand;
use WPMigrations\Cli\MigrationsCommand;
use WPMigrations\Cli\RollbackCommand;
use WPMigrations\Cli\StatusCommand;

if ( defined('WP_CLI') && WP_CLI ) {
	
	WP_CLI::add_command('migrations', MigrationsCommand::class);
	
	WP_CLI::add_command('migrations add', AddMigrationCommand::class);
	
	WP_CLI::add_command('migrations migrate', MigrateCommand::class);
	
	WP_CLI::add_command('migrations rollback', RollbackCommand::class);
	
	WP_CLI::add_command('migrations status', StatusCommand::class);
	
}
