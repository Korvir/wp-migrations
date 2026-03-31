<?php

namespace WPMigrations\Migrations;

use WPMigrations\Database\ConnectionInterface;
use WPMigrations\Database\WPDBConnection;

class MigrationRunner {
	protected string $path;
	protected MigrationRepository $repository;
	protected MigrationFinder $finder;
	protected Migrator $migrator;

	public function __construct( array $config = [] ) {
		$connection = $this->resolveConnection($config);
		$this->path = $this->resolvePath($config);
		$table = $config['table'] ?? $connection->tablePrefix() . 'migrations';
		$strict = array_key_exists('strict', $config) ? (bool)$config['strict'] : true;

		$wpdb = $this->unwrapWpdb($connection);
		$this->repository = new MigrationRepository($wpdb, $table);
		$this->finder = new MigrationFinder($this->path, $this->repository);
		$this->migrator = new Migrator($this->repository, $this->finder, $connection, $strict);
	}

	public function install(): void {
		$this->repository->ensureTable();
	}

	public function pending( ?string $target = null, ?array $only = null, ?array $except = null, ?int $step = null ): array {
		return $this->finder->pending($target, $only, $except, $step);
	}

	public function migrate( ?string $target = null, ?array $only = null, ?array $except = null, ?int $step = null ): int {
		return $this->migrator->migrate($target, $only, $except, $step);
	}

	public function rollback(): int {
		return $this->migrator->rollback();
	}

	public function rollbackSteps( int $steps ): int {
		return $this->migrator->rollbackSteps($steps);
	}

	public function reset(): int {
		return $this->migrator->reset();
	}

	public function resetList(): array {
		return $this->migrator->resetList();
	}

	public function rollbackList(): array {
		return $this->migrator->rollbackList();
	}

	public function rollbackStepList( int $steps ): array {
		return $this->migrator->rollbackStepList($steps);
	}

	public function executed(): array {
		return $this->migrator->executed();
	}

	public function status(): array {
		return $this->migrator->status();
	}

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

	private function resolveConnection( array $config ): ConnectionInterface {
		if ( isset($config['connection']) && $config['connection'] instanceof ConnectionInterface ) {
			return $config['connection'];
		}

		global $wpdb;
		return new WPDBConnection($wpdb);
	}

	private function unwrapWpdb( ConnectionInterface $connection ) {
		if ( $connection instanceof WPDBConnection ) {
			return $connection->wpdb();
		}

		global $wpdb;
		return $wpdb;
	}
}
