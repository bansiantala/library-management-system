document.addEventListener("DOMContentLoaded", function () {

    const body = document.body;

    /*
    |--------------------------------------------------------------------------
    | FIND THEME BUTTON
    |--------------------------------------------------------------------------
    */

    const adminThemeButton =
        document.getElementById("adminThemeToggle");

    const userThemeButton =
        document.getElementById("darkModeToggle");


    /*
    |--------------------------------------------------------------------------
    | LOAD SAVED THEME
    |--------------------------------------------------------------------------
    */

    const savedTheme =
        localStorage.getItem("library_theme");


    /*
    |--------------------------------------------------------------------------
    | APPLY SAVED THEME
    |--------------------------------------------------------------------------
    */

    function applyTheme(theme) {

        if (theme === "dark") {

            body.classList.add("library-dark-mode");

        } else {

            body.classList.remove("library-dark-mode");

        }

        updateButtons();
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE BUTTON ICONS
    |--------------------------------------------------------------------------
    */

    function updateButtons() {

        const isDark =
            body.classList.contains("library-dark-mode");


        /*
        | ADMIN BUTTON
        */

        if (adminThemeButton) {

            if (isDark) {

                adminThemeButton.innerHTML =
                    '<i class="bi bi-sun-fill"></i>';

                adminThemeButton.title =
                    "Switch to Light Mode";

                adminThemeButton.setAttribute(
                    "aria-label",
                    "Switch to Light Mode"
                );

            } else {

                adminThemeButton.innerHTML =
                    '<i class="bi bi-moon-fill"></i>';

                adminThemeButton.title =
                    "Switch to Dark Mode";

                adminThemeButton.setAttribute(
                    "aria-label",
                    "Switch to Dark Mode"
                );
            }
        }


        /*
        | USER BUTTON
        */

        if (userThemeButton) {

            if (isDark) {

                userThemeButton.innerHTML =
                    '<i class="bi bi-sun-fill"></i>';

                userThemeButton.title =
                    "Switch to Light Mode";

                userThemeButton.setAttribute(
                    "aria-label",
                    "Switch to Light Mode"
                );

            } else {

                userThemeButton.innerHTML =
                    '<i class="bi bi-moon-fill"></i>';

                userThemeButton.title =
                    "Switch to Dark Mode";

                userThemeButton.setAttribute(
                    "aria-label",
                    "Switch to Dark Mode"
                );
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | LOAD INITIAL THEME
    |--------------------------------------------------------------------------
    */

    if (savedTheme === "dark") {

        body.classList.add(
            "library-dark-mode"
        );

    } else {

        body.classList.remove(
            "library-dark-mode"
        );
    }


    updateButtons();


    /*
    |--------------------------------------------------------------------------
    | ADMIN THEME BUTTON
    |--------------------------------------------------------------------------
    */

    if (adminThemeButton) {

        adminThemeButton.addEventListener(
            "click",
            function () {

                body.classList.toggle(
                    "library-dark-mode"
                );


                const isDark =
                    body.classList.contains(
                        "library-dark-mode"
                    );


                localStorage.setItem(
                    "library_theme",
                    isDark ? "dark" : "light"
                );


                updateButtons();

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | USER THEME BUTTON
    |--------------------------------------------------------------------------
    */

    if (userThemeButton) {

        userThemeButton.addEventListener(
            "click",
            function () {

                body.classList.toggle(
                    "library-dark-mode"
                );


                const isDark =
                    body.classList.contains(
                        "library-dark-mode"
                    );


                localStorage.setItem(
                    "library_theme",
                    isDark ? "dark" : "light"
                );


                updateButtons();

            }
        );
    }

});