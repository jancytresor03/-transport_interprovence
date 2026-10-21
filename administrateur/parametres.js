
/* =========================================
   CONFIGURATION
========================================= */

const API_PARAMETRES = "parametres-api.php";


/* =========================================
   PARAMETRES PAR DEFAUT
========================================= */

const parametresParDefaut = {
    nomEntreprise: "Burundi Transport",
    telephoneEntreprise: "+257 79 00 00 00",
    emailEntreprise: "contact@burunditransport.bi",
    adresseEntreprise: "Bujumbura",
    placesDefaut: 28,
    devise: "BIF",
    delaiReservation: "30",
    passagersMaximum: 5,
    notificationsReservation: true,
    notificationsEmail: true,
    alertesSysteme: true,
    theme: "clair",
    modeMaintenance: false
};


/* =========================================
   ELEMENTS HTML
========================================= */

const nomEntreprise = document.getElementById("nomEntreprise");
const telephoneEntreprise = document.getElementById("telephoneEntreprise");
const emailEntreprise = document.getElementById("emailEntreprise");
const adresseEntreprise = document.getElementById("adresseEntreprise");

const placesDefaut = document.getElementById("placesDefaut");
const devise = document.getElementById("devise");
const delaiReservation = document.getElementById("delaiReservation");
const passagersMaximum = document.getElementById("passagersMaximum");

const notificationsReservation = document.getElementById("notificationsReservation");
const notificationsEmail = document.getElementById("notificationsEmail");
const alertesSysteme = document.getElementById("alertesSysteme");

const themeClair = document.getElementById("themeClair");
const themeSombre = document.getElementById("themeSombre");

const modeMaintenance = document.getElementById("modeMaintenance");

const saveButton = document.getElementById("saveButton");
const resetButton = document.getElementById("resetButton");
const messageBox = document.getElementById("messageBox");

const logoutButton = document.getElementById("logoutButton");
const menuButton = document.getElementById("menuButton");
const sidebar = document.getElementById("sidebar");
const notificationBadge = document.getElementById("notificationBadge");


/* =========================================
   ETAT
========================================= */

let parametresActuels = { ...parametresParDefaut };
let chargementEnCours = false;
let sauvegardeEnCours = false;


/* =========================================
   COMMUNICATION AVEC L'API
========================================= */

async function envoyerRequete(url, options = {}) {

    const reponse = await fetch(url, {
        credentials: "same-origin",
        cache: "no-store",
        ...options,
        headers: {
            "Accept": "application/json",
            ...(options.headers || {})
        }
    });

    let resultat;

    try {
        resultat = await reponse.json();
    } catch (erreur) {
        throw new Error(
            "Le serveur n'a pas retourné une réponse JSON valide. Vérifie parametres-api.php."
        );
    }

    if (!reponse.ok || resultat.succes !== true) {
        throw new Error(
            resultat.message || "Une erreur est survenue."
        );
    }

    return resultat;
}


/* =========================================
   CHARGER LES PARAMETRES DEPUIS MYSQL
========================================= */

async function chargerParametres() {

    if (chargementEnCours) {
        return;
    }

    chargementEnCours = true;
    saveButton.disabled = true;
    resetButton.disabled = true;

    try {

        const resultat = await envoyerRequete(API_PARAMETRES);

        parametresActuels = {
            ...parametresParDefaut,
            ...(resultat.parametres || {})
        };

        afficherParametres(parametresActuels);

        afficherMessage(
            "Les paramètres ont été chargés depuis la base de données.",
            "success"
        );

    } catch (erreur) {

        console.error("Erreur de chargement :", erreur);

        afficherMessage(
            erreur.message + " Les valeurs affichées sont les valeurs par défaut.",
            "error"
        );

        parametresActuels = { ...parametresParDefaut };
        afficherParametres(parametresActuels);

    } finally {

        chargementEnCours = false;
        saveButton.disabled = false;
        resetButton.disabled = false;
    }
}


/* =========================================
   AFFICHER LES PARAMETRES DANS LE FORMULAIRE
========================================= */

function afficherParametres(parametres) {

    nomEntreprise.value = parametres.nomEntreprise ?? "";
    telephoneEntreprise.value = parametres.telephoneEntreprise ?? "";
    emailEntreprise.value = parametres.emailEntreprise ?? "";
    adresseEntreprise.value = parametres.adresseEntreprise ?? "";

    placesDefaut.value = parametres.placesDefaut ?? 28;
    devise.value = parametres.devise ?? "BIF";
    delaiReservation.value = String(parametres.delaiReservation ?? "30");
    passagersMaximum.value = parametres.passagersMaximum ?? 5;

    notificationsReservation.checked =
        parametres.notificationsReservation === true;

    notificationsEmail.checked =
        parametres.notificationsEmail === true;

    alertesSysteme.checked =
        parametres.alertesSysteme === true;

    modeMaintenance.checked =
        parametres.modeMaintenance === true;

    themeClair.checked = parametres.theme !== "sombre";
    themeSombre.checked = parametres.theme === "sombre";

    appliquerTheme(parametres.theme);
}


/* =========================================
   RECUPERER LES VALEURS DU FORMULAIRE
========================================= */

function recupererParametres() {

    return {

        nomEntreprise: nomEntreprise.value.trim(),

        telephoneEntreprise: telephoneEntreprise.value.trim(),

        emailEntreprise: emailEntreprise.value.trim(),

        adresseEntreprise: adresseEntreprise.value.trim(),

        placesDefaut: Number(placesDefaut.value),

        devise: devise.value,

        delaiReservation: delaiReservation.value,

        passagersMaximum: Number(passagersMaximum.value),

        notificationsReservation: notificationsReservation.checked,

        notificationsEmail: notificationsEmail.checked,

        alertesSysteme: alertesSysteme.checked,

        theme: themeSombre.checked ? "sombre" : "clair",

        modeMaintenance: modeMaintenance.checked
    };
}


/* =========================================
   VERIFIER LES VALEURS
========================================= */

function verifierParametres(parametres) {

    if (parametres.nomEntreprise === "") {

        afficherMessage(
            "Veuillez entrer le nom de l'entreprise.",
            "error"
        );

        nomEntreprise.focus();
        return false;
    }

    if (
        parametres.emailEntreprise === "" ||
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(parametres.emailEntreprise)
    ) {

        afficherMessage(
            "Veuillez entrer une adresse email valide.",
            "error"
        );

        emailEntreprise.focus();
        return false;
    }

    if (
        !Number.isInteger(parametres.placesDefaut) ||
        parametres.placesDefaut < 1 ||
        parametres.placesDefaut > 100
    ) {

        afficherMessage(
            "Le nombre de places doit être compris entre 1 et 100.",
            "error"
        );

        placesDefaut.focus();
        return false;
    }

    if (
        !Number.isInteger(parametres.passagersMaximum) ||
        parametres.passagersMaximum < 1 ||
        parametres.passagersMaximum > 20
    ) {

        afficherMessage(
            "Le nombre maximum de passagers doit être compris entre 1 et 20.",
            "error"
        );

        passagersMaximum.focus();
        return false;
    }

    if (!["BIF", "USD"].includes(parametres.devise)) {

        afficherMessage("Veuillez choisir une devise valide.", "error");
        return false;
    }

    if (!["15", "30", "60", "120"].includes(parametres.delaiReservation)) {

        afficherMessage("Veuillez choisir un délai valide.", "error");
        return false;
    }

    return true;
}


/* =========================================
   ENREGISTRER DANS MYSQL
========================================= */

async function enregistrerParametres() {

    if (sauvegardeEnCours || chargementEnCours) {
        return;
    }

    const parametres = recupererParametres();

    if (!verifierParametres(parametres)) {
        return;
    }

    sauvegardeEnCours = true;
    saveButton.disabled = true;
    resetButton.disabled = true;

    const texteOriginal = saveButton.innerHTML;

    saveButton.innerHTML =
        '<i class="fa-solid fa-spinner fa-spin"></i> Enregistrement...';

    try {

        const resultat = await envoyerRequete(API_PARAMETRES, {

            method: "POST",

            headers: {
                "Content-Type": "application/json"
            },

            body: JSON.stringify({
                action: "save",
                parametres: parametres
            })
        });

        parametresActuels = {
            ...parametresParDefaut,
            ...(resultat.parametres || parametres)
        };

        afficherParametres(parametresActuels);

        afficherMessage(
            resultat.message || "Les paramètres ont été enregistrés avec succès.",
            "success"
        );

    } catch (erreur) {

        console.error("Erreur d'enregistrement :", erreur);

        afficherMessage(
            erreur.message || "Impossible d'enregistrer les paramètres.",
            "error"
        );

    } finally {

        sauvegardeEnCours = false;
        saveButton.disabled = false;
        resetButton.disabled = false;
        saveButton.innerHTML = texteOriginal;
    }
}


/* =========================================
   REINITIALISER DANS MYSQL
========================================= */

async function reinitialiserParametres() {

    if (sauvegardeEnCours || chargementEnCours) {
        return;
    }

    const confirmation = confirm(
        "Voulez-vous vraiment réinitialiser tous les paramètres aux valeurs par défaut ?"
    );

    if (!confirmation) {
        return;
    }

    sauvegardeEnCours = true;
    saveButton.disabled = true;
    resetButton.disabled = true;

    const texteOriginal = resetButton.innerHTML;

    resetButton.innerHTML =
        '<i class="fa-solid fa-spinner fa-spin"></i> Réinitialisation...';

    try {

        const resultat = await envoyerRequete(API_PARAMETRES, {

            method: "POST",

            headers: {
                "Content-Type": "application/json"
            },

            body: JSON.stringify({
                action: "reset"
            })
        });

        parametresActuels = {
            ...parametresParDefaut,
            ...(resultat.parametres || {})
        };

        afficherParametres(parametresActuels);

        afficherMessage(
            resultat.message || "Les paramètres ont été réinitialisés.",
            "success"
        );

    } catch (erreur) {

        console.error("Erreur de réinitialisation :", erreur);

        afficherMessage(
            erreur.message || "Impossible de réinitialiser les paramètres.",
            "error"
        );

    } finally {

        sauvegardeEnCours = false;
        saveButton.disabled = false;
        resetButton.disabled = false;
        resetButton.innerHTML = texteOriginal;
    }
}


/* =========================================
   THEME CLAIR / SOMBRE
========================================= */

function appliquerTheme(theme) {

    document.body.classList.toggle(
        "dark-mode",
        theme === "sombre"
    );
}

themeClair.addEventListener("change", function () {

    if (themeClair.checked) {
        appliquerTheme("clair");
    }
});

themeSombre.addEventListener("change", function () {

    if (themeSombre.checked) {
        appliquerTheme("sombre");
    }
});


/* =========================================
   AFFICHER UN MESSAGE
========================================= */

function afficherMessage(message, type) {

    messageBox.textContent = message;

    messageBox.className = "message-box";
    messageBox.style.display = "block";
    messageBox.style.background = "";
    messageBox.style.color = "";
    messageBox.style.border = "";

    if (type === "success") {

        messageBox.classList.add("success");

    } else {

        messageBox.classList.add("error");
    }
}


/* =========================================
   COMPTEUR DE NOTIFICATIONS
========================================= */

function afficherNombreNotifications() {

    if (!notificationBadge) {
        return;
    }

    try {

        const donnees = localStorage.getItem(
            "burundiTransportNotifications"
        );

        const notifications = donnees ? JSON.parse(donnees) : [];

        if (!Array.isArray(notifications)) {
            notificationBadge.textContent = "0";
            return;
        }

        const nonLues = notifications.filter(function (notification) {
            return notification.lue === false;
        });

        notificationBadge.textContent = nonLues.length;

    } catch (erreur) {

        console.error("Erreur de lecture des notifications :", erreur);
        notificationBadge.textContent = "0";
    }
}


/* =========================================
   MENU MOBILE
========================================= */

if (menuButton && sidebar) {

    menuButton.addEventListener("click", function () {

        sidebar.classList.toggle("open");

    });
}


/* =========================================
   DECONNEXION
========================================= */

if (logoutButton) {

    logoutButton.addEventListener("click", function (evenement) {

        const lien = logoutButton.querySelector("a");

        if (!lien) {
            return;
        }

        evenement.preventDefault();

        const confirmation = confirm(
            "Voulez-vous vraiment vous déconnecter ?"
        );

        if (confirmation) {
            window.location.href = lien.href;
        }
    });
}


/* =========================================
   BOUTONS
========================================= */

saveButton.addEventListener(
    "click",
    enregistrerParametres
);

resetButton.addEventListener(
    "click",
    reinitialiserParametres
);


/* =========================================
   INITIALISATION
========================================= */

messageBox.style.display = "none";

afficherNombreNotifications();

chargerParametres();
