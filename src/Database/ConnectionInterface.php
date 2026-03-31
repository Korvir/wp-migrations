<?php

namespace WPMigrations\Database;

interface ConnectionInterface {
	public function statement( string $query ): void;

	public function scalar( string $query );

	public function column( string $query ): array;

	public function rows( string $query ): array;

	public function prepare( string $query, ...$args ): string;

	public function insert( string $table, array $data ): void;

	public function delete( string $table, array $where ): void;

	public function tablePrefix(): string;

	public function charsetCollation(): string;

	public function currentTimestamp(): string;

	public function lastError(): string;

	public function hideErrors(): void;
}
