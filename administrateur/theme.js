/* =========================================
   THEME GLOBAL ADMINISTRATEUR
========================================= */

const CLE_THEME = "burundiTransportParametres";


function obtenirTheme() {

    const parametresSauvegardes =
        localStorage.getItem(CLE_THEME);

    if (!parametresSauvegardes) {
        return "clair";
    }

    try {

        const parametres =
            JSON.parse(parametresSauvegardes);

        return parametres.theme || "clair";

    } catch (erreur) {

        return "clair";
    }
}


function appliquerThemeGlobal() {

    const theme = obtenirTheme();

    if (theme === "sombre") {

        document.body.classList.add("dark-mode");

    } else {

        document.body.classList.remove("dark-mode");
    }
}


/* Appliquer le thème dès l'ouverture de la page */

appliquerThemeGlobal();