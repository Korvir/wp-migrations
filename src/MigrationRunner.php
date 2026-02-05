<?php

namespace WPMigrations;

class MigrationRunner
{
	protected $wpdb;
	protected string $path;
	protected MigrationRepository $repo;
	
	public function __construct(array $config = [])
	{
		global $wpdb;
		
		$this->wpdb = $wpdb;
		
		$this->path = $this->resolvePath($config);
		$table      = $config['table'] ?? $wpdb->prefix . 'migrations';
		
		$this->repo = new MigrationRepository($wpdb, $table);
	}
	
	
	public function pending(?string $target = null): array
	{
		$this->repo->ensureTable();
		
		$pending = [];
		
		foreach ($this->getFiles() as $name => $file) {
			
			if ($target && $target !== $name) {
				continue;
			}
			
			if ($this->repo->has($name)) {
				continue;
			}
			
			$pending[$name] = $file;
		}
		
		return $pending;
	}
	
	/* -------------------------------- */
	
	public function migrate(?string $target = null): int
	{
		global $wpdb;
		
		$wpdb->hide_errors();
		
		$this->repo->ensureTable();
		
		$pending = $this->pending($target);
		
		if (empty($pending)) {
			return 0;
		}
		
		$batch = $this->repo->nextBatch();
		
		$executed = 0;
		
		foreach ($pending as $name => $file) {
			$migration = require $file;
			if (! $migration instanceof MigrationInterface) {
				throw new \Exception("$name must implement MigrationInterface");
			}
			
			$migration->up();
			if ($wpdb->last_error) {
				throw new \Exception(
					"Migration failed: {$name}\n{$wpdb->last_error}"
				);
			}
			$this->repo->log($name, $batch);
			
			$executed++;
		}
		
		return $executed;
	}

	
	/* -------------------------------- */
	
	public function rollback(): int
	{
		$this->repo->ensureTable();
		$batch = $this->repo->lastBatch();
		
		if (! $batch) {
			return 0;
		}
		
		$migrations = $this->repo->getMigrationsByBatch($batch);
		$files = $this->getFiles();
		
		$rolledBack = 0;
		foreach ($migrations as $name) {
			
			if (! isset($files[$name])) {
				throw new \Exception("Migration file missing: {$name}");
			}
			
			$migration = require $files[$name];
			$migration->down();
			$this->repo->delete($name);

			$rolledBack++;
		}
		
		return $rolledBack;
	}
	
	/* -------------------------------- */
	
	public function rollbackList(): array
	{
		$this->repo->ensureTable();
		$batch = $this->repo->lastBatch();
		
		if (! $batch) {
			return [];
		}
		
		$migrations = $this->repo->getMigrationsByBatch($batch);
		$files = $this->getFiles();
		
		$list = [];
		foreach ($migrations as $name) {
			$list[$name] = [
				'file'  => $files[$name] ?? null,
				'batch' => $batch,
			];
		}
		
		return $list;
	}
	
	
	/* -------------------------------- */
	
	public function executed(): array
	{
		$this->repo->ensureTable();
		
		return $this->repo->all();
	}
	
	/* -------------------------------- */
	
	public function status(): array
	{
		$this->repo->ensureTable();
		$files = $this->getFiles();
		$executed = $this->repo->all();
		
		$map = [];
		
		// Executed
		foreach ($executed as $row) {
			$map[$row['migration']] = [
				'batch'  => $row['batch'],
				'status' => 'Complete',
			];
		}
		
		// Pending
		foreach ($files as $name => $file) {
			if (! isset($map[$name])) {
				$map[$name] = [
					'batch'  => null,
					'status' => 'Pending',
				];
			}
		}
		
		ksort($map);
		
		return $map;
	}
	
	/* -------------------------------- */
	
	protected function getFiles(): array
	{
		if (! is_dir($this->path)) {
			return [];
		}
		
		$files = glob($this->path . '/*.php');
		
		sort($files);
		
		$out = [];
		
		foreach ($files as $file) {
			$name = basename($file, '.php');
			$out[$name] = $file;
		}
		
		return $out;
	}
	
	
	protected function resolvePath(array $config): string
	{
		if (! empty($config['path'])) {
			return rtrim($config['path'], '/');
		}
		
		if (defined('WP_MIGRATIONS_PATH')) {
			return rtrim(WP_MIGRATIONS_PATH, '/');
		}
		
		if (function_exists('get_stylesheet_directory')) {
			return get_stylesheet_directory() . '/migrations';
		}
		
		return WP_CONTENT_DIR . '/migrations';
	}
}
