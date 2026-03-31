<?php

namespace WPMigrations\Database;

use wpdb;

final class WPDBConnection implements ConnectionInterface {
	private wpdb $wpdb;

	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	public function statement( string $query ): void {
		$this->wpdb->query($query);
	}

	public function scalar( string $query ) {
		return $this->wpdb->get_var($query);
	}

	public function column( string $query ): array {
		return $this->wpdb->get_col($query);
	}

	public function rows( string $query ): array {
		return $this->wpdb->get_results($query, ARRAY_A);
	}

	public function prepare( string $query, ...$args ): string {
		return $this->wpdb->prepare($query, ...$args);
	}

	public function insert( string $table, array $data ): void {
		$this->wpdb->insert($table, $data);
	}

	public function delete( string $table, array $where ): void {
		$this->wpdb->delete($table, $where);
	}

	public function tablePrefix(): string {
		return $this->wpdb->prefix;
	}

	public function charsetCollation(): string {
		return $this->wpdb->get_charset_collate();
	}

	public function currentTimestamp(): string {
		if ( function_exists('current_time') ) {
			return current_time('mysql');
		}

		return gmdate('Y-m-d H:i:s');
	}

	public function lastError(): string {
		return (string)$this->wpdb->last_error;
	}

	public function hideErrors(): void {
		$this->wpdb->hide_errors();
	}

	public function wpdb(): wpdb {
		return $this->wpdb;
	}
}
