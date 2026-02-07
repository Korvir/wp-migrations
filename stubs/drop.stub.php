<?php

use WPMigrations\Schema\Schema;

return new class
{
	
	public function up() {
		Schema::drop('{{table}}');
	}
	
	public function down() {
		//
	}
	
};
