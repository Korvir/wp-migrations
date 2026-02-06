<?php

namespace WPMigrations;

/**
 * Handles database operations related to migration tracking.
 */
class MigrationRepository {
	protected $wpdb;
	protected string $table;
	
	public function __construct( $wpdb, string $table ) {
		$this->wpdb = $wpdb;
		$this->table = $table;
	}
	
	
	
	/**
	 * Ensures that the database table exists by creating it if it does not already exist.
	 * The table contains columns for ID, migration name, batch number, and execution timestamp.
	 *
	 * @return void
	 */
	public function ensureTable(): void {
		$this->wpdb->query("
			CREATE TABLE IF NOT EXISTS {$this->table} (
				id INT AUTO_INCREMENT,
				migration VARCHAR(255) NOT NULL,
				batch INT NOT NULL,
				executed_at DATETIME NOT NULL,
				PRIMARY KEY(id)
			)
		");
	}
	
	
	/**
	 * Retrieves the next batch number by finding the maximum batch value in the database
	 * and incrementing it by one. If no batches exist, it returns 1 as the initial batch number.
	 *
	 * @return int The next batch number to be used.
	 */
	public function nextBatch(): int {
		$max = $this->wpdb->get_var(
			"SELECT MAX(batch) FROM {$this->table}"
		);
		
		return $max ? $max + 1 : 1;
	}
	
	
	/**
	 * Retrieves the highest batch number from the database.
	 * If no batches exist, it returns null.
	 *
	 * @return int|null The maximum batch number or null if no records are found.
	 */
	public function lastBatch(): ?int {
		return $this->wpdb->get_var(
			"SELECT MAX(batch) FROM {$this->table}"
		);
	}
	
	
	/**
	 * Retrieves the most recent migrations from the database, ordered by their ID in descending order.
	 *
	 * @param int $limit The maximum number of migrations to retrieve.
	 *
	 * @return array An array of migration names, ordered from the most recent to the oldest.
	 */
	public function lastMigrations( int $limit): array
	{
		return $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT migration
				 FROM {$this->table}
				 ORDER BY id DESC
				 LIMIT %d",
				$limit
			)
		);
	}
	
	
	/**
	 * Retrieves a list of migrations corresponding to a specific batch number.
	 * The migrations are ordered in descending order of their IDs.
	 *
	 * @param int $batch The batch number for which migrations should be retrieved.
	 *
	 * @return array An array of migration names associated with the specified batch.
	 */
	public function getMigrationsByBatch( int $batch ): array {
		return $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT migration
				 FROM {$this->table}
				 WHERE batch = %d
				 ORDER BY id DESC",
				$batch
			)
		);
	}
	
	
	/**
	 * Checks if a specific migration exists in the database by counting
	 * the number of entries that match the provided migration name.
	 *
	 * @param string $migration The name of the migration to check for.
	 *
	 * @return bool True if the migration exists, otherwise false.
	 */
	public function has( string $migration ): bool {
		return (bool)$this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE migration = %s",
				$migration
			)
		);
	}
	
	
	/**
	 * Logs a migration entry into the database, recording its name, corresponding batch number,
	 * and the execution timestamp.
	 *
	 * @param string $migration The name of the migration being logged.
	 * @param int    $batch     The batch number associated with the migration.
	 *
	 * @return void
	 */
	public function log( string $migration, int $batch ): void {
		$this->wpdb->insert(
			$this->table,
			[
				'migration'   => $migration,
				'batch'       => $batch,
				'executed_at' => current_time('mysql'),
			]
		);
	}
	
	
	/**
	 * Deletes a migration record from the database based on the provided migration name.
	 *
	 * @param string $migration The name of the migration to be deleted.
	 *
	 * @return void
	 */
	public function delete( string $migration ): void {
		$this->wpdb->delete(
			$this->table,
			[ 'migration' => $migration ]
		);
	}
	
	
	/**
	 * Retrieves all records from the database table, including the migration and batch columns,
	 * ordered by the ID in ascending order.
	 *
	 * @return array An array of results where each result is represented as an associative array.
	 */
	public function all(): array {
		return $this->wpdb->get_results(
			"SELECT migration, batch
			 FROM {$this->table}
			 ORDER BY id ASC",
			ARRAY_A
		);
	}
}
