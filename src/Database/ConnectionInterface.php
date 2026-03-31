<?php

namespace WPMigrations\Database;

interface ConnectionInterface {
	public function statement( string $query ): void;

	public function scalar( string $query );

	public function prepare( string $query, ...$args ): string;

	public function tablePrefix(): string;

	public function charsetCollation(): string;

	public function lastError(): string;

	public function hideErrors(): void;
}
