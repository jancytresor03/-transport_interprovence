
"use strict";

/* =========================================
   DÉCONNEXION AUTOMATIQUE DU CLIENT
   Délai : 5 minutes d'inactivité
========================================= */

const DELAI_INACTIVITE = 1 * 60 * 1000;
const URL_DECONNEXION = "deconnexion-auto.php";

let minuterieInactivite;
let derniereSynchronisation = 0;
let requeteEnCours = false;
let sessionExpiree = false;

/* =========================================
   1. REDIRIGER VERS LA CONNEXION
========================================= */

function redirigerConnexion(message) {
    if (sessionExpiree) {
        return;
    }

    sessionExpiree = true;
    clearTimeout(minuterieInactivite);

    if (message) {
        alert(message);
    }

    window.location.replace("../login.html");
}

/* =========================================
   2. INFORMER LE SERVEUR DE L'ACTIVITÉ
========================================= */

async function signalerActivite() {
    if (sessionExpiree || requeteEnCours) {
        return;
    }

    const maintenant = Date.now();

    // Éviter d'envoyer une requête à chaque mouvement de souris.
    if (maintenant - derniereSynchronisation < 15000) {
        return;
    }

    derniereSynchronisation = maintenant;
    requeteEnCours = true;

    try {
        const reponse = await fetch(URL_DECONNEXION, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({
                action: "activite"
            })
        });

        const donnees = await reponse.json();

        if (reponse.status === 401 || donnees.expiree) {
            redirigerConnexion(
                "Votre session a expiré. Veuillez vous reconnecter."
            );
        } else if (!reponse.ok || !donnees.succes) {
            console.error(
                "Impossible de vérifier la session du client."
            );
        }
    } catch (erreur) {
        console.error(
            "Erreur de communication avec le serveur :",
            erreur
        );
    } finally {
        requeteEnCours = false;
    }
}

/* =========================================
   3. DÉMARRER LE COMPTEUR
========================================= */

function reinitialiserCompteur() {
    if (sessionExpiree) {
        return;
    }

    clearTimeout(minuterieInactivite);

    minuterieInactivite = setTimeout(function () {
        deconnecterClient();
    }, DELAI_INACTIVITE);
}

/* =========================================
   4. DEMANDER LA DÉCONNEXION AU SERVEUR
========================================= */

async function deconnecterClient() {
    if (sessionExpiree) {
        return;
    }

    sessionExpiree = true;
    clearTimeout(minuterieInactivite);

    try {
        await fetch(URL_DECONNEXION, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({
                action: "deconnexion"
            })
        });
    } catch (erreur) {
        console.error(
            "Erreur lors de la demande de déconnexion :",
            erreur
        );
    }

    // Redirection directe, sans message ni confirmation.
    window.location.replace("../login.html");
}

/* =========================================
   5. DÉTECTER LES ACTIONS DU CLIENT
========================================= */

const evenementsActivite = [
    "mousemove",
    "mousedown",
    "keydown",
    "scroll",
    "touchstart",
    "click"
];

evenementsActivite.forEach(function (evenement) {
    document.addEventListener(evenement, function () {
        reinitialiserCompteur();
        signalerActivite();
    }, { passive: true });
});

/* =========================================
   6. DÉMARRAGE
========================================= */

reinitialiserCompteur();
signalerActivite();
