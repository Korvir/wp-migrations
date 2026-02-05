<?php

namespace WPMigrations;

class MigrationRepository
{
	protected $wpdb;
	protected string $table;
	
	public function __construct($wpdb, string $table)
	{
		$this->wpdb  = $wpdb;
		$this->table = $table;
	}
	
	/* -------------------------------- */
	
	public function ensureTable(): void
	{
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
	
	/* -------------------------------- */
	
	public function nextBatch(): int
	{
		$max = $this->wpdb->get_var(
			"SELECT MAX(batch) FROM {$this->table}"
		);
		
		return $max ? $max + 1 : 1;
	}
	
	/* -------------------------------- */
	
	public function lastBatch(): ?int
	{
		return $this->wpdb->get_var(
			"SELECT MAX(batch) FROM {$this->table}"
		);
	}
	
	/* -------------------------------- */
	
	public function getMigrationsByBatch(int $batch): array
	{
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
	
	/* -------------------------------- */
	
	public function has(string $migration): bool
	{
		return (bool) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE migration = %s",
				$migration
			)
		);
	}
	
	/* -------------------------------- */
	
	public function log(string $migration, int $batch): void
	{
		$this->wpdb->insert(
			$this->table,
			[
				'migration'   => $migration,
				'batch'       => $batch,
				'executed_at' => current_time('mysql'),
			]
		);
	}
	
	/* -------------------------------- */
	
	public function delete(string $migration): void
	{
		$this->wpdb->delete(
			$this->table,
			['migration' => $migration]
		);
	}
	
	/* -------------------------------- */
	
	public function all(): array
	{
		return $this->wpdb->get_results(
			"SELECT migration, batch
			 FROM {$this->table}
			 ORDER BY id ASC",
			ARRAY_A
		);
	}
}
