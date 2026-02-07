<?php

namespace WPMigrations\Schema;


final class Blueprint {
	public const MODE_CREATE = 'create';
	public const MODE_ALTER = 'alter';
	
	protected TableContext $context;
	
	/** @var Column[] */
	protected array $columns = [];
	
	/** @var Column[] */
	protected array $changedColumns = [];
	
	protected array $droppedColumns = [];
	protected array $renamedColumns = [];
	
	protected ?Index $primary = null;
	protected array $uniqueIndexes = [];
	protected array $indexes = [];
	
	protected bool $dropPrimary = false;
	protected array $droppedIndexes = [];
	
	
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
	
	// ---------- columns (add) ----------
	
	public function id( string $name = 'id' ): Column {
		$column = $this->bigInteger($name)
			->unsigned()
			->autoIncrement();
		$this->primary($name);
		return $column;
	}
	
	public function bigInteger( string $name ): Column {
		return $this->addColumn('bigInteger', $name);
	}
	
	public function string( string $name, int $length = 255 ): Column {
		return $this->addColumn('string', $name, $length);
	}
	
	public function integer( string $name ): Column {
		return $this->addColumn('integer', $name);
	}
	
	// TODO: остальные типы добавишь аналогично
	
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
	
	
	// ---------- change ----------
	
	public function change( Column $column ): void {
		$column->markAsChange();
		$this->changedColumns[] = $column;
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
	
	public function getChangedColumns(): array {
		return $this->changedColumns;
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
	
	public function dropUnique(string $name): void {
		$this->droppedIndexes[] = $name;
	}
	
	public function shouldDropPrimary(): bool {
		return $this->dropPrimary;
	}
	
	public function getDroppedIndexes(): array {
		return $this->droppedIndexes;
	}
	
}

