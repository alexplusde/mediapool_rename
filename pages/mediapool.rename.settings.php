<?php

/** @var rex_addon $this */

$form = rex_config_form::factory('mediapool_rename');

$form->addFieldset(rex_i18n::msg('mediapool_rename_settings_fieldset'));

$field = $form->addTextAreaField('excluded_table_patterns');
$field->setLabel(rex_i18n::msg('mediapool_rename_settings_excluded_patterns_label'));
$field->setAttribute('rows', 8);
$field->setAttribute('placeholder', "_history\ntmp_");

$form->addRawField('<p class="help-block re-help-block">' . rex_i18n::msg('mediapool_rename_settings_excluded_patterns_notice') . '</p>');

$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('mediapool_rename_settings'));
$fragment->setVar('body', $form->get(), false);

echo $fragment->parse('core/page/section.php');
