<?php

use WPMigrations\Migrations\MigrationInterface;
use WPMigrations\Schema\Schema;

return new class implements MigrationInterface
{
	public function up() : void {
		Schema::rename('{{from}}', '{{to}}');
	}

	public function down() : void {
		Schema::rename('{{to}}', '{{from}}');
	}
};
