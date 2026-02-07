<?php

use WPMigrations\Schema;
use WPMigrations\Blueprint;

return new class
{
	
	public function up() {
		Schema::create('{{table}}', function( Blueprint $table ) {
			$table->id();
		});
	}
	
	public function down() {
		Schema::dropIfExists('{{table}}');
	}
};
