<?php

namespace WPMigrations\Cli;

use Throwable;
use WP_CLI;
use WP_CLI_Command;
use WPMigrations\Migrations\MigrationRunner;

class FreshCommand extends WP_CLI_Command {
	/**
	 * Reset and re-run all migrations.
	 *
	 * ## OPTIONS
	 *
	 * [--path=<path>]
	 * : Custom path to migrations directory.
	 *
	 * [--pretend]
	 * : Show which migrations would be rolled back and re-run without executing them.
	 */
	public function __invoke( $args, $assoc_args ) {
		$pretend = isset($assoc_args['pretend']);

		$config = [];
		if ( !empty($assoc_args['path']) ) {
			$config['path'] = rtrim($assoc_args['path'], '/');
		}

		$runner = new MigrationRunner($config);

		try {
			$resetList = $runner->resetList();
			if ( $pretend && !empty($resetList) ) {
				WP_CLI::log('Would reset database:');
				WP_CLI::log('');
				foreach ( $resetList as $row ) {
					WP_CLI::log(sprintf('%s (batch %d)', $row['migration'], $row['batch']));
				}
				WP_CLI::log('');
			}

			if ( !empty($resetList) && !$pretend ) {
				WP_CLI::log('Resetting database...');
				WP_CLI::log('');
				foreach ( $resetList as $row ) {
					WP_CLI::log(sprintf('Rolling back: %s (batch %d)', $row['migration'], $row['batch']));
				}
				WP_CLI::log('');
				$runner->reset();
			}

			$pending = $runner->pending();
			if ( $pretend && !empty($pending) ) {
				WP_CLI::log('Would run migrations:');
				WP_CLI::log('');
				foreach ( array_keys($pending) as $migration ) {
					WP_CLI::log($migration);
				}
				return;
			}

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
		} catch ( Throwable $e ) {
			WP_CLI::error($e->getMessage());
		}
	}
}
