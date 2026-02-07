<?php

use WPMigrations\Schema\Blueprint;
use WPMigrations\Schema\Schema;

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
