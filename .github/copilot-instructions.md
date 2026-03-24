# Copilot Instructions for mediapool_rename

## Project Overview
`mediapool_rename` is a [REDAXO 5](https://redaxo.org/) addon that enables renaming media files in REDAXO's mediapool. When a file is renamed via a meta info field, the addon renames the physical file and updates all database references automatically.

## Tech Stack
- **Language:** PHP (≥ 8.1, compatible with REDAXO 5)
- **Framework:** [REDAXO 5 CMS](https://redaxo.org/) addon structure
- **Database:** MySQL / MariaDB via REDAXO's `rex_sql` abstraction

## Repository Structure
- `boot.php` – Registers REDAXO extension points on addon boot
- `install.php` – Creates the meta info field on installation
- `uninstall.php` – Removes the meta info field on uninstallation
- `lib/rex_mediapool_rename.php` – Main addon class with all business logic
- `package.yml` – Addon metadata and dependency declarations
- `help.php` – Addon help page

## REDAXO-Specific Conventions
- Use REDAXO's `rex_sql` class for all database queries; avoid raw `mysqli_*` or PDO calls.
- Use `rex::getTablePrefix()` when referencing database table names (e.g., `rex::getTablePrefix() . 'media'`).
- Register functionality via REDAXO extension points using `rex_extension::register()`.
- Use `rex_view::error()`, `rex_view::warning()`, `rex_view::success()`, `rex_view::info()` for user-facing messages.
- Use `rex_path::media()` and other `rex_path` helpers when constructing file-system paths.
- Use `rex_media::get($filename)` to retrieve media objects.
- Use `rex_string::normalize()` for sanitizing user-provided strings (e.g., filenames).
- Cache should be cleared with `rex_delete_cache()` after structural changes.
- Meta info fields are registered with `rex_metainfo_add_field()` (in `install.php`) and removed with `rex_metainfo_delete_field()` (in `uninstall.php`).

## Coding Style
- PSR-2 / PSR-12 PHP coding style.
- Class names follow the pattern `rex_<addon_name>` (e.g., `rex_mediapool_rename`).
- Static methods are preferred for addon utility classes.
- Wrap database operations in `try/catch (rex_sql_exception $e)` blocks and surface errors via `rex_view::warning()`.
- Comments in German or English are both acceptable (the project uses both).

## Key Business Logic
1. The addon adds a custom meta info field (`med_mediapool_rename`) to REDAXO's media table.
2. On `MEDIA_FORM_EDIT` (late): clears the `med_mediapool_rename` meta field for all media records via an unconditional `UPDATE` on the media table when the form extension point is executed (i.e., during form handling, not after saving).
3. On `MEDIA_UPDATED` (late): reads the rename field, sanitizes it, renames the physical file, and runs `REPLACE` SQL updates across all database tables/fields that reference the old filename using a word-boundary regexp.
4. If the target filename already exists, the rename is aborted with an error message.

## Dependencies
- REDAXO: `^5.2.0`
- mediapool addon: `>=2.3.0`

## When Suggesting Code
- Always respect REDAXO addon architecture and APIs.
- Prefer `rex_sql` over raw SQL when feasible.
- Ensure filename handling is safe: sanitize with `rex_string::normalize()` before use in SQL or filesystem operations.
- When touching SQL queries that embed user-supplied values, prefer `rex_sql::setValue()` (which escapes the value) to avoid SQL injection. Use `rex_sql::setRawValue()` only for trusted SQL expressions (e.g., `NOW()`), never for user input.
