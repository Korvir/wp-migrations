<?php

namespace WPMigrations\Cli;

use RuntimeException;
use WP_CLI;
use WP_CLI\ExitException;
use WP_CLI_Command;

class AddMigrationCommand extends WP_CLI_Command {
	/**
	 * Create a new migration file.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : Migration name. Example: create_users_table
	 *
	 * [--path=<path>]
	 * : Custom path to migrations directory.
	 *
	 * ## EXAMPLES
	 *
	 *     wp migrations add create_users_table
	 */
	public function __invoke( $args, $assoc_args ) {
		$name = $args[0] ?? null;
		if ( !$name ) {
			WP_CLI::error('Migration name required.');
		}

		$path = $this->getMigrationsPath($assoc_args['path'] ?? null);
		if ( !is_dir($path) ) {
			mkdir($path, 0755, true);
		}

		$file = $this->generateFileName($name, $path);
		file_put_contents($file, $this->getStub($name));

		WP_CLI::success("Migration created: {$file}");
	}

	protected function getMigrationsPath( ?string $path = null ): string {
		if ( $path ) {
			return rtrim($path, '/');
		}

		if ( defined('WP_MIGRATIONS_PATH') ) {
			return rtrim(WP_MIGRATIONS_PATH, '/');
		}

		if ( function_exists('get_stylesheet_directory') ) {
			return get_stylesheet_directory() . '/migrations';
		}

		return WP_CONTENT_DIR . '/migrations';
	}

	protected function getStubPath(): string {
		if ( defined('WP_MIGRATIONS_STUB_PATH') ) {
			return rtrim(WP_MIGRATIONS_STUB_PATH, '/');
		}

		if ( function_exists('get_stylesheet_directory') ) {
			$project = get_stylesheet_directory() . '/migrations/stubs';
			if ( is_dir($project) ) {
				return $project;
			}
		}

		return dirname(__DIR__, 2) . '/stubs';
	}

	protected function generateFileName( string $name, string $path ): string {
		$timestamp = date('Y_m_d_His');
		$slug = strtolower(preg_replace('/[^a-z0-9_]+/i', '_', $name));

		return "{$path}/{$timestamp}_{$slug}.php";
	}

	protected function guessTableName( string $name ): string {
		$name = strtolower($name);
		$name = preg_replace('/^create_/', '', $name);
		$name = preg_replace('/_table$/', '', $name);

		return $name ?: 'table_name';
	}

	protected function getStub( string $name ): string {
		$stubFile = $this->resolveStubFile($name);
		if ( !file_exists($stubFile) ) {
			throw new RuntimeException('Migration stub not found: ' . basename($stubFile));
		}

		$stub = file_get_contents($stubFile);
		$replacements = $this->buildStubReplacements($name);

		return str_replace(array_keys($replacements), array_values($replacements), $stub);
	}

	protected function resolveStubFile( string $name ): string {
		$prefix = strtolower(strtok($name, '_'));
		$map = [
			'create' => 'create.stub.php',
			'update' => 'update.stub.php',
			'rename' => 'rename.stub.php',
			'drop'   => 'drop.stub.php',
		];
		$file = $map[ $prefix ] ?? 'default.stub.php';

		return $this->getStubPath() . '/' . $file;
	}

	protected function buildStubReplacements( string $name ): array {
		$parts = explode('_', strtolower($name));
		$table = $this->guessTableName($name);
		$from = $this->guessRenameFrom($parts);
		$to = $this->guessRenameTo($parts);

		return [
			'{{ table }}' => $table,
			'{{table}}'   => $table,
			'{{ from }}'  => $from,
			'{{from}}'    => $from,
			'{{ to }}'    => $to,
			'{{to}}'      => $to,
		];
	}

	protected function guessRenameFrom( array $parts ): string {
		$toIndex = array_search('to', $parts, true);
		if ( $toIndex === false ) {
			return '';
		}

		return implode('_', array_slice($parts, 1, $toIndex - 1));
	}

	protected function guessRenameTo( array $parts ): string {
		$toIndex = array_search('to', $parts, true);
		if ( $toIndex === false ) {
			return '';
		}

		return implode('_', array_slice($parts, $toIndex + 1));
	}
}
