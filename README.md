# korvir/wp-migrations

Simple WordPress migration runner via WP-CLI.

## Install

composer require korvir/wp-migrations

## Commands

wp migrations add create_users_table
wp migrations migrate
wp migrations rollback
wp migrations status

## Migration example
```php
return new class implements MigrationInterface {
	public function up() {}
	public function down() {}
};
```
