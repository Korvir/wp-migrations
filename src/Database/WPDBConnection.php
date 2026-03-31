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

	public function prepare( string $query, ...$args ): string {
		return $this->wpdb->prepare($query, ...$args);
	}

	public function tablePrefix(): string {
		return $this->wpdb->prefix;
	}

	public function charsetCollation(): string {
		return $this->wpdb->get_charset_collate();
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
