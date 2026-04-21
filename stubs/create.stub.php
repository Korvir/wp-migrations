<?php

use WPMigrations\Migrations\MigrationInterface;
use WPMigrations\Schema\Blueprint;
use WPMigrations\Schema\Schema;

return new class implements MigrationInterface
{
	
	public function up() : void {
		Schema::create('{{table}}', function( Blueprint $table ) {
			$table->id();
		});
	}
	
	public function down() : void {
		Schema::dropIfExists('{{table}}');
	}
	
};
