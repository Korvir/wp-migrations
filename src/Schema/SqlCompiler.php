<?php

namespace WPMigrations\Sql;

use WPMigrations\Schema\Blueprint;

final class SqlCompiler {
	
	public function compile( Blueprint $blueprint ): array {
		if ( $blueprint->getContext()->getMode() === Blueprint::MODE_CREATE ) {
			return $this->compileCreate($blueprint);
		}
		
		return $this->compileAlter($blueprint);
	}
	
	protected function compileCreate( Blueprint $blueprint ): array {
		// TODO
		return [];
	}
	
	protected function compileAlter( Blueprint $blueprint ): array {
		// TODO
		return [];
	}
}

