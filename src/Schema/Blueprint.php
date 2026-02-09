<?php

namespace WPMigrations\Schema;


final class Blueprint {
	public const MODE_CREATE = 'create';
	public const MODE_ALTER = 'alter';
	
	protected TableContext $context;
	
	/** @var Column[] */
	protected array $columns = [];
	
	protected array $droppedColumns = [];
	protected array $renamedColumns = [];
	
	protected ?Index $primary = null;
	protected array $uniqueIndexes = [];
	protected array $indexes = [];
	
	protected bool $dropPrimary = false;
	protected array $droppedIndexes = [];
	
	protected array $foreignKeys = [];
	protected array $droppedForeignKeys = [];
	
	
	public function __construct( string $table, string $mode ) {
		$this->context = new TableContext($table, $mode);
	}
	
	// ---------- table options ----------
	
	public function charset( string $charset ): void {
		$this->context->setCharset($charset);
	}
	
	public function collation( string $collation ): void {
		$this->context->setCollation($collation);
	}
	
	// ---------- columns ----------
	
	public function id( string $name = 'id' ): Column {
		$column = $this->bigInteger($name)
			->unsigned()
			->autoIncrement();
		$this->primary($name);
		return $column;
	}
	
	public function tinyInteger( string $name ): Column {
		return $this->addColumn('tinyInteger', $name);
	}
	
	public function smallInteger( string $name ): Column {
		return $this->addColumn('smallInteger', $name);
	}
	
	public function mediumInteger( string $name ): Column {
		return $this->addColumn('mediumInteger', $name);
	}
	
	public function integer( string $name ): Column {
		return $this->addColumn('integer', $name);
	}
	
	public function bigInteger( string $name ): Column {
		return $this->addColumn('bigInteger', $name);
	}
	
	public function decimal( string $name, int $precision = 8, int $scale = 2 ): Column {
		return $this->addColumn('decimal', $name, $precision, $scale);
	}
	
	public function float( string $name ): Column {
		return $this->addColumn('float', $name);
	}
	
	public function double( string $name ): Column {
		return $this->addColumn('double', $name);
	}
	
	public function boolean( string $name ): Column {
		return $this->addColumn('boolean', $name);
	}
	
	public function char( string $name, int $length = 1 ): Column {
		return $this->addColumn('char', $name, $length);
	}
	
	public function string( string $name, int $length = 255 ): Column {
		return $this->addColumn('string', $name, $length);
	}
	
	public function text( string $name ): Column {
		return $this->addColumn('text', $name);
	}
	
	public function mediumText( string $name ): Column {
		return $this->addColumn('mediumText', $name);
	}
	
	public function longText( string $name ): Column {
		return $this->addColumn('longText', $name);
	}
	
	public function binary( string $name ): Column {
		return $this->addColumn('binary', $name);
	}
	
	public function enum( string $name, array $values ): Column {
		return $this->addColumn('enum', $name, $values);
	}
	
	public function json( string $name ): Column {
		return $this->addColumn('json', $name);
	}
	
	public function macAddress( string $name ): Column {
		return $this->addColumn('macAddress', $name);
	}
	
	public function ipAddress( string $name ): Column {
		return $this->addColumn('ipAddress', $name);
	}
	
	public function uuid( string $name ): Column {
		return $this->addColumn('uuid', $name);
	}
	
	public function ulid( string $name ): Column {
		return $this->addColumn('ulid', $name);
	}
	
	public function date( string $name ): Column {
		return $this->addColumn('date', $name);
	}
	
	public function time( string $name ): Column {
		return $this->addColumn('time', $name);
	}
	
	public function dateTime( string $name ): Column {
		return $this->addColumn('dateTime', $name);
	}
	
	public function year( string $name ): Column {
		return $this->addColumn('year', $name);
	}
	
	public function timestamp( string $name ): Column {
		return $this->addColumn('timestamp', $name);
	}
	
	public function timestamps(): void {
		$this->timestamp('created_at')->nullable();
		$this->timestamp('updated_at')->nullable();
	}
	
	
	protected function addColumn( string $type, string $name, ...$args ): Column {
		$column = new Column($name, $type, $args);
		$this->columns[] = $column;
		
		return $column;
	}
	
	
	// ---------- Indexes ----------
	public function primary( $columns ): void {
		$this->primary = new Index('primary', (array)$columns);
	}
	
	public function unique( $columns, ?string $name = null ): void {
		$this->uniqueIndexes[] = new Index('unique', (array)$columns, $name);
	}
	
	public function index( $columns, ?string $name = null ): void {
		$this->indexes[] = new Index('index', (array)$columns, $name);
	}
	
	// ---------- Foreign Keys ----------
	public function foreign(string $column): ForeignKey {
		$fk = new ForeignKey($column);
		$this->foreignKeys[] = $fk;
		return $fk;
	}
	
	public function dropForeign(string $name): void {
		$this->droppedForeignKeys[] = $name;
	}
	
	
	
	
	// ---------- destructive ----------
	
	/**
	 * @param string|string[] $columns
	 */
	public function dropColumn( $columns ): void {
		foreach ( (array)$columns as $column ) {
			$this->droppedColumns[] = $column;
		}
	}
	
	public function renameColumn( string $from, string $to ): void {
		$this->renamedColumns[] = compact('from', 'to');
	}
	
	// ---------- getters (для compiler) ----------
	
	public function getContext(): TableContext {
		return $this->context;
	}
	
	public function getColumns(): array {
		return $this->columns;
	}
	
	public function getDroppedColumns(): array {
		return $this->droppedColumns;
	}
	
	public function getRenamedColumns(): array {
		return $this->renamedColumns;
	}
	
	public function getPrimary(): ?Index {
		return $this->primary;
	}
	
	public function getUniqueIndexes(): array {
		return $this->uniqueIndexes;
	}
	
	public function getIndexes(): array {
		return $this->indexes;
	}
	
	public function dropPrimary(): void {
		$this->dropPrimary = true;
	}
	
	public function dropIndex( string $name ): void {
		$this->droppedIndexes[] = $name;
	}
	
	public function dropUnique( string $name ): void {
		$this->droppedIndexes[] = $name;
	}
	
	public function shouldDropPrimary(): bool {
		return $this->dropPrimary;
	}
	
	public function getDroppedIndexes(): array {
		return $this->droppedIndexes;
	}
	
	public function getForeignKeys(): array {
		return $this->foreignKeys;
	}
	
	public function getDroppedForeignKeys(): array {
		return $this->droppedForeignKeys;
	}
	
}

