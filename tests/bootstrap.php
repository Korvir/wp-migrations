<?php

if ( !class_exists('wpdb') ) {
	class wpdb {
		public string $prefix = 'wp_';
		public string $last_error = '';

		public function get_charset_collate(): string {
			return 'DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
		}
	}
}

require dirname(__DIR__) . '/vendor/autoload.php';
