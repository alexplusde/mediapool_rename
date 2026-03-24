document.addEventListener("DOMContentLoaded", function () {
    var input = document.querySelector('input[name="med_mediapool_rename"]');
    if (!input) {
        return;
    }

    var formGroup = input.closest(".form-group") || input.parentNode;
    var inputContainer = input.parentNode;
    var pattern = input.getAttribute("pattern");
    if (!pattern) {
        return;
    }
    var regex = new RegExp("^" + pattern + "$");

    var feedback = document.createElement("p");
    feedback.className = "help-block text-danger";
    feedback.style.display = "none";
    feedback.textContent = rex.mediapool_rename_validation_error || "";
    inputContainer.appendChild(feedback);

    function validate() {
        var val = input.value;
        if (val === "") {
            formGroup.classList.remove("has-error", "has-success");
            feedback.style.display = "none";
            return true;
        }
        if (regex.test(val)) {
            formGroup.classList.remove("has-error");
            formGroup.classList.add("has-success");
            feedback.style.display = "none";
            return true;
        }
        formGroup.classList.remove("has-success");
        formGroup.classList.add("has-error");
        feedback.style.display = "";
        return false;
    }

    input.addEventListener("input", validate);

    var form = input.closest("form");
    if (form) {
        form.addEventListener("submit", function (e) {
            if (!validate()) {
                e.preventDefault();
                input.focus();
            }
        });
    }
});
