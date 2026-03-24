<?php

use Alexplusde\MediapoolRename\MediapoolRename;

rex_extension::register('MEDIA_FORM_EDIT', [MediapoolRename::class, 'clearMetaField'], rex_extension::LATE);
rex_extension::register('MEDIA_UPDATED', [MediapoolRename::class, 'processUpdatedMedia'], rex_extension::LATE);

if (rex::isBackend() && rex::getUser()) {
    $page = rex_request('page', 'string', '');
    if (str_starts_with($page, 'mediapool')) {
        rex_view::setJsProperty('mediapool_rename_validation_error', rex_i18n::msg('mediapool_rename_validation_error'));
        rex_view::addJsFile($this->getAssetsUrl('js/mediapool_rename.js'));
    }
}
