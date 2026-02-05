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
		
		$this->path = $config['path'] ?? WP_CONTENT_DIR . '/migrations';
		$table      = $config['table'] ?? 'wp_migrations';
		
		$this->repo = new MigrationRepository($wpdb, $table);
	}
	
	/* -------------------------------- */
	
	public function migrate(?string $target = null): void
	{
		$this->repo->ensureTable();
		
		foreach ($this->getFiles() as $name => $file) {
			
			if ($target && $target !== $name) {
				continue;
			}
			
			if ($this->repo->has($name)) {
				continue;
			}
			
			$migration = require $file;
			
			if (! $migration instanceof MigrationInterface) {
				throw new \Exception("$name must implement MigrationInterface");
			}
			
			$migration->up();
			
			$this->repo->log($name);
		}
	}
	
	/* -------------------------------- */
	
	public function rollback(?string $target = null): void
	{
		$files = array_reverse($this->getFiles());
		
		foreach ($files as $name => $file) {
			
			if ($target && $target !== $name) {
				continue;
			}
			
			if (! $this->repo->has($name)) {
				continue;
			}
			
			$migration = require $file;
			
			$migration->down();
			
			$this->repo->delete($name);
		}
	}
	
	/* -------------------------------- */
	
	protected function getFiles(): array
	{
		$files = glob($this->path . '/*.php');
		
		sort($files);
		
		$out = [];
		
		foreach ($files as $file) {
			$name = basename($file, '.php');
			$out[$name] = $file;
		}
		
		return $out;
	}
}
