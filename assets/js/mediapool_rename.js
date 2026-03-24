document.addEventListener("DOMContentLoaded", function () {
    var input = document.querySelector('input[name="med_mediapool_rename"]');
    if (!input) {
        return;
    }

    var urlParams = new URLSearchParams(window.location.search);
    var filename = urlParams.get("file_name") || "";
    var nameWithoutExt = filename.replace(/\.[^.]+$/, "");

    if (nameWithoutExt) {
        input.setAttribute("placeholder", nameWithoutExt);
    }

    var formGroup = input.closest(".form-group") || input.parentNode;
    var pattern = input.getAttribute("pattern");
    if (!pattern) {
        return;
    }
    var regex = new RegExp("^" + pattern + "$");

    var feedback = document.createElement("p");
    feedback.className = "help-block text-danger";
    feedback.style.display = "none";
    feedback.textContent = rex.mediapool_rename_validation_error || "";
    formGroup.appendChild(feedback);

    input.addEventListener("input", function () {
        var val = this.value;
        if (val === "") {
            formGroup.classList.remove("has-error", "has-success");
            feedback.style.display = "none";
            return;
        }
        if (regex.test(val)) {
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
