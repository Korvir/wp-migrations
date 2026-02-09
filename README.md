# Simple WP Migrations
Simple, explicit database migrations for WordPress via WP-CLI.

This package provides a minimal execution-based migration system inspired by Laravel,
but designed specifically for WordPress and MySQL/MariaDB.


#### Install
```bash
composer require korvir/wp-migrations
```

#### Requirements
- PHP 7.4+
- WordPress 5.5+
- MySQL/MariaDB
- WP-CLI

---

## Migrations

### Commands
```bash
wp migrations add
wp migrations migrate
wp migrations rollback
wp migrations status
wp migrations reset
wp migrations fresh
```

#### Commands overview
```bash
add -  Create a new migration file.
migrate -  Run pending migrations.
rollback -  Rollback the last database migration in batch.
rollback --step N - Rollback the N last database migration,
status -  Show a list of all migrations.
reset -  Rollback all database migrations.
fresh -  Drop all tables and re-run all migrations.
```

`migrate` command can be used with optional flags `--only` and `--except` to run specific migrations:
```bash
wp migrations migrate --only=2021_01_01_000000_create_users_table
wp migrations migrate --except=2021_01_01_000000_create_users_table
wp migrations migrate --only=2021_01_01_000000_create_users_table,2021_01_01_000001_create_posts_table
```


#### Migration Pretend mode (dry-run)
All migration commands support the `--pretend` flag.
When enabled, migrations are **not executed**.
Instead, the command will show what *would* be done.

Preview pending migrations:
```bash
wp migrations migrate --pretend
wp migrations rollback --step=2 --pretend
```


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

---

## Schema Builder & Blueprint
Schema and Blueprint provide a declarative API for describing structural tables in WordPress (MySQL/MariaDB) migrations.

### Methods:
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
```

#### Charset & Collation
In `Schema::table()`, `charset()` and `collation()` change
the table default charset and collation only.

Existing columns and data are not modified.
```php
Schema::create('posts', function (Blueprint $table) {
    $table->charset('utf8mb4');
    $table->collation('utf8mb4_unicode_ci');
});

// If not specified, WordPress defaults ($wpdb->get_charset_collate()) are used.
```

### Table Columns
#### Supported column types
| Blueprint method              | SQL type                                     |
| ----------------------------- | -------------------------------------------- |
| `id()`                        | `BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY` |
| `increments()`                | `INT UNSIGNED AUTO_INCREMENT`                |
| `bigIncrements()`             | `BIGINT UNSIGNED AUTO_INCREMENT`             |
| `integer()`                   | `INT`                                        |
| `bigInteger()`                | `BIGINT`                                     |
| `mediumInteger()`             | `MEDIUMINT`                                  |
| `smallInteger()`              | `SMALLINT`                                   |
| `tinyInteger()`               | `TINYINT`                                    |
| `boolean()`                   | `TINYINT(1)`                                 |
| `string($length = 255)`       | `VARCHAR($length)`                           |
| `char($length)`               | `CHAR($length)`                              |
| `text()`                      | `TEXT`                                       |
| `mediumText()`                | `MEDIUMTEXT`                                 |
| `longText()`                  | `LONGTEXT`                                   |
| `binary()`                    | `BLOB`                                       |
| `float()`                     | `FLOAT`                                      |
| `double()`                    | `DOUBLE`                                     |
| `decimal($precision, $scale)` | `DECIMAL(p, s)`                              |
| `date()`                      | `DATE`                                       |
| `dateTime()`                  | `DATETIME`                                   |
| `time()`                      | `TIME`                                       |
| `timestamp()`                 | `TIMESTAMP`                                  |
| `timestamps()`                | `created_at` + `updated_at` (`DATETIME`)     |
| `year()`                      | `YEAR`                                       |
| `json()`                      | `JSON`                                       |
| `enum(array $values)`         | `ENUM(...)`                                  |
| `uuid()`                      | `CHAR(36)`                                   |
| `ulid()`                      | `CHAR(26)`                                   |
| `ipAddress()`                 | `VARCHAR(45)`                                |
| `macAddress()`                | `VARCHAR(17)`                                |


#### Supported column Modifiers
Modifiers can be chained on column definitions.

| Modifier                | Description                |
| ----------------------- | -------------------------- |
| `nullable()`            | Allows `NULL`              |
| `notNullable()`         | Sets `NOT NULL`            |
| `default($value)`       | Default value              |
| `unsigned()`            | UNSIGNED (numeric types)   |
| `autoIncrement()`       | AUTO_INCREMENT             |
| `comment($text)`        | Column comment             |
| `charset($charset)`     | Column charset             |
| `collation($collation)` | Column collation           |
| `first()`               | Place column first         |
| `after($column)`        | Place column after another |
```php
// Example:
$table->string('status', 20)
	->unsigned()
	->default(1)
	->comment('User status');
```

#### Changing column types
```php
// To modify an existing column, define it again and call change().
$table->string('email', 320)->change();
```

#### Dropping & Renaming Columns
```php
$table->dropColumn('legacy');
$table->dropColumn(['foo', 'bar']);

$table->renameColumn('old_name', 'new_name');
```

#### Adding & Dropping Indexes
Primary keys are unnamed.

Unique and non-unique indexes may be named explicitly.
If an index name is not provided, MySQL will generate one automatically.

To drop an index, you must know its name.
```php
// Creating indexes
$table->primary('id');
$table->unique('email');
$table->index(['user_id', 'status']);

// Named indexes
$table->unique('email', 'unique_email');
$table->index(['user_id'], 'idx_user');

// Dropping indexes
$table->dropPrimary();
$table->dropIndex('idx_user');
$table->dropUnique('unique_email');
```

#### Adding & Dropping Foreign Keys
This package does **not** attempt to:
- detect existing constraints
- infer relationships
- automatically manage rollback safety

Foreign keys are executed exactly as declared. If no constraint name is provided, the following naming convention is used:
`{table}_{column}_foreign` === `(orders_user_id_foreign)`

```php
// Examples:
Schema::table('orders', function (Blueprint $table) {
    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->onDelete('cascade');
});

Schema::table('orders', function (Blueprint $table) {
    $table->dropForeign('orders_user_id_foreign');
});
```
This generates:
```sql
ALTER TABLE wp_orders
ADD CONSTRAINT orders_user_id_foreign
FOREIGN KEY (user_id)
REFERENCES wp_users(id)
ON DELETE CASCADE;

ALTER TABLE wp_orders DROP FOREIGN KEY orders_user_id_foreign;
```

#### Foreign Keys and Column Changes
MySQL does not allow modifying or dropping a column while a foreign key constraint exists.

When changing or dropping such columns, foreign keys must be dropped manually
and re-created if necessary.

Examples:
```php
// Changing a column type
public function up() {
    Schema::table('orders', function (Blueprint $table) {
        $table->dropForeign('fk_orders_user');
        $table->bigInteger('user_id')->change();
        $table->foreign('user_id')
            ->references('id')
            ->on('users');
    });
}

// Dropping a column that participates in a foreign key constraint
// requires dropping the foreign key first.
public function up() {
    Schema::table('orders', function (Blueprint $table) {
        $table->dropForeign('fk_orders_user');
        $table->dropColumn('user_id');
    });
}
```

---

#### Database Views
Views may be created using raw SQL.
```php
Schema::createView('active_users', '
    SELECT id, email
    FROM users
    WHERE active = 1
');

Schema::dropView('active_users');
```

---

#### Raw SQL
You may execute arbitrary SQL queries using `Schema::raw()`.
```php
// Single query
Schema::raw('ALTER TABLE users ENGINE=InnoDB');

// Multiple queries may be executed at once:
Schema::raw([
    'SET FOREIGN_KEY_CHECKS=0',
    'DROP TABLE legacy',
    'SET FOREIGN_KEY_CHECKS=1',
]);
```

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


---

---

---

## Multisite
Not supported.

But you may apply schema changes across multiple sites manually
(e.g. by iterating over `get_sites()` and using `switch_to_blog()`).

In this case, the migration is still considered a single unit.
Rollback correctness is the responsibility of the migration author.

Example:
```php
public function up() {
    foreach (get_sites() as $site) {
        switch_to_blog($site->blog_id);
    
        Schema::table('orders', function (Blueprint $table) {
            $table->string('foo');
        });
    
        restore_current_blog();
    }
}
```

---

