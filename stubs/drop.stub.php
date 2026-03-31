<?php

use WPMigrations\Migrations\MigrationInterface;
use WPMigrations\Schema\Schema;

return new class implements MigrationInterface
{
	public function up() {
		Schema::drop('{{table}}');
	}

	public function down() {
		//
	}
};
