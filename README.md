# Simple WP Migrations
Simple, explicit database migrations for WordPress via WP-CLI.

This package provides a minimal execution-based migration system inspired by Laravel,
but designed specifically for WordPress and MySQL/MariaDB.


## Install
```bash
composer require korvir/wp-migrations
```

## Requirements
- PHP 7.4+
- WordPress 5.5+
- MySQL/MariaDB
- WP-CLI

---

## Commands
```bash
wp migrations add
wp migrations migrate
wp migrations rollback
wp migrations status
wp migrations reset
wp migrations fresh
```
#### `add` Create a new migration file.
#### `migrate` Run pending migrations.
#### `rollback` Rollback the last database migration in batch.
#### `rollback --step N` Rollback the N last database migration,
#### `status` Show a list of all migrations.
#### `reset` Rollback all database migrations.
#### `fresh` Drop all tables and re-run all migrations.


### Pretend mode (dry-run)
All migration commands support the `--pretend` flag.
When enabled, migrations are **not executed**.
Instead, the command will show what *would* be done.


### Migration Structure
```php
return new class {
    public function up(){
        // apply changes
    }
    public function down(){
        // rollback changes
    }
};
```

### Examples
Preview pending migrations:
```bash
wp migrations migrate --pretend
wp migrations rollback --step=2 --pretend
```

---


### Charset & Collation
In `Schema::table()`, `charset()` and `collation()` change
the table default charset and collation only.

Existing columns and data are not modified.

---

### Index naming
Primary keys are unnamed.

Unique and non-unique indexes may be named explicitly.
If an index name is not provided, MySQL will generate one automatically.

To drop an index, you must know its name.

---

### Migration stubs
Migration stubs are selected automatically based on migration name prefix:
- create_* → create stub
- update_* → update stub
- rename_* → rename stub
- drop_*   → drop stub

If no keyword is detected, the default stub is used.


### Stub publishing
You need do define the `WP_MIGRATIONS_STUBS_PATH` constant.
To customize migration templates, you can publish the default stubs:
```bash
wp migrations stub:publish
```
This will copy stub files into your project, where they can be freely modified.
