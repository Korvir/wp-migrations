<?php

namespace WPMigrations\Migrations;

use Exception;

class MigrationRunner {
	protected $wpdb;
	protected string $path;
	protected MigrationRepository $repository;
	
	public function __construct( array $config = [] ) {
		global $wpdb;
		
		$this->wpdb = $wpdb;
		$this->path = $this->resolvePath($config);
		$table = $config['table'] ?? $wpdb->prefix . 'migrations';
		
		$this->repository = new MigrationRepository($wpdb, $table);
	}
	
	
	/**
	 * Retrieves a list of files that are pending processing. If a target name is provided,
	 * only the file with the corresponding name is checked and included in the result.
	 *
	 * @param string|null $target  An optional specific file name to filter the pending files.
	 *                             If null, all files are considered.
	 *
	 * @return array An associative array of pending files, with the file names as keys
	 *               and the file data as values.
	 */
	public function pending( ?string $target = null ): array {
		$this->repository->ensureTable();
		
		$pending = [];
		foreach ( $this->getFiles() as $name => $file ) {
			if ( $target && $target !== $name ) {
				continue;
			}
			if ( $this->repository->has($name) ) {
				continue;
			}
			$pending[ $name ] = $file;
		}
		
		return $pending;
	}
	
	
	/**
	 * Executes pending database migrations.
	 *
	 * This method processes and applies all pending migrations up to a specified
	 * target, or all pending migrations if no target is provided.
	 *
	 * @param string|null $target  Specifies up to which migration the method should run.
	 *                             If null, all pending migrations will be executed.
	 *
	 * @return int The number of migrations successfully executed.
	 *
	 * @throws Exception If a migration does not implement MigrationInterface
	 *                   or if an error occurs during the execution.
	 */
	public function migrate( ?string $target = null ): int {
		global $wpdb;
		
		$wpdb->hide_errors();
		$this->repository->ensureTable();
		
		$pending = $this->pending($target);
		if ( empty($pending) ) {
			return 0;
		}
		
		$batch = $this->repository->nextBatch();
		
		$executed = 0;
		foreach ( $pending as $name => $file ) {
			$migration = require $file;
			if ( !$migration instanceof MigrationInterface ) {
				throw new Exception("$name must implement MigrationInterface");
			}
			
			$migration->up();
			if ( $wpdb->last_error ) {
				throw new Exception(
					"Migration failed: {$name}\n{$wpdb->last_error}"
				);
			}
			$this->repository->log($name, $batch);
			$executed++;
		}
		
		return $executed;
	}
	
	
	/**
	 * Rolls back the last batch of executed database migrations.
	 *
	 * This method identifies the last batch of migrations that were executed,
	 * processes them in reverse order, and rolls them back by invoking the
	 * corresponding `down()` method for each migration. Migration records are
	 * subsequently removed from the repository.
	 *
	 * @return int The number of migrations successfully rolled back.
	 *
	 * @throws Exception If a required migration file is missing.
	 */
	public function rollback(): int {
		$this->repository->ensureTable();
		
		$batch = $this->repository->lastBatch();
		if ( !$batch ) {
			return 0;
		}
		
		$migrations = $this->repository->getMigrationsByBatch($batch);
		$files = $this->getFiles();
		
		$rolledBack = 0;
		foreach ( $migrations as $name ) {
			
			if ( !isset($files[ $name ]) ) {
				throw new Exception("Migration file missing: {$name}");
			}
			
			$migration = require $files[ $name ];
			$migration->down();
			$this->repository->delete($name);
			
			$rolledBack++;
		}
		
		return $rolledBack;
	}
	
	
	/**
	 * Rolls back a specific number of database migrations.
	 *
	 * This method reverts the most recent migrations in reverse order, based on
	 * the specified number of steps. Each migration's `down` method is executed
	 * to undo its changes.
	 *
	 * @param int $steps The number of migrations to roll back. Must be greater than 0.
	 *
	 * @return int The number of migrations successfully rolled back.
	 *
	 * @throws Exception If a migration file is missing or an error occurs during execution.
	 */
	public function rollbackSteps( int $steps ): int {
		
		$this->repository->ensureTable();
		
		if ( $steps <= 0 ) {
			return 0;
		}
		
		$migrations = $this->repository->lastMigrations($steps);
		if ( empty($migrations) ) {
			return 0;
		}
		
		$files = $this->getFiles();
		
		$rolledBack = 0;
		foreach ( $migrations as $name ) {
			if ( !isset($files[ $name ]) ) {
				throw new Exception("Migration file missing: {$name}");
			}
			
			$migration = require $files[ $name ];
			$migration->down();
			$this->repository->delete($name);
			
			$rolledBack++;
		}
		
		return $rolledBack;
	}
	
	
	/**
	 * Resets the database by rolling back all applied migrations.
	 *
	 * This method iteratively rolls back all batches of migrations,
	 * resetting the database to its initial state.
	 *
	 * @return int The total number of migrations rolled back.
	 *
	 * @throws Exception If an error occurs during the rollback process.
	 */
	public function reset(): int {
		$this->repository->ensureTable();
		
		$total = 0;
		while ( $this->repository->lastBatch() ) {
			$total += $this->rollback();
		}
		
		return $total;
	}
	
	
	/**
	 * Resets and retrieves the list of executed migrations in reverse order.
	 *
	 * This method retrieves all executed migrations from the repository, reverses their execution
	 * order, and constructs a list containing details about each migration, including its name,
	 * batch number, and associated file (if available).
	 *
	 * @return array An array of executed migrations, where each entry contains:
	 *               - 'migration': The name of the migration.
	 *               - 'batch': The batch number in which the migration was executed.
	 *               - 'file': The file path of the migration, or null if it is not available.
	 */
	public function resetList(): array {
		$this->repository->ensureTable();
		
		$files = $this->getFiles();
		$executed = $this->repository->all();
		
		$list = [];
		
		// Reverse execution order
		for ( $i = count($executed) - 1; $i >= 0; $i-- ) {
			$name = $executed[ $i ]['migration'];
			$list[] = [
				'migration' => $name,
				'batch'     => $executed[ $i ]['batch'],
				'file'      => $files[ $name ] ?? null,
			];
		}
		
		return $list;
	}
	
	
	/* -------------------------------- */
	
	/**
	 * Generates a list of migrations from the last executed batch available for rollback.
	 *
	 * This method identifies the migrations executed in the most
	 * recent batch, retrieves their associated file paths, and organizes
	 * them into an array for rollback purposes.
	 *
	 * @return array An associative array where keys are migration names and values
	 *               are arrays containing the following details:
	 *               - 'file': The file path of the migration, or null if not found.
	 *               - 'batch': The batch number of the migration.
	 */
	public function rollbackList(): array {
		
		$this->repository->ensureTable();
		
		$batch = $this->repository->lastBatch();
		if ( !$batch ) {
			return [];
		}
		
		$migrations = $this->repository->getMigrationsByBatch($batch);
		$files = $this->getFiles();
		
		$list = [];
		foreach ( $migrations as $name ) {
			$list[ $name ] = [
				'file'  => $files[ $name ] ?? null,
				'batch' => $batch,
			];
		}
		
		return $list;
	}
	
	
	/**
	 * Retrieves a list of migrations from the most recent steps and associates them with their corresponding file paths, if available.
	 *
	 * @param int $steps The number of recent migrations to process. If the value is less than or equal to 0, an empty array is returned.
	 *
	 * @return array An associative array where the keys are migration names and the values are file paths or null if the file path is not found.
	 */
	public function rollbackStepList( int $steps ): array {
		
		$this->repository->ensureTable();
		
		if ( $steps <= 0 ) {
			return [];
		}
		
		$migrations = $this->repository->lastMigrations($steps);
		$files = $this->getFiles();
		$list = [];
		foreach ( $migrations as $name ) {
			$list[ $name ] = $files[ $name ] ?? null;
		}
		
		return $list;
	}
	
	
	/**
	 * @return array Returns an array of all entries from the repository after ensuring the table exists.
	 */
	public function executed(): array {
		$this->repository->ensureTable();
		return $this->repository->all();
	}
	
	
	/**
	 * Retrieves the status of migrations by combining executed and pending migrations.
	 *
	 * @return array Returns an associative array where each key is the migration name
	 *               and the value is an array containing the batch number and status
	 *               ('Complete' for executed migrations or 'Pending' for migrations
	 *               that have not yet been executed), sorted by migration name.
	 */
	public function status(): array {
		$this->repository->ensureTable();
		$files = $this->getFiles();
		$executed = $this->repository->all();
		
		$map = [];
		
		// Executed
		foreach ( $executed as $row ) {
			$map[ $row['migration'] ] = [
				'batch'  => $row['batch'],
				'status' => 'Complete',
			];
		}
		
		// Pending
		foreach ( $files as $name => $file ) {
			if ( !isset($map[ $name ]) ) {
				$map[ $name ] = [
					'batch'  => null,
					'status' => 'Pending',
				];
			}
		}
		
		ksort($map);
		
		return $map;
	}
	
	
	/**
	 * Retrieves an array of PHP files from a specified directory, using the path property.
	 *
	 * The method checks if the directory exists. If not, it returns an empty array.
	 * It then searches for files with a `.php` extension, sorts them, and maps them
	 * to an associative array where the key is the file name without the extension,
	 * and the value is the full file path.
	 *
	 * @return array An associative array of PHP file names (without extensions) as keys
	 * and their full paths as values. Returns an empty array if the directory does not exist
	 * or contains no `.php` files.
	 */
	protected function getFiles(): array {
		if ( !is_dir($this->path) ) {
			return [];
		}
		
		$files = glob($this->path . '/*.php');
		sort($files);
		
		$out = [];
		foreach ( $files as $file ) {
			$name = basename($file, '.php');
			$out[ $name ] = $file;
		}
		
		return $out;
	}
	
	
	/**
	 * Resolves and returns the base path for migrations based on the provided configuration or default values.
	 *
	 * The method prioritizes the following sources for determining the path:
	 * 1. The `path` value is specified in the `$config` array (if not empty).
	 * 2. The `WP_MIGRATIONS_PATH` constant (if defined).
	 * 3. The output of the ` get_stylesheet_directory ` function (if it exists), with `/migrations` appended.
	 * 4. The `WP_CONTENT_DIR` constant with `/migrations` appended, as a fallback.
	 *
	 * Optionally trims trailing slashes from paths before returning.
	 *
	 * @param array $config An associative array that may contain a 'path' key specifying a custom migrations path.
	 *
	 * @return string The resolved migrations path, based on the priority described.
	 */
	protected function resolvePath( array $config ): string {
		
		if ( !empty($config['path']) ) {
			return rtrim($config['path'], '/');
		}
		
		if ( defined('WP_MIGRATIONS_PATH') ) {
			return rtrim(WP_MIGRATIONS_PATH, '/');
		}
		
		if ( function_exists('get_stylesheet_directory') ) {
			return get_stylesheet_directory() . '/migrations';
		}
		
		return WP_CONTENT_DIR . '/migrations';
	}
	
}
