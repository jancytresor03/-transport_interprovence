
/* =========================
   RECUPERATION DES ELEMENTS
========================= */

const userButton = document.getElementById("userButton");
const userDropdown = document.getElementById("userDropdown");
const logoutButton = document.getElementById("logoutButton");

const startSearchButton = document.getElementById("startSearchButton");
const searchForm = document.getElementById("searchForm");

const departure = document.getElementById("departure");
const destination = document.getElementById("destination");
const date = document.getElementById("date");
const passengers = document.getElementById("passengers");

const searchMessage = document.getElementById("searchMessage");


/* =========================
   MENU DU CLIENT
========================= */

userButton.addEventListener("click", function () {

    const menuOuvert = !userDropdown.hidden;

    userDropdown.hidden = menuOuvert;

    userButton.setAttribute(
        "aria-expanded",
        String(!menuOuvert)
    );

});


// Fermer le menu si on clique ailleurs
document.addEventListener("click", function (event) {

    const clicDansMenu = userButton.contains(event.target);
    const clicDansDropdown = userDropdown.contains(event.target);

    if (!clicDansMenu && !clicDansDropdown) {

        userDropdown.hidden = true;

        userButton.setAttribute("aria-expanded", "false");

    }

});


/* =========================
   BOUTON DE RECHERCHE
========================= */

startSearchButton.addEventListener("click", function () {

    document.getElementById("searchSection").scrollIntoView({
        behavior: "smooth"
    });

});


/* =========================
   DATE MINIMALE
========================= */

// Obtenir la date locale au format AAAA-MM-JJ
const maintenant = new Date();

const annee = maintenant.getFullYear();

const mois = String(
    maintenant.getMonth() + 1
).padStart(2, "0");

const jour = String(
    maintenant.getDate()
).padStart(2, "0");

const dateMinimum = `${annee}-${mois}-${jour}`;

date.min = dateMinimum;


/* =========================
   AFFICHER UN MESSAGE
========================= */

function afficherErreur(message) {

    searchMessage.textContent = message;

    searchMessage.style.color = "red";

}

function effacerMessage() {

    searchMessage.textContent = "";

}


/* =========================
   FORMULAIRE DE RECHERCHE
========================= */

searchForm.addEventListener("submit", function (event) {

    // Empêcher le formulaire de se soumettre directement
    event.preventDefault();

    effacerMessage();

    const villeDepart = departure.value;
    const villeDestination = destination.value;
    const dateVoyage = date.value;
    const nombrePassagers = passengers.value;


    // Vérifier le départ
    if (villeDepart === "") {

        afficherErreur("Veuillez choisir une ville de départ.");

        departure.focus();

        return;

    }


    // Vérifier la destination
    if (villeDestination === "") {

        afficherErreur("Veuillez choisir une destination.");

        destination.focus();

        return;

    }


    // Vérifier que les deux villes sont différentes
    if (villeDepart === villeDestination) {

        afficherErreur(
            "La ville de départ et la destination doivent être différentes."
        );

        destination.focus();

        return;

    }


    // Vérifier la date
    if (dateVoyage === "") {

        afficherErreur("Veuillez choisir une date de voyage.");

        date.focus();

        return;

    }


    // Empêcher une date antérieure à aujourd'hui
    if (dateVoyage < dateMinimum) {

        afficherErreur(
            "La date de voyage ne peut pas être dans le passé."
        );

        date.focus();

        return;

    }


    // Vérifier le nombre de passagers
    if (
        nombrePassagers === "" ||
        Number(nombrePassagers) < 1
    ) {

        afficherErreur(
            "Veuillez choisir un nombre valide de passagers."
        );

        passengers.focus();

        return;

    }


    /* =========================
       PREPARER LES PARAMETRES
    ========================= */

    const parametres = new URLSearchParams();

    parametres.set("depart", villeDepart);
    parametres.set("destination", villeDestination);
    parametres.set("date", dateVoyage);
    parametres.set("passagers", nombrePassagers);


    /* =========================
       REDIRECTION VERS RECHERCHE.PHP
    ========================= */

    window.location.href =
        "recherche.php?" + parametres.toString();

});


/* =========================
   DECONNEXION
========================= */

logoutButton.addEventListener("click", function () {

    const confirmation = confirm(
        "Voulez-vous vraiment vous déconnecter ?"
    );

    if (confirmation) {

        // Le fichier PHP doit détruire la session
        window.location.href = "../logout.php";

    }

});
