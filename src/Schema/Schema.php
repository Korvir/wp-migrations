<?php

namespace WPMigrations\Schema;

use InvalidArgumentException;
use RuntimeException;
use wpdb;
use WPMigrations\Database\ConnectionInterface;
use WPMigrations\Database\Grammar\GrammarInterface;
use WPMigrations\Database\Grammar\MySqlGrammar;
use WPMigrations\Database\WPDBConnection;
use WPMigrations\Sql\SqlCompiler;

final class Schema {
	protected static ?ConnectionInterface $connection = null;
	protected static ?GrammarInterface $grammar = null;

	public static function setConnection( wpdb $db ): void {
		self::$connection = new WPDBConnection($db);
	}

	public static function setDatabaseConnection( ConnectionInterface $connection ): void {
		self::$connection = $connection;
	}

	public static function setGrammar( GrammarInterface $grammar ): void {
		self::$grammar = $grammar;
	}

	public static function create( string $table, callable $callback ): void {
		$blueprint = new Blueprint($table, Blueprint::MODE_CREATE);
		$callback($blueprint);

		$sql = ( new SqlCompiler(self::grammar(), self::connection()) )->compile($blueprint);
		self::execute($sql);
	}

	public static function table( string $table, callable $callback ): void {
		$blueprint = new Blueprint($table, Blueprint::MODE_ALTER);
		$callback($blueprint);

		$sql = ( new SqlCompiler(self::grammar(), self::connection()) )->compile($blueprint);
		self::execute($sql);
	}

	public static function raw( $queries ): void {
		if ( is_string($queries) ) {
			$queries = [ $queries ];
		}

		if ( !is_array($queries) ) {
			throw new InvalidArgumentException('Schema::raw() expects string or array of strings.');
		}

		self::execute($queries);
	}

	public static function rename( string $from, string $to ): void {
		$fromTable = self::grammar()->wrapTable(self::qualifyTable($from));
		$toTable = self::grammar()->wrapTable(self::qualifyTable($to));

		self::execute([
			"RENAME TABLE {$fromTable} TO {$toTable}",
		]);
	}

	public static function drop( string $table ): void {
		$wrapped = self::grammar()->wrapTable(self::qualifyTable($table));

		self::execute([
			"DROP TABLE {$wrapped}",
		]);
	}

	public static function dropIfExists( string $table ): void {
		$wrapped = self::grammar()->wrapTable(self::qualifyTable($table));

		self::execute([
			"DROP TABLE IF EXISTS {$wrapped}",
		]);
	}

	public static function hasTable( string $table ): bool {
		$qualified = self::qualifyTable($table);
		$sql = self::connection()->prepare('SHOW TABLES LIKE %s', $qualified);

		return (bool)self::connection()->scalar($sql);
	}

	public static function hasColumn( string $table, string $column ): bool {
		$wrapped = self::grammar()->wrapTable(self::qualifyTable($table));
		$sql = self::connection()->prepare("SHOW COLUMNS FROM {$wrapped} LIKE %s", $column);

		return (bool)self::connection()->scalar($sql);
	}

	public static function hasIndex( string $table, string $index ): bool {
		$wrapped = self::grammar()->wrapTable(self::qualifyTable($table));
		$sql = self::connection()->prepare("SHOW INDEX FROM {$wrapped} WHERE Key_name = %s", $index);

		return (bool)self::connection()->scalar($sql);
	}

	public static function createView( string $name, string $select ): void {
		$view = self::grammar()->wrapTable(self::qualifyTable($name));

		self::execute([
			"CREATE VIEW {$view} AS {$select}",
		]);
	}

	public static function dropView( string $name ): void {
		$view = self::grammar()->wrapTable(self::qualifyTable($name));

		self::execute([
			"DROP VIEW {$view}",
		]);
	}

	public static function createOrReplaceView( string $name, string $select ): void {
		$view = self::grammar()->wrapTable(self::qualifyTable($name));

		self::execute([
			"CREATE OR REPLACE VIEW {$view} AS {$select}",
		]);
	}

	protected static function execute( array $queries ): void {
		foreach ( $queries as $query ) {
			self::connection()->statement($query);
			if ( self::connection()->lastError() !== '' ) {
				throw new RuntimeException(self::connection()->lastError());
			}
		}
	}

	private static function connection(): ConnectionInterface {
		if ( self::$connection ) {
			return self::$connection;
		}

		global $wpdb;
		self::$connection = new WPDBConnection($wpdb);
		return self::$connection;
	}

	private static function grammar(): GrammarInterface {
		if ( !self::$grammar ) {
			self::$grammar = new MySqlGrammar();
		}

		return self::$grammar;
	}

	private static function qualifyTable( string $table ): string {
		return self::grammar()->qualifyTable($table, self::connection()->tablePrefix());
	}
}
