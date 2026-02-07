<?php

use WPMigrations\Schema\Blueprint;
use WPMigrations\Schema\Schema;

return new class
{
	
	public function up() {
		Schema::table('{{table}}', function( Blueprint $table ) {
			//
		});
	}
	
	public function down() {
		Schema::table('{{table}}', function( Blueprint $table ) {
			//
		});
	}
	
};
