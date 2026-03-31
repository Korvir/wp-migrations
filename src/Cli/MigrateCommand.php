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
	 * [--only=<names>]
	 * : Comma-separated list of migration name fragments to include.
	 *
	 * [--except=<names>]
	 * : Comma-separated list of migration name fragments to exclude.
	 *
	 * [--step=<number>]
	 * : Run only the next N pending migrations.
	 *
	 * [--path=<path>]
	 * : Custom path to migrations directory.
	 *
	 * [--force]
	 * : Force execution in production environment.
	 *
	 * [--pretend]
	 * : Show which migrations would be executed without running them.
	 *
	 * ## EXAMPLES
	 *
	 *     wp migrations migrate
	 *     wp migrations migrate create_users_table
	 *     wp migrations migrate --step=1
	 *     wp migrations migrate --path=wp-content/themes/my-theme/migrations
	 *     wp migrations migrate --pretend
	 *
	 * @throws ExitException
	 */
	public function __invoke( $args, $assoc_args ) {
		$name = $args[0] ?? null;
		$pretend = isset($assoc_args['pretend']);
		$step = isset($assoc_args['step']) ? (int)$assoc_args['step'] : null;
		$only = !empty($assoc_args['only'])
			? array_values(array_filter(array_map('trim', explode(',', $assoc_args['only'])), 'strlen'))
			: null;
		$except = !empty($assoc_args['except'])
			? array_values(array_filter(array_map('trim', explode(',', $assoc_args['except'])), 'strlen'))
			: null;

		if ( !empty($assoc_args['only']) && !empty($assoc_args['except']) ) {
			WP_CLI::error('--only and --except cannot be used together.');
		}

		if ( $name ) {
			$only = null;
			$except = null;
			$step = null;
		}

		if ( $step !== null && $step <= 0 ) {
			WP_CLI::error('--step must be greater than 0.');
		}

		if ( $this->isProduction() && !isset($assoc_args['force']) ) {
			WP_CLI::error('Application is in production. Use --force to run migrations.');
		}

		$config = [];
		if ( !empty($assoc_args['path']) ) {
			$config['path'] = rtrim($assoc_args['path'], '/');
		}

		$runner = new MigrationRunner($config);

		try {
			$pending = $runner->pending($name, $only, $except, $step);
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

			$count = $runner->migrate($name, $only, $except, $step);
			WP_CLI::success("Migrations executed: {$count}");
		} catch ( Throwable $e ) {
			WP_CLI::error($e->getMessage());
		}
	}

	private function isProduction(): bool {
		if ( function_exists('wp_get_environment_type') ) {
			return wp_get_environment_type() === 'production';
		}

		return false;
	}
}
