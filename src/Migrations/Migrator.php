<?php

namespace WPMigrations\Migrations;

use Exception;
use WPMigrations\Database\ConnectionInterface;

final class Migrator {
	private MigrationRepository $repository;
	private MigrationFinder $finder;
	private ConnectionInterface $connection;
	private bool $strict;

	public function __construct( MigrationRepository $repository, MigrationFinder $finder, ConnectionInterface $connection, bool $strict = true ) {
		$this->repository = $repository;
		$this->finder = $finder;
		$this->connection = $connection;
		$this->strict = $strict;
	}

	public function migrate( ?string $target = null, ?array $only = null, ?array $except = null, ?int $step = null ): int {
		$this->connection->hideErrors();
		$this->repository->ensureTable();

		$pending = $this->finder->pending($target, $only, $except, $step);
		if ( empty($pending) ) {
			return 0;
		}

		$batch = $this->repository->nextBatch();
		$executed = 0;

		foreach ( $pending as $name => $file ) {
			$migration = require $file;
			$this->assertMigrationContract($migration, $name);

			$migration->up();
			if ( $this->connection->lastError() !== '' ) {
				throw new Exception("Migration failed: {$name}\n" . $this->connection->lastError());
			}

			$this->repository->log($name, $batch);
			$executed++;
		}

		return $executed;
	}

	public function rollback(): int {
		$this->repository->ensureTable();

		$batch = $this->repository->lastBatch();
		if ( !$batch ) {
			return 0;
		}

		$migrations = $this->repository->getMigrationsByBatch($batch);
		$files = $this->finder->allFiles();
		$rolledBack = 0;

		foreach ( $migrations as $name ) {
			if ( !isset($files[ $name ]) ) {
				throw new Exception("Migration file missing: {$name}");
			}

			$migration = require $files[ $name ];
			$this->assertMigrationContract($migration, $name);
			$migration->down();

			if ( $this->connection->lastError() !== '' ) {
				throw new Exception("Rollback failed: {$name}\n" . $this->connection->lastError());
			}

			$this->repository->delete($name);
			$rolledBack++;
		}

		return $rolledBack;
	}

	public function rollbackSteps( int $steps ): int {
		$this->repository->ensureTable();

		if ( $steps <= 0 ) {
			return 0;
		}

		$migrations = $this->repository->lastMigrations($steps);
		if ( empty($migrations) ) {
			return 0;
		}

		$files = $this->finder->allFiles();
		$rolledBack = 0;
		foreach ( $migrations as $name ) {
			if ( !isset($files[ $name ]) ) {
				throw new Exception("Migration file missing: {$name}");
			}

			$migration = require $files[ $name ];
			$this->assertMigrationContract($migration, $name);
			$migration->down();

			if ( $this->connection->lastError() !== '' ) {
				throw new Exception("Rollback failed: {$name}\n" . $this->connection->lastError());
			}

			$this->repository->delete($name);
			$rolledBack++;
		}

		return $rolledBack;
	}

	public function reset(): int {
		$this->repository->ensureTable();

		$total = 0;
		while ( $this->repository->lastBatch() ) {
			$total += $this->rollback();
		}

		return $total;
	}

	public function resetList(): array {
		$this->repository->ensureTable();

		$files = $this->finder->allFiles();
		$executed = $this->repository->all();
		$list = [];

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

	public function rollbackList(): array {
		$this->repository->ensureTable();

		$batch = $this->repository->lastBatch();
		if ( !$batch ) {
			return [];
		}

		$migrations = $this->repository->getMigrationsByBatch($batch);
		$files = $this->finder->allFiles();
		$list = [];
		foreach ( $migrations as $name ) {
			$list[ $name ] = [
				'file'  => $files[ $name ] ?? null,
				'batch' => $batch,
			];
		}

		return $list;
	}

	public function rollbackStepList( int $steps ): array {
		$this->repository->ensureTable();

		if ( $steps <= 0 ) {
			return [];
		}

		$migrations = $this->repository->lastMigrations($steps);
		$files = $this->finder->allFiles();
		$list = [];
		foreach ( $migrations as $name ) {
			$list[ $name ] = $files[ $name ] ?? null;
		}

		return $list;
	}

	public function executed(): array {
		$this->repository->ensureTable();
		return $this->repository->all();
	}

	public function status(): array {
		$this->repository->ensureTable();
		$files = $this->finder->allFiles();
		$executed = $this->repository->all();
		$map = [];

		foreach ( $executed as $row ) {
			$map[ $row['migration'] ] = [
				'batch'  => $row['batch'],
				'status' => 'Complete',
			];
		}

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

	private function assertMigrationContract( $migration, string $name ): void {
		if ( $migration instanceof MigrationInterface ) {
			return;
		}

		if ( !$this->strict && is_object($migration) && method_exists($migration, 'up') && method_exists($migration, 'down') ) {
			return;
		}

		throw new Exception("{$name} must implement MigrationInterface");
	}
}
