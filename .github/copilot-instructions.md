# Copilot Instructions for mediapool_rename

## Project Overview
`mediapool_rename` is a [REDAXO 5](https://redaxo.org/) addon that enables renaming media files in REDAXO's mediapool. When a file is renamed via a meta info field, the addon renames the physical file and updates all database references automatically.

## Tech Stack
- **Language:** PHP (≥ 8.3, < 9)
- **Framework:** [REDAXO 5 CMS](https://redaxo.org/) addon structure
- **Database:** MySQL / MariaDB via REDAXO's `rex_sql` abstraction
- **Namespace:** `Alexplusde\MediapoolRename`

## Repository Structure
- `boot.php` – Registers REDAXO extension points on addon boot
- `install.php` – Creates the meta info field on installation
- `uninstall.php` – Removes the meta info field on uninstallation
- `lib/rex_mediapool_rename.php` – Main addon class `MediapoolRename` (namespace `Alexplusde\MediapoolRename`)
- `package.yml` – Addon metadata, dependency declarations, page definitions
- `help.php` – Addon help page (renders README.md)
- `lang/de_de.lang` – German translations
- `lang/en_gb.lang` – English translations
- `readme.md` – Comprehensive addon documentation

## Namespace & Class Conventions
- Namespace: `Alexplusde\MediapoolRename`
- Main class: `Alexplusde\MediapoolRename\MediapoolRename`
- REDAXO autoloads classes from `lib/` based on the file's namespace declaration
- In `boot.php`, use `use Alexplusde\MediapoolRename\MediapoolRename;` and reference via `MediapoolRename::class`

## REDAXO-Specific Conventions
- Use REDAXO's `rex_sql` class for all database queries; avoid raw `mysqli_*` or PDO calls.
- Use `rex::getTable('media')` (not `rex::getTablePrefix() . 'media'`) when referencing database table names.
- Use parameterized queries with `rex_sql` for any user-supplied values to prevent SQL injection.
- Register functionality via REDAXO extension points using `rex_extension::register()`.
- Use `rex_view::error()`, `rex_view::warning()`, `rex_view::success()`, `rex_view::info()` for user-facing messages.
- Use `rex_path::media()` and other `rex_path` helpers when constructing file-system paths.
- Use `rex_media::get($filename)` to retrieve media objects.
- Use `rex_string::normalize()` for sanitizing user-provided strings (e.g., filenames).
- Use `rex_i18n::msg()` for all user-facing strings; never hardcode text.
- Cache should be cleared with `rex_delete_cache()` after structural changes.
- Meta info fields are registered with `rex_metainfo_add_field()` (in `install.php`) and removed with `rex_metainfo_delete_field()` (in `uninstall.php`).

## Coding Style
- PSR-12 PHP coding style.
- Use PHP 8.3+ features: typed properties, return types, named arguments where appropriate.
- Static methods are preferred for addon utility classes.
- Wrap database operations in `try/catch (rex_sql_exception $e)` blocks.
- Use constants (`private const`) for repeated string literals.
- All user-facing text must use `rex_i18n::msg()` with keys from `lang/*.lang` files.

## Key Business Logic
1. The addon adds a custom meta info field (`med_mediapool_rename`) to REDAXO's media table.
2. On `MEDIA_FORM_EDIT` (late): clears the `med_mediapool_rename` meta field for the current media item.
3. On `MEDIA_UPDATED` (late): reads the rename field, sanitizes it, renames the physical file, and runs `REPLACE` SQL updates across all database tables/fields that reference the old filename using a word-boundary regexp.
4. If the target filename already exists, the rename is aborted with a translated error message.

## What Gets Updated (and What Doesn't Yet)
**Currently updated:**
- Direct filename matches in any VARCHAR/TEXT column across all database tables
- Comma-separated media lists

**Not yet supported (planned for future versions):**
- TEXTAREA / be_media fields defined in YForm table definitions
- JSON-encoded references
- Serialized PHP data
- Media Manager cache files

## Dependencies
- PHP: >= 8.3, < 9
- REDAXO: ^5.18.3
- mediapool addon: >= 2.3.0
- metainfo addon: >= 2.6.0

## When Suggesting Code
- Always respect REDAXO addon architecture and APIs.
- Use the `Alexplusde\MediapoolRename` namespace for all new classes.
- Use `rex_sql` parameterized queries — never interpolate user values into SQL strings.
- Ensure filename handling is safe: sanitize with `rex_string::normalize()` before use.
- Use `rex_i18n::msg()` for any user-facing strings; add new keys to `lang/de_de.lang` and `lang/en_gb.lang`.
- All methods must have return type declarations and parameter type hints.
