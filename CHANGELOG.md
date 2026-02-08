# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](https://semver.org/).

---
## [1.0.1]
- Column types:
  - `enum`
  - `json`

## [1.0.0] — 2026-02-07

### Added

- WP-CLI based migration runner for WordPress
- Migration creation command: `wp migrations add`
- Migration execution commands:
    - `wp migrations migrate`
    - `wp migrations rollback`
    - `wp migrations status`
- Execution-based migration system (no schema introspection)
- Schema Blueprint for MySQL / MariaDB
- Table operations:
    - `Schema::create`
    - `Schema::table`
    - `Schema::rename`
    - `Schema::drop`
    - `Schema::dropIfExists`
- Column types:
    - Numeric: `tinyInteger`, `smallInteger`, `mediumInteger`, `integer`, `bigInteger`,
      `decimal`, `float`, `double`, `boolean`
    - String / binary: `char`, `string`, `text`, `mediumText`, `longText`, `binary`
    - Date / time: `date`, `time`, `dateTime`, `timestamp`, `timestamps`
- Column modifiers:
    - `nullable`, `default`, `unsigned`
    - `autoIncrement`, `removeAutoIncrement`
    - `first`, `after`
    - `change`
    - `comment`
    - `charset`
    - `collation`
- Indexes:
    - `primary`
    - `unique` (named and unnamed)
    - `index` (named and unnamed)
    - `dropPrimary`, `dropUnique`, `dropIndex`
- Charset and collation support for CREATE and ALTER TABLE
- Keyword-based migration stubs:
    - `create`
    - `update`
    - `rename`
    - `drop`
    - `default`
- Stub publishing command:
    - `wp migrations stub:publish`
- Support for project-level stub overrides
- Support for batch-based rollback
- `--pretend` dry-run mode

### Notes

- This is the first stable release.
- The migration system is intentionally explicit.
- No automatic schema introspection is performed.
- Index names must be known explicitly when dropping.
- MySQL behavior is not abstracted away.

---
