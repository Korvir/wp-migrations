<?php

use WPMigrations\Schema\Schema;

return new class
{
	
	public function up() {
		Schema::rename('{{from}}', '{{to}}');
	}
	
	public function down() {
		Schema::rename('{{to}}', '{{from}}');
	}
	
};
