<?php

/** @var rex_addon $this */
$file = $this->getPath('readme.md');

if (file_exists($file)) {
    [$readmeContent] = rex_markdown::factory()->parse(rex_file::get($file));
    echo '<div class="rex-docs">' . $readmeContent . '</div>';
}