# korvir/wp-migrations
Simple database migration runner for WordPress via WP-CLI.

## Install
```bash
composer require korvir/wp-migrations
```
`
## Commands
wp migrations add create_users_table
wp migrations migrate
wp migrations rollback
wp migrations rollback --step=1
wp migrations status
wp migrations reset
wp migrations fresh

## Migration example
```bash
return new class implements MigrationInterface {
	public function up() {}
	public function down() {}
};
```

## Pretend mode (dry-run)
All migration commands support the `--pretend` flag.

When enabled, migrations are **not executed**.
Instead, the command will show what *would* be done.

### Examples
Preview pending migrations:
```bash
wp migrations migrate --pretend
wp migrations rollback --step=2 --pretend
```

## Stub publishing

To customize migration templates, you can publish the default stubs:
```bash
wp migrations stub:publish
```
This will copy stub files into your project, where they can be freely modified.
