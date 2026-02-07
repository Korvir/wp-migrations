<?php

namespace WPMigrations\Cli;

use WP_CLI;
use WP_CLI\ExitException;
use WP_CLI_Command;

class PublishStubCommand extends WP_CLI_Command {
	
	/**
	 * Publish migration stubs to the project.
	 *
	 * ## EXAMPLES
	 *
	 *     wp migrations stub:publish
	 *
	 * @throws ExitException
	 */
	public function __invoke() {
		$source = $this->getPackageStubPath();
		$target = $this->getProjectStubPath();
		
		if ( !is_dir($source) ) {
			WP_CLI::error('Stub source directory not found.');
		}
		if ( !is_dir($target) ) {
			mkdir($target, 0755, true);
		}
		
		
		$files = glob($source . '/*.stub.php');
		if ( empty($files) ) {
			WP_CLI::warning('No stub files found.');
			return;
		}
		
		foreach ( $files as $file ) {
			$destination = $target . '/' . basename($file);
			if ( file_exists($destination) ) {
				WP_CLI::log('Stub already exists, skipping: ' . basename($file));
				continue;
			}
			
			copy($file, $destination);
			WP_CLI::log('Published: ' . basename($file));
		}
		
		WP_CLI::success('Stub publishing complete.');
	}
	
	
	/**
	 * Retrieves the file system path to the package stubs directory.
	 *
	 * @return string The absolute path to the stubs directory.
	 */
	protected function getPackageStubPath(): string {
		return dirname(__DIR__, 2) . '/stubs';
	}
	
	
	/**
	 * Retrieves the file system path to the project stubs directory.
	 *
	 * @return string The absolute path to the project stubs directory.
	 */
	protected function getProjectStubPath(): string {
		if ( defined('WP_MIGRATIONS_STUB_PATH') ) {
			return rtrim(WP_MIGRATIONS_STUB_PATH, '/');
		}
		if ( function_exists('get_stylesheet_directory') ) {
			return get_stylesheet_directory() . '/migrations/stubs';
		}
		
		return WP_CONTENT_DIR . '/migrations/stubs';
	}
}
