<?php

use Alexplusde\MediapoolRename\MediapoolRename;

rex_extension::register('MEDIA_FORM_EDIT', [MediapoolRename::class, 'clearMetaField'], rex_extension::LATE);
rex_extension::register('MEDIA_UPDATED', [MediapoolRename::class, 'processUpdatedMedia'], rex_extension::LATE);

if (rex::isBackend() && rex::getUser()) {
    $page = rex_request('page', 'string', '');
    if (str_starts_with($page, 'mediapool')) {
        rex_extension::register('OUTPUT_FILTER', static function (rex_extension_point $ep) {
            $content = $ep->getSubject();

            $noticeJs = json_encode(rex_i18n::msg('mediapool_rename_allowed_chars'), JSON_THROW_ON_ERROR);
            $errorJs = json_encode(rex_i18n::msg('mediapool_rename_validation_error'), JSON_THROW_ON_ERROR);

            $script = <<<HTML
            <script>
            document.addEventListener("DOMContentLoaded", function() {
                var input = document.querySelector('input[name="med_mediapool_rename"]');
                if (!input) return;

                var urlParams = new URLSearchParams(window.location.search);
                var filename = urlParams.get("file_name") || "";
                var nameWithoutExt = filename.replace(/\\.[^.]+$/, "");

                input.setAttribute("placeholder", nameWithoutExt);
                input.setAttribute("pattern", "[a-z0-9_-]+");

                var formGroup = input.closest(".form-group") || input.parentNode;

                var notice = document.createElement("p");
                notice.className = "help-block text-muted";
                notice.textContent = {$noticeJs};
                formGroup.appendChild(notice);

                var feedback = document.createElement("p");
                feedback.className = "help-block text-danger";
                feedback.style.display = "none";
                feedback.textContent = {$errorJs};
                formGroup.appendChild(feedback);

                input.addEventListener("input", function() {
                    var val = this.value;
                    if (val === "") {
                        formGroup.classList.remove("has-error", "has-success");
                        feedback.style.display = "none";
                        return;
                    }
                    if (/^[a-z0-9_-]+$/.test(val)) {
                        formGroup.classList.remove("has-error");
                        formGroup.classList.add("has-success");
                        feedback.style.display = "none";
                    } else {
                        formGroup.classList.remove("has-success");
                        formGroup.classList.add("has-error");
                        feedback.style.display = "";
                    }
                });
            });
            </script>
            HTML;

            return str_replace('</body>', $script . '</body>', $content);
        });
    }
}
