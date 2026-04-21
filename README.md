# Simple WP Migrations

Simple, explicit database migrations for WordPress via WP-CLI.

`korvir/wp-migrations` is a lightweight migration system inspired by Laravel,
but focused on WordPress + MySQL/MariaDB.

## Requirements
- PHP 7.4+
- WordPress 5.5+
- MySQL/MariaDB
- WP-CLI

## Install

```bash
composer require korvir/wp-migrations
```

## Quick Start

Create a migration:
```bash
wp migrations add create_users_table
```

Run pending migrations:
```bash
wp migrations migrate
```

Check status:
```bash
wp migrations status
```

Rollback last batch:
```bash
wp migrations rollback
```

## Commands

| Command | Description |
|---|---|
| `wp migrations install` | Create migrations table. |
| `wp migrations add <name>` | Create migration file from stub. |
| `wp migrations migrate [<name>]` | Run pending migrations. |
| `wp migrations rollback` | Roll back last batch. |
| `wp migrations status` | Show migration status table. |
| `wp migrations reset` | Roll back all executed migrations. |
| `wp migrations fresh` | Reset and run all pending migrations. |
| `wp migrations stub:publish` | Publish package stubs to project. |

### Command Options

`add`
- `--path=<path>`: custom migrations path.

`migrate`
- `[<name>]`: run one specific migration.
- `--only=<names>`: comma-separated filename fragments to include.
- `--except=<names>`: comma-separated filename fragments to exclude.
- `--step=<N>`: run only next N pending migrations.
- `--path=<path>`: custom migrations path.
- `--pretend`: dry-run (print migrations without executing).
- `--force`: skip production confirmation.

`rollback`
- `--step=<N>`: rollback N latest executed migrations (ignores batch boundary).
- `--pretend`: dry-run.

`reset`
- `--path=<path>`
- `--pretend`

`fresh`
- `--path=<path>`
- `--pretend`

Notes:
- `--only` and `--except` are mutually exclusive.
- In `production` environment, `migrate` asks for confirmation unless `--force` is provided.


## Migration File Structure

Use `MigrationInterface`:
```php
<?php

use WPMigrations\Migrations\MigrationInterface;
use WPMigrations\Schema\Blueprint;
use WPMigrations\Schema\Schema;

return new class implements MigrationInterface
{
    public function up(): void {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email', 190);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('users');
    }
};
```

## Schema API

### Schema methods

```php
Schema::create();
Schema::table();
Schema::rename();
Schema::drop();
Schema::dropIfExists();
Schema::hasTable();
Schema::hasColumn();
Schema::hasIndex();
Schema::createView();
Schema::dropView();
Schema::createOrReplaceView();
Schema::raw();
```

### Column Types (Blueprint)

| Blueprint method | SQL / behavior |
|---|---|
| `id()` | `BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY` |
| `increments()` | `INT UNSIGNED AUTO_INCREMENT PRIMARY KEY` |
| `bigIncrements()` | `BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY` |
| `tinyInteger()` | `TINYINT` |
| `smallInteger()` | `SMALLINT` |
| `mediumInteger()` | `MEDIUMINT` |
| `integer()` | `INT` |
| `bigInteger()` | `BIGINT` |
| `decimal($precision, $scale)` | `DECIMAL(p, s)` |
| `float()` | `FLOAT` |
| `double()` | `DOUBLE` |
| `boolean()` | `TINYINT(1)` |
| `char($length)` | `CHAR(length)` |
| `string($length = 255)` | `VARCHAR(length)` |
| `text()` | `TEXT` |
| `mediumText()` | `MEDIUMTEXT` |
| `longText()` | `LONGTEXT` |
| `binary()` | `BLOB` |
| `enum(array $values)` | `ENUM(...)` |
| `json()` | `JSON` |
| `uuid()` | `CHAR(36)` |
| `ulid()` | `CHAR(26)` |
| `ipAddress()` | `VARCHAR(45)` |
| `macAddress()` | `VARCHAR(17)` |
| `date()` | `DATE` |
| `time()` | `TIME` |
| `dateTime()` | `DATETIME` |
| `timestamp()` | `TIMESTAMP` |
| `year()` | `YEAR` |
| `timestamps()` | Adds `created_at` + `updated_at` |
| `timestampsTz()` | Alias of `timestamps()` |
| `softDeletes()` | Adds nullable `deleted_at` |
| `foreignId($column)` | Adds unsigned `BIGINT` foreign key column helper |

### Column Modifiers

| Modifier | Description |
|---|---|
| `nullable()` | Set column to `NULL` |
| `notNullable()` | Set column to `NOT NULL` |
| `default($value)` | Set default value |
| `unsigned()` | Add `UNSIGNED` (numeric types) |
| `autoIncrement()` | Add `AUTO_INCREMENT` |
| `removeAutoIncrement()` | Remove auto increment flag |
| `comment($text)` | Add column comment |
| `charset($charset)` | Set column charset |
| `collation($collation)` | Set column collation |
| `first()` | Place column first |
| `after($column)` | Place column after another column |
| `change()` | Compile as column modification (`ALTER ... MODIFY`) |

### Indexes

| Method | Description |
|---|---|
| `primary($columns)` | Add primary key |
| `unique($columns, $name = null)` | Add unique index |
| `index($columns, $name = null)` | Add non-unique index |
| `dropPrimary()` | Drop primary key |
| `dropUnique($name)` | Drop unique index by name |
| `dropIndex($name)` | Drop index by name |

### Foreign Keys

- `foreign($column)->references()->on()->onDelete()->onUpdate()->name()`
- `dropForeign($name)`
- `foreignId($column)->constrained($table = null, $column = 'id')`
- `foreignId(...)->cascadeOnDelete()/cascadeOnUpdate()/restrictOnDelete()/nullOnDelete()`
- `dropConstrainedForeignId($column)`

## Views

```php
Schema::createOrReplaceView(
    'active_users',
    "SELECT id, email FROM wp_users WHERE user_status = 0"
);

Schema::dropView('active_users');
```

## Raw SQL

```php
Schema::raw('ALTER TABLE wp_users ENGINE=InnoDB');

Schema::raw([
    'SET FOREIGN_KEY_CHECKS=0',
    'DROP TABLE IF EXISTS wp_legacy',
    'SET FOREIGN_KEY_CHECKS=1',
]);
```

## Stub Resolution

`wp migrations add` selects a stub by migration prefix:
- `create_*` -> `create.stub.php`
- `update_*` -> `update.stub.php`
- `rename_*` -> `rename.stub.php`
- `drop_*` -> `drop.stub.php`
- otherwise -> `default.stub.php`

Stub lookup order:
1. `WP_MIGRATIONS_STUB_PATH`
2. `<theme>/migrations/stubs`
3. package `stubs/`

Publish stubs into project:

```bash
wp migrations stub:publish
```

## Configuration

Path resolution order for migrations:
1. CLI option `--path`
2. `WP_MIGRATIONS_PATH`
3. `<theme>/migrations`
4. `WP_CONTENT_DIR/migrations`

Programmatic runner config:

```php
new WPMigrations\Migrations\MigrationRunner([
    'path' => '/custom/migrations/path',
    'table' => 'wp_migrations',
    // 'strict' => true,
    // 'connection' => custom ConnectionInterface implementation,
]);
```

## Important Notes

- Execution is explicit; there is no schema introspection.
- Rollback is not transaction-wrapped by default.
- Write defensive `down()` methods (especially for FK/index/column order).
- For MySQL, drop foreign keys before dropping dependent indexes/columns.
- Multisite is not first-class; if you iterate blogs manually, rollback correctness is on migration author.
