
"use strict";

/*
=========================================
CONFIGURATION
=========================================
*/

// theme.js se trouve à la racine du projet.
// L'API se trouve dans le dossier administrateur.
const URL_API_PARAMETRES = new URL(
    "administrateur/parametres-api.php",
    document.currentScript.src
).href;

/*
=========================================
APPLIQUER LE THEME
=========================================
*/

async function appliquerThemeGlobal() {
    try {
        const reponse = await fetch(URL_API_PARAMETRES, {
            method: "GET",
            credentials: "same-origin",
            headers: {
                "Accept": "application/json"
            },
            cache: "no-store"
        });

        if (!reponse.ok) {
            throw new Error(
                "Erreur HTTP : " + reponse.status
            );
        }

        const resultat = await reponse.json();

        if (
            !resultat.succes ||
            !resultat.parametres
        ) {
            throw new Error(
                resultat.message || "Paramètres indisponibles."
            );
        }

        const theme = resultat.parametres.theme;

        document.body.classList.toggle(
            "dark-mode",
            theme === "sombre"
        );

        console.log("Thème récupéré depuis MySQL :", theme);

    } catch (erreur) {
        console.error(
            "Impossible de charger le thème depuis la base de données :",
            erreur
        );
    }
}

/*
=========================================
INITIALISATION
=========================================
*/

if (document.readyState === "loading") {
    document.addEventListener(
        "DOMContentLoaded",
        appliquerThemeGlobal
    );
} else {
    appliquerThemeGlobal();
}
