<?php

use WPMigrations\Migrations\MigrationInterface;
use WPMigrations\Schema\Schema;

return new class implements MigrationInterface
{
	public function up() {
		Schema::rename('{{from}}', '{{to}}');
	}

	public function down() {
		Schema::rename('{{to}}', '{{from}}');
	}
};
