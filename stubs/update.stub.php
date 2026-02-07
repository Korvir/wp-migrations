<?php

use WPMigrations\Schema;
use WPMigrations\Blueprint;

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
