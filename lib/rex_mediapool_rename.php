<?php

namespace Alexplusde\MediapoolRename;

use rex;
use rex_config;
use rex_delete_cache;
use rex_extension_point;
use rex_i18n;
use rex_media;
use rex_path;
use rex_request;
use rex_sql;
use rex_sql_exception;
use rex_string;
use rex_view;

/**
 * Handles renaming of media files in REDAXO's mediapool.
 *
 * Renames the physical file and updates all database references
 * (simple text fields, comma-separated lists) automatically.
 *
 * **Currently supported reference types:**
 * - Direct filename matches in any VARCHAR / TEXT column across all tables
 *
 * **Not yet supported (planned):**
 * - TEXTAREA / be_media fields defined in YForm table definitions
 * - JSON-encoded references
 * - Serialized PHP data
 */
class MediapoolRename
{
    private const META_FIELD = 'med_mediapool_rename';

    /**
     * Pre-fills the rename meta field for the current media item with its basename.
     *
     * Called via extension point MEDIA_FORM_EDIT (late) to initialize the rename field.
     */
    public static function clearMetaField(rex_extension_point $ep): void
    {
        /** @var rex_sql $media */
        $media = $ep->getParam('media');
        if (!$media instanceof rex_sql) {
            return;
        }

        $filename = $media->getValue('filename');
        if (!$filename) {
            return;
        }

        // Pre-fill meta field with normalized current filename (without extension)
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        $normalizedName = rex_string::normalize($nameWithoutExt, '_');

        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable('media'));
        $sql->setValue(self::META_FIELD, $normalizedName);
        $sql->setWhere('filename = :filename', ['filename' => $filename]);

        try {
            $sql->update();
        } catch (rex_sql_exception $e) {
            echo rex_view::warning($e->getMessage());
        }
    }

    /**
     * Processes a media rename after media has been updated.
     *
     * Called via extension point MEDIA_UPDATED (late).
     * Renames the physical file and updates all database references.
     */
    public static function processUpdatedMedia(rex_extension_point $ep): void
    {
        $filename = $ep->getParam('filename');

        if (!$filename || !self::getMediaByFilename($filename)) {
            return;
        }

        $renameValue = rex_request::post('med_mediapool_rename', 'string', '');

        $rename = rex_string::normalize($renameValue, '_', '-');

        if ('' === $rename) {
            return;
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $newFile = $rename . ($extension !== '' ? '.' . $extension : '');
        $oldFile = $filename;

        // Skip if filename has not changed
        if ($newFile === $oldFile) {
            return;
        }

        // Clear meta field
        self::clearMetaFieldForFile($oldFile);

        // Check if target filename already exists
        if (self::filenameExists($newFile)) {
            echo rex_view::error(rex_i18n::msg('mediapool_rename_error_filename_exists'));
            return;
        }

        // Rename physical file
        $oldPath = rex_path::media($oldFile);
        $newPath = rex_path::media($newFile);

        if (!file_exists($oldPath)) {
            echo rex_view::error(rex_i18n::msg('mediapool_rename_error_file_not_found'));
            return;
        }

        if (!rename($oldPath, $newPath)) {
            echo rex_view::error(rex_i18n::msg('mediapool_rename_error_rename_failed'));
            return;
        }

        // Update all database references
        self::updateDatabaseReferences($oldFile, $newFile);

        rex_delete_cache();

        echo rex_view::success(rex_i18n::msg('mediapool_rename_success', $oldFile, $newFile));
    }

    /**
     * Retrieves a media object if the file exists.
     */
    public static function getMediaByFilename(string $filename): ?rex_media
    {
        $media = rex_media::get($filename);

        if ($media instanceof rex_media && $media->fileExists()) {
            return $media;
        }

        return null;
    }

    /**
     * Clears the rename meta field for a specific file.
     */
    private static function clearMetaFieldForFile(string $filename): void
    {
        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable('media'));
        $sql->setValue(self::META_FIELD, '');
        $sql->setWhere('filename = :filename', ['filename' => $filename]);

        try {
            $sql->update();
        } catch (rex_sql_exception $e) {
            echo rex_view::warning($e->getMessage());
        }
    }

    /**
     * Checks whether a filename already exists in the media table.
     */
    private static function filenameExists(string $filename): bool
    {
        $sql = rex_sql::factory();
        $sql->setQuery(
            'SELECT `id` FROM `' . rex::getTable('media') . '` WHERE `filename` = :filename LIMIT 1',
            ['filename' => $filename],
        );

        return $sql->getRows() > 0;
    }

    /**
     * Returns the list of table-exclusion patterns from config.
     * Each non-empty line of the stored value is treated as a substring
     * that, if present in a table name, causes the table to be skipped.
     *
     * @return list<string>
     */
    private static function getExcludedTablePatterns(): array
    {
        // The PHP-level default ensures the feature works on existing installations
        // that upgrade without triggering a full re-install (where default_config is applied).
        $default = "_history\ntmp_";
        $raw = (string) rex_config::get('mediapool_rename', 'excluded_table_patterns', $default);

        return array_values(array_filter(array_map('trim', explode("\n", $raw))));
    }

    /**
     * Returns true when the given table name matches at least one exclusion pattern.
     *
     * @param list<string> $patterns
     */
    private static function isExcludedTable(string $table, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (str_contains($table, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true when the given MySQL column type is a textual type
     * on which a REPLACE/REGEXP update makes sense.
     */
    private static function isTextualColumnType(string $type): bool
    {
        // Normalize to lower-case and strip any length/charset suffix, e.g. "varchar(255)" -> "varchar"
        $baseType = strtolower(preg_replace('/\s*\(.*/', '', $type));

        return in_array($baseType, ['char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext'], true);
    }

    /**
     * Updates all database references from the old filename to the new one.
     *
     * Iterates over all tables and all VARCHAR/TEXT columns, replacing
     * occurrences that match on word boundaries.
     */
    private static function updateDatabaseReferences(string $oldFile, string $newFile): void
    {
        $sql = rex_sql::factory();
        $sql->setQuery('SHOW TABLES');
        $tables = $sql->getArray();

        // Load exclusion patterns once to avoid re-parsing config on every table iteration
        $excludedPatterns = self::getExcludedTablePatterns();

        foreach ($tables as $row) {
            $table = current($row);

            // Skip tables matching any configured exclusion pattern
            if (self::isExcludedTable($table, $excludedPatterns)) {
                continue;
            }

            $fieldSql = rex_sql::factory();
            $fieldSql->setQuery('SHOW COLUMNS FROM ' . $fieldSql->escapeIdentifier($table));
            $fields = $fieldSql->getArray();

            foreach ($fields as $fieldRow) {
                $field = $fieldRow['Field'];

                // Skip non-textual columns (INT, FLOAT, BLOB, DATE, etc.)
                if (!self::isTextualColumnType($fieldRow['Type'])) {
                    continue;
                }

                $escapedTable = $sql->escapeIdentifier($table);
                $escapedField = $sql->escapeIdentifier($field);

                $update = 'UPDATE ' . $escapedTable . '
                    SET ' . $escapedField . ' = REPLACE(' . $escapedField . ', :old_file, :new_file)
                    WHERE CONVERT(' . $escapedField . ' USING utf8mb4) REGEXP :regexp';

                // Match the filename only when it is delimited by characters that are not valid
                // filename characters (or by start/end of string). This approximates a "word boundary"
                // tailored to normalized media filenames (letters, digits, underscore, dot, dash).
                $filenameCharClass = 'A-Za-z0-9_.-';
                $regexp = '(^|[^' . $filenameCharClass . '])' . preg_quote($oldFile, '/') . '([^' . $filenameCharClass . ']|$)';

                $updateSql = rex_sql::factory();

                try {
                    $updateSql->setQuery($update, [
                        'regexp' => $regexp,
                        'old_file' => $oldFile,
                        'new_file' => $newFile,
                    ]);
                } catch (rex_sql_exception $e) {
                    // Silently skip tables/fields that cannot be updated (e.g. views, generated columns)
                }
            }
        }
    }
}
