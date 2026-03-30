<?php

/** @var rex_addon $this */

$attributes = 'note="translate:mediapool_rename_allowed_chars" pattern="[a-z0-9_-]+"';

$sql = rex_sql::factory();
$sql->setQuery(
    'SELECT id FROM ' . rex::getTable('metainfo_field') . ' WHERE name = :name',
    ['name' => 'med_mediapool_rename'],
);

if (0 === $sql->getRows()) {
    rex_metainfo_add_field(
        'translate:mediapool_rename_meta_field_label',
        'med_mediapool_rename',
        1,
        $attributes,
        1,
        '',
        '',
        '',
        '',
    );
} else {
    $update = rex_sql::factory();
    $update->setTable(rex::getTable('metainfo_field'));
    $update->setValue('attributes', $attributes);
    $update->setWhere('name = :name', ['name' => 'med_mediapool_rename']);
    $update->update();
}

// Sync assets to public directory
$this->setConfig('version', $this->getVersion());
rex_dir::copy($this->getPath('assets'), $this->getAssetsPath());
