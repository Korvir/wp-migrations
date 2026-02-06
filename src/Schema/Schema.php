<?php

namespace WPMigrations\Schema;

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

