<?php

namespace WPMigrations\Schema;

use InvalidArgumentException;
use wpdb;
use WPMigrations\Sql\SqlCompiler;

final class Schema {
	
	protected static ?wpdb $db = null;
	
	public static function setConnection( wpdb $db ): void {
		self::$db = $db;
	}
	
	public static function create( string $table, callable $callback ): void {
		$blueprint = new Blueprint($table, Blueprint::MODE_CREATE);
		$callback($blueprint);
		
		$sql = ( new SqlCompiler() )->compile($blueprint);
		self::execute($sql);
	}
	
	public static function table( string $table, callable $callback ): void {
		$blueprint = new Blueprint($table, Blueprint::MODE_ALTER);
		$callback($blueprint);
		
		$sql = ( new SqlCompiler() )->compile($blueprint);
		self::execute($sql);
	}
	
	public static function raw( $queries ): void {
		if ( is_string($queries) ) {
			$queries = [ $queries ];
		}
		
		if ( !is_array($queries) ) {
			throw new InvalidArgumentException(
				'Schema::raw() expects string or array of strings.'
			);
		}
		
		self::execute($queries);
	}
	
	
	public static function rename( string $from, string $to ): void {
		global $wpdb;
		$from = $wpdb->prefix . $from;
		$to = $wpdb->prefix . $to;
		
		$wpdb->query("RENAME TABLE {$from} TO {$to}");
	}
	
	public static function drop( string $table ): void {
		global $wpdb;
		$table = $wpdb->prefix . $table;
		
		$wpdb->query("DROP TABLE {$table}");
	}
	
	public static function dropIfExists( string $table ): void {
		global $wpdb;
		$table = $wpdb->prefix . $table;
		
		$wpdb->query("DROP TABLE IF EXISTS {$table}");
	}
	
	public static function hasTable( string $table ): bool {
		global $wpdb;
		$prefixed = $wpdb->prefix . $table;
		
		$sql = $wpdb->prepare(
			"SHOW TABLES LIKE %s",
			$prefixed
		);
		
		return (bool)$wpdb->get_var($sql);
	}
	
	public static function hasColumn( string $table, string $column ): bool {
		global $wpdb;
		$prefixed = $wpdb->prefix . $table;
		
		$sql = $wpdb->prepare(
			"SHOW COLUMNS FROM {$prefixed} LIKE %s",
			$column
		);
		
		return (bool)$wpdb->get_var($sql);
	}
	
	public static function hasIndex( string $table, string $index ): bool {
		global $wpdb;
		$prefixed = $wpdb->prefix . $table;
		
		$sql = $wpdb->prepare(
			"SHOW INDEX FROM {$prefixed} WHERE Key_name = %s",
			$index
		);
		
		return (bool)$wpdb->get_var($sql);
	}
	
	public static function createView( string $name, string $select ): void {
		global $wpdb;
		$view = $wpdb->prefix . $name;
		
		self::execute([
			"CREATE VIEW {$view} AS {$select}",
		]);
	}
	
	public static function dropView( string $name ): void {
		global $wpdb;
		$view = $wpdb->prefix . $name;
		
		self::execute([
			"DROP VIEW {$view}",
		]);
	}
	
	public static function createOrReplaceView( string $name, string $select ): void {
		global $wpdb;
		$view = $wpdb->prefix . $name;
		
		self::execute([
			"CREATE OR REPLACE VIEW {$view} AS {$select}",
		]);
	}
	
	
	protected static function execute( array $queries ): void {
		if ( !self::$db ) {
			global $wpdb;
			self::$db = $wpdb;
		}
		
		foreach ( $queries as $query ) {
			self::$db->query($query);
		}
	}
}

