<?php

namespace WPMigrations\Migrations;

final class MigrationFinder {
	private string $path;
	private MigrationRepository $repository;

	public function __construct( string $path, MigrationRepository $repository ) {
		$this->path = rtrim($path, '/');
		$this->repository = $repository;
	}

	public function pending( ?string $target = null, ?array $only = null, ?array $except = null, ?int $limit = null ): array {
		$this->repository->ensureTable();

		$pending = [];
		foreach ( $this->getFiles() as $name => $file ) {
			if ( $target && $target !== $name ) {
				continue;
			}

			if ( $this->repository->has($name) ) {
				continue;
			}

			if ( $only ) {
				$matched = false;
				foreach ( $only as $token ) {
					if ( strpos($name, $token) !== false ) {
						$matched = true;
						break;
					}
				}
				if ( !$matched ) {
					continue;
				}
			}

			if ( $except ) {
				$excluded = false;
				foreach ( $except as $token ) {
					if ( strpos($name, $token) !== false ) {
						$excluded = true;
						break;
					}
				}
				if ( $excluded ) {
					continue;
				}
			}

			$pending[ $name ] = $file;
			if ( $limit !== null && count($pending) >= $limit ) {
				break;
			}
		}

		return $pending;
	}

	public function allFiles(): array {
		return $this->getFiles();
	}

	private function getFiles(): array {
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
}
