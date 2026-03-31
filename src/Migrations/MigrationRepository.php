<?php

namespace WPMigrations\Migrations;

use WPMigrations\Database\ConnectionInterface;

/**
 * Handles database operations related to migration tracking.
 */
class MigrationRepository {
	protected ConnectionInterface $connection;
	protected string $table;

	public function __construct( ConnectionInterface $connection, string $table ) {
		$this->connection = $connection;
		$this->table = $table;
	}

	public function ensureTable(): void {
		$this->connection->statement(
			"CREATE TABLE IF NOT EXISTS {$this->table} (
				id INT AUTO_INCREMENT,
				migration VARCHAR(255) NOT NULL,
				batch INT NOT NULL,
				executed_at DATETIME NOT NULL,
				PRIMARY KEY(id)
			)"
		);
	}

	public function nextBatch(): int {
		$max = $this->connection->scalar("SELECT MAX(batch) FROM {$this->table}");
		return $max ? (int)$max + 1 : 1;
	}

	public function lastBatch(): ?int {
		$batch = $this->connection->scalar("SELECT MAX(batch) FROM {$this->table}");
		return $batch !== null ? (int)$batch : null;
	}

	public function lastMigrations( int $limit ): array {
		$query = $this->connection->prepare(
			"SELECT migration
			 FROM {$this->table}
			 ORDER BY id DESC
			 LIMIT %d",
			$limit
		);

		return $this->connection->column($query);
	}

	public function getMigrationsByBatch( int $batch ): array {
		$query = $this->connection->prepare(
			"SELECT migration
			 FROM {$this->table}
			 WHERE batch = %d
			 ORDER BY id DESC",
			$batch
		);

		return $this->connection->column($query);
	}

	public function has( string $migration ): bool {
		$query = $this->connection->prepare(
			"SELECT COUNT(*) FROM {$this->table} WHERE migration = %s",
			$migration
		);

		return (bool)$this->connection->scalar($query);
	}

	public function log( string $migration, int $batch ): void {
		$this->connection->insert(
			$this->table,
			[
				'migration'   => $migration,
				'batch'       => $batch,
				'executed_at' => $this->connection->currentTimestamp(),
			]
		);
	}

	public function delete( string $migration ): void {
		$this->connection->delete($this->table, [ 'migration' => $migration ]);
	}

	public function all(): array {
		return $this->connection->rows(
			"SELECT migration, batch
			 FROM {$this->table}
			 ORDER BY id ASC"
		);
	}
}
