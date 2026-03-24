<?php

namespace Alexplusde\MediapoolRename;

use rex;
use rex_delete_cache;
use rex_extension_point;
use rex_i18n;
use rex_media;
use rex_path;
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
     * Clears the rename meta field for the current media item.
     *
     * Called via extension point MEDIA_FORM_EDIT (late).
     */
    public static function clearMetaField(rex_extension_point $ep): void
    {
        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable('media'));
        $sql->setValue(self::META_FIELD, '');
        $sql->setWhere('filename = :filename', ['filename' => $ep->getParam('filename')]);

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

        $sql = rex_sql::factory();
        $sql->setQuery(
            'SELECT `filename`, `med_mediapool_rename` FROM `' . rex::getTable('media') . '` WHERE `filename` = :filename',
            ['filename' => $filename],
        );

        if (0 === $sql->getRows()) {
            return;
        }

        $currentFilename = $sql->getValue('filename');
        $renameValue = $sql->getValue(self::META_FIELD);

        $rename = rex_string::normalize((string) $renameValue, '_', '.-');

        if ('' === $rename) {
            return;
        }

        $extension = pathinfo((string) $currentFilename, PATHINFO_EXTENSION);
        $newFile = $rename . ($extension !== '' ? '.' . $extension : '');
        $oldFile = (string) $currentFilename;

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

        foreach ($tables as $row) {
            $table = current($row);

            $fieldSql = rex_sql::factory();
            $fieldSql->setQuery('SHOW COLUMNS FROM ' . $fieldSql->escapeIdentifier($table));
            $fields = $fieldSql->getArray();

            foreach ($fields as $fieldRow) {
                $field = $fieldRow['Field'];

                $escapedTable = $sql->escapeIdentifier($table);
                $escapedField = $sql->escapeIdentifier($field);

                $update = 'UPDATE ' . $escapedTable . '
                    SET ' . $escapedField . ' = REPLACE(' . $escapedField . ', :old_file, :new_file)
                    WHERE CONVERT(' . $escapedField . ' USING utf8mb4) REGEXP :regexp';

                $regexp = '(^|[,|])' . preg_quote($oldFile, '/') . '($|[,|])';

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
