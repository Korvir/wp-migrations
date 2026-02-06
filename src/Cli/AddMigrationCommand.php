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
	 * ## EXAMPLES
	 *
	 *     wp migrations add create_users_table
	 *
	 * @throws ExitException
	 */
	public function __invoke( $args, $assoc_args ) {
		$name = $args[0] ?? null;
		if ( !$name ) {
			WP_CLI::error('Migration name required.');
		}
		
		$path = $this->getMigrationsPath();
		if ( !is_dir($path) ) {
			mkdir($path, 0755, true);
		}
		
		$file = $this->generateFileName($name, $path);
		file_put_contents(
			$file,
			$this->getStub($name)
		);
		
		WP_CLI::success("Migration created: {$file}");
	}
	
	
	/**
	 * Retrieve the path where migration files are stored.
	 *
	 * This method determines the migrations directory path based on defined constants
	 * or WordPress functions. It prioritizes the `WP_MIGRATIONS_PATH` constant if defined,
	 * followed by the theme's stylesheet directory, and finally defaults to a directory
	 * within the WordPress content directory.
	 *
	 * @return string The resolved migrations directory path.
	 */
	protected function getMigrationsPath(): string {
		
		if ( defined('WP_MIGRATIONS_PATH') ) {
			return rtrim(WP_MIGRATIONS_PATH, '/');
		}
		
		if ( function_exists('get_stylesheet_directory') ) {
			return get_stylesheet_directory() . '/migrations';
		}
		
		return WP_CONTENT_DIR . '/migrations';
	}
	
	
	/**
	 * Retrieves the file system path for migration stubs.
	 *
	 * This method determines the stub path by checking if a project-specific
	 * override is defined using the `WP_MIGRATIONS_STUB_PATH` constant. If not
	 * defined, it falls back to the default package stub path.
	 *
	 * @return string The resolved stub path.
	 */
	protected function getStubPath(): string {
		// Project override
		if ( defined('WP_MIGRATIONS_STUB_PATH') ) {
			return rtrim(WP_MIGRATIONS_STUB_PATH, '/');
		}
		
		// Default package stub
		return dirname(__DIR__, 2) . '/stubs';
	}
	
	
	/**
	 * Generates a file name for a new migration file.
	 *
	 * The file name is constructed using the current timestamp, the sanitized
	 * version of the provided name, and the target path.
	 *
	 * @param string $name The name of the migration, which will be sanitized
	 *                     and included in the file name.
	 * @param string $path The directory path where the file will be created.
	 *
	 * @return string The fully qualified file name, including the path,
	 *                timestamp, and sanitized name.
	 */
	protected function generateFileName( string $name, string $path ): string {
		$timestamp = date('Y_m_d_His');
		$slug = strtolower(
			preg_replace('/[^a-z0-9_]+/i', '_', $name)
		);
		
		return "{$path}/{$timestamp}_{$slug}.php";
	}
	
	
	/**
	 * Guesses the table name based on the provided migration name.
	 *
	 * @param string $name The name of the migration, typically using a convention like "create_users_table".
	 *
	 * @return string The derived table name, such as "users" or "orders". Defaults to "table_name" if the name cannot be processed.
	 */
	protected function guessTableName( string $name ): string {
		$name = strtolower($name);
		$name = preg_replace('/^create_/', '', $name);
		$name = preg_replace('/_table$/', '', $name);
		
		return $name ? : 'table_name';
	}
	
	
	/**
	 * Retrieves the migration stub content and replaces placeholders with dynamic values.
	 *
	 * @param string $name The name of the migration, used to guess the table name.
	 *
	 * @return string The processed content of the migration stub with placeholders replaced.
	 * @throws RuntimeException If the migration stub file does not exist.
	 */
	protected function getStub( string $name ): string {
		$table = $this->guessTableName($name);
		
		$stubFile = $this->getStubPath() . '/migration.php.stub';
		if ( !file_exists($stubFile) ) {
			throw new RuntimeException('Migration stub not found.');
		}
		$stub = file_get_contents($stubFile);
		
		return str_replace(
			[ '{{ table }}' ],
			[ $table ],
			$stub
		);
	}
	
}
