<?php

use WPMigrations\Migrations\MigrationInterface;
use WPMigrations\Schema\Blueprint;
use WPMigrations\Schema\Schema;

return new class implements MigrationInterface
{
	public function up() : void {
		Schema::table('{{table}}', function( Blueprint $table ) {
			//
		});
	}

	public function down() : void {
		Schema::table('{{table}}', function( Blueprint $table ) {
			//
		});
	}
};
