<?php

namespace WPMigrations;

class MigrationRepository
{
	protected $wpdb;
	protected string $table;
	
	public function __construct($wpdb, string $table)
	{
		$this->wpdb  = $wpdb;
		$this->table = $wpdb->prefix . $table;
	}
	
	/* -------------------------------- */
	
	public function ensureTable(): void
	{
		$this->wpdb->query("
			CREATE TABLE IF NOT EXISTS {$this->table} (
				id INT AUTO_INCREMENT,
				migration VARCHAR(255) NOT NULL,
				executed_at DATETIME NOT NULL,
				PRIMARY KEY(id)
			)
		");
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
	
	public function log(string $migration): void
	{
		$this->wpdb->insert(
			$this->table,
			[
				'migration'   => $migration,
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
}
