<?php

/** @var rex_addon $this */

use Alexplusde\MediapoolRename\MediapoolRename;

$form = rex_config_form::factory('mediapool_rename');

$form->addFieldset(rex_i18n::msg('mediapool_rename_settings_fieldset'));

$field = $form->addSelectField('excluded_tables');
$field->setLabel(rex_i18n::msg('mediapool_rename_settings_excluded_tables_label'));

$select = $field->getSelect();
$select->setMultiple(true);
$select->setSize(15);

// Get all database tables and add only those not always excluded
$sql = rex_sql::factory();
$sql->setQuery('SHOW TABLES');
$tables = $sql->getArray();

$alwaysExcludedTables = [];

foreach ($tables as $row) {
    $table = (string) current($row);

    if (MediapoolRename::isAlwaysExcludedTable($table)) {
        $alwaysExcludedTables[] = $table;
        continue;
    }

    $select->addOption($table, $table);
}

$form->addRawField(
    '<p class="help-block rex-note">'
    . rex_i18n::msg('mediapool_rename_settings_excluded_tables_notice')
    . '</p>',
);

if (count($alwaysExcludedTables) > 0) {
    $form->addRawField(
        '<p class="help-block rex-note">'
        . rex_i18n::msg('mediapool_rename_settings_always_excluded')
        . '<br><code>' . implode('</code>, <code>', array_map('rex_escape', $alwaysExcludedTables)) . '</code>'
        . '</p>',
    );
}

$form->addRawField(
    '<p class="help-block rex-note">'
    . rex_i18n::msg('mediapool_rename_settings_ep_notice')
    . '</p>',
);

$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('mediapool_rename_settings'));
$fragment->setVar('body', $form->get(), false);

echo $fragment->parse('core/page/section.php');
