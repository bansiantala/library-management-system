document.addEventListener("DOMContentLoaded", function () {

    const body = document.body;
    const toggleButton = document.getElementById("adminThemeToggle");

    if (!toggleButton) {
        return;
    }


    /* =====================================================
       UPDATE BUTTON
    ====================================================== */

    function updateThemeButton() {

        const isDark =
            body.classList.contains("admin-dark-mode");

        if (isDark) {

            toggleButton.innerHTML =
                '<i class="bi bi-sun-fill"></i>';

            toggleButton.title =
                "Switch to Light Mode";

            toggleButton.setAttribute(
                "aria-label",
                "Switch to Light Mode"
            );

        } else {

            toggleButton.innerHTML =
                '<i class="bi bi-moon-fill"></i>';

            toggleButton.title =
                "Switch to Dark Mode";

            toggleButton.setAttribute(
                "aria-label",
                "Switch to Dark Mode"
            );
        }
    }


    /* =====================================================
       LOAD SAVED THEME
    ====================================================== */

    const savedTheme =
        localStorage.getItem("adminTheme");

    if (savedTheme === "dark") {

        body.classList.add(
            "admin-dark-mode"
        );

    }


    updateThemeButton();


    /* =====================================================
       TOGGLE THEME
    ====================================================== */

    toggleButton.addEventListener(
        "click",
        function () {

            body.classList.toggle(
                "admin-dark-mode"
            );


            const isDark =
                body.classList.contains(
                    "admin-dark-mode"
                );


            localStorage.setItem(
                "adminTheme",
                isDark ? "dark" : "light"
            );


            updateThemeButton();

        }
    );

});