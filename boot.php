<?php

use Alexplusde\MediapoolRename\MediapoolRename;

rex_extension::register('MEDIA_FORM_EDIT', [MediapoolRename::class, 'clearMetaField'], rex_extension::LATE);
rex_extension::register('MEDIA_UPDATED', [MediapoolRename::class, 'processUpdatedMedia'], rex_extension::LATE);
