<?php

use WPMigrations\Migrations\MigrationInterface;
use WPMigrations\Schema\Schema;

return new class implements MigrationInterface
{
	public function up() : void {
		Schema::drop('{{table}}');
	}

	public function down() : void {
		//
	}
};
