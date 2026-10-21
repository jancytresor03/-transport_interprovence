
/* ================================
   RECUPERATION DES ELEMENTS
================================ */

const userButton = document.getElementById("userButton");
const userDropdown = document.getElementById("userDropdown");
const logoutButton = document.getElementById("logoutButton");

const searchForm = document.getElementById("searchForm");

const departure = document.getElementById("departure");
const destination = document.getElementById("destination");
const date = document.getElementById("date");
const passengers = document.getElementById("passengers");

const departureError = document.getElementById("departureError");
const destinationError = document.getElementById("destinationError");
const dateError = document.getElementById("dateError");

const searchMessage = document.getElementById("searchMessage");


/* ================================
   MENU UTILISATEUR
================================ */

userButton.addEventListener("click", function () {

    const menuOuvert = !userDropdown.hidden;

    userDropdown.hidden = menuOuvert;

    userDropdown.classList.toggle("show", !menuOuvert);

    userButton.setAttribute(
        "aria-expanded",
        String(!menuOuvert)
    );

});


// Fermer le menu en cliquant ailleurs
document.addEventListener("click", function (event) {

    if (
        !userButton.contains(event.target) &&
        !userDropdown.contains(event.target)
    ) {

        userDropdown.hidden = true;
        userDropdown.classList.remove("show");

        userButton.setAttribute("aria-expanded", "false");

    }

});


/* ================================
   DATE MINIMUM
================================ */

// Construire la date locale au format AAAA-MM-JJ
const aujourdHui = new Date();

const annee = aujourdHui.getFullYear();

const mois = String(
    aujourdHui.getMonth() + 1
).padStart(2, "0");

const jour = String(
    aujourdHui.getDate()
).padStart(2, "0");

const dateMinimum = `${annee}-${mois}-${jour}`;

date.min = dateMinimum;


/* ================================
   RECUPERER LES PARAMETRES URL
================================ */

const parametres = new URLSearchParams(
    window.location.search
);

const departParam = parametres.get("depart");
const destinationParam = parametres.get("destination");
const dateParam = parametres.get("date");
const passagersParam = parametres.get("passagers");


/* ================================
   REMPLIR LE FORMULAIRE
================================ */

if (departParam) {
    departure.value = departParam;
}

if (destinationParam) {
    destination.value = destinationParam;
}

if (dateParam) {
    date.value = dateParam;
}

if (
    passagersParam &&
    ["1", "2", "3", "4", "5"].includes(passagersParam)
) {
    passengers.value = passagersParam;
}


/* ================================
   EFFACER LES ERREURS
================================ */

function effacerErreurs() {

    departureError.textContent = "";
    destinationError.textContent = "";
    dateError.textContent = "";

    searchMessage.textContent = "";
    searchMessage.className = "search-message";

}


/* ================================
   FORMULAIRE DE RECHERCHE
================================ */

searchForm.addEventListener("submit", function (event) {

    event.preventDefault();

    effacerErreurs();

    // Recuperer les valeurs
    const departValue = departure.value;
    const destinationValue = destination.value;
    const dateValue = date.value;
    const passagersValue = passengers.value;

    let erreur = false;


    /* ================================
       VERIFIER LE DEPART
    ================================ */

    if (departValue === "") {

        departureError.textContent =
            "Veuillez choisir la ville de départ.";

        erreur = true;

    }


    /* ================================
       VERIFIER LA DESTINATION
    ================================ */

    if (destinationValue === "") {

        destinationError.textContent =
            "Veuillez choisir la destination.";

        erreur = true;

    }


    /* ================================
       DEPART ET DESTINATION DIFFERENTS
    ================================ */

    if (
        departValue !== "" &&
        destinationValue !== "" &&
        departValue === destinationValue
    ) {

        destinationError.textContent =
            "La destination doit être différente du départ.";

        erreur = true;

    }


    /* ================================
       VERIFIER LA DATE
    ================================ */

    if (dateValue === "") {

        dateError.textContent =
            "Veuillez choisir une date.";

        erreur = true;

    } else if (dateValue < dateMinimum) {

        dateError.textContent =
            "La date ne peut pas être dans le passé.";

        erreur = true;

    }


    /* ================================
       VERIFIER LES PASSAGERS
    ================================ */

    if (
        !["1", "2", "3", "4", "5"].includes(passagersValue)
    ) {

        searchMessage.textContent =
            "Veuillez choisir un nombre valide de passagers.";

        erreur = true;

    }


    // Arreter si un champ est invalide
    if (erreur) {
        return;
    }


    /* ================================
       PREPARER LES PARAMETRES
    ================================ */

    const nouveauxParametres = new URLSearchParams();

    nouveauxParametres.set("depart", departValue);
    nouveauxParametres.set("destination", destinationValue);
    nouveauxParametres.set("date", dateValue);
    nouveauxParametres.set("passagers", passagersValue);


    /* ================================
       ALLER VERS LA PAGE DES TRAJETS
    ================================ */

    window.location.href =
        "trajets.php?" + nouveauxParametres.toString();

});


/* ================================
   DECONNEXION
================================ */

logoutButton.addEventListener("click", function () {

    const confirmer = confirm(
        "Voulez-vous vraiment vous déconnecter ?"
    );

    if (confirmer) {

        window.location.href = "../logout.php";

    }

});
