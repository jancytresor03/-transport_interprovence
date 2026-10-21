
/* =========================================
   DONNEES FOURNIES PAR PHP
========================================= */

const donnees = window.reservationPageData || {};

const utilisateur = donnees.utilisateur || {};
const trajet = donnees.trajet || {};

const trajetId = trajet.id || "";
const passagers = Number(donnees.passagers) || 0;
const places = Array.isArray(donnees.places)
    ? donnees.places
    : [];

const prix = Number(trajet.prix) || 0;


/* =========================================
   ELEMENTS DU RESUME
========================================= */

const departElement = document.getElementById("depart");
const destinationElement = document.getElementById("destination");
const dateElement = document.getElementById("date");
const heureElement = document.getElementById("heure");
const passagersElement = document.getElementById("passagers");
const placesElement = document.getElementById("places");
const prixUnitaireElement = document.getElementById("prixUnitaire");
const totalElement = document.getElementById("total");


/* =========================================
   ELEMENTS DU FORMULAIRE
========================================= */

const reservationForm = document.getElementById("reservationForm");

const nomInput = document.getElementById("nom");
const prenomInput = document.getElementById("prenom");
const telephoneInput = document.getElementById("telephone");
const emailInput = document.getElementById("email");
const identiteInput = document.getElementById("identite");
const numeroIdentiteInput = document.getElementById("numeroIdentite");
const remarqueInput = document.getElementById("remarque");
const conditionsInput = document.getElementById("conditions");

const backButton = document.getElementById("backButton");


/* =========================================
   FORMATAGE DES PRIX
========================================= */

function formaterPrix(montant) {
    return montant.toLocaleString("fr-FR") + " FBu";
}


/* =========================================
   AFFICHER LE RESUME
========================================= */

function afficherResume() {

    if (!trajet.id) {
        return;
    }

    departElement.textContent = trajet.depart || "-";

    destinationElement.textContent =
        trajet.destination || "-";

    heureElement.textContent =
        trajet.heure || "-";

    passagersElement.textContent =
        passagers + " passager(s)";

    placesElement.textContent =
        places.length > 0
            ? places.join(", ")
            : "-";

    if (trajet.date) {

        const dateObj = new Date(
            trajet.date + "T00:00:00"
        );

        dateElement.textContent =
            dateObj.toLocaleDateString("fr-FR");

    } else {

        dateElement.textContent = "-";

    }

    prixUnitaireElement.textContent =
        formaterPrix(prix);

    totalElement.textContent =
        formaterPrix(prix * passagers);
}


/* =========================================
   AFFICHER UNE ERREUR
========================================= */

function afficherErreur(input, errorId, message) {

    if (input) {
        input.classList.add("input-error");
        input.setAttribute("aria-invalid", "true");
    }

    const elementErreur = document.getElementById(errorId);

    if (elementErreur) {
        elementErreur.textContent = message;
    }
}


/* =========================================
   SUPPRIMER UNE ERREUR
========================================= */

function supprimerErreur(input, errorId) {

    if (input) {
        input.classList.remove("input-error");
        input.removeAttribute("aria-invalid");
    }

    const elementErreur = document.getElementById(errorId);

    if (elementErreur) {
        elementErreur.textContent = "";
    }
}


/* =========================================
   VALIDATION DU FORMULAIRE
========================================= */

function validerFormulaire() {

    let valide = true;

    // Nom
    if (!nomInput.value.trim()) {

        afficherErreur(
            nomInput,
            "nomError",
            "Veuillez entrer votre nom."
        );

        valide = false;

    } else {

        supprimerErreur(nomInput, "nomError");

    }


    // Prénom
    if (!prenomInput.value.trim()) {

        afficherErreur(
            prenomInput,
            "prenomError",
            "Veuillez entrer votre prénom."
        );

        valide = false;

    } else {

        supprimerErreur(prenomInput, "prenomError");

    }


    // Téléphone
    const telephone = telephoneInput.value.trim();

    const telephoneNettoye = telephone.replace(/[\s()+.-]/g, "");

    if (!telephone) {

        afficherErreur(
            telephoneInput,
            "telephoneError",
            "Veuillez entrer votre numéro de téléphone."
        );

        valide = false;

    } else if (!/^\d{8,15}$/.test(telephoneNettoye)) {

        afficherErreur(
            telephoneInput,
            "telephoneError",
            "Veuillez entrer un numéro valide."
        );

        valide = false;

    } else {

        supprimerErreur(telephoneInput, "telephoneError");

    }


    // E-mail facultatif
    const email = emailInput.value.trim();

    const emailCorrect =
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (email && !emailCorrect.test(email)) {

        afficherErreur(
            emailInput,
            "emailError",
            "Veuillez entrer une adresse e-mail valide."
        );

        valide = false;

    } else {

        supprimerErreur(emailInput, "emailError");

    }


    // Type de pièce d'identité
    if (!identiteInput.value) {

        afficherErreur(
            identiteInput,
            "identiteError",
            "Veuillez sélectionner une pièce d'identité."
        );

        valide = false;

    } else {

        supprimerErreur(identiteInput, "identiteError");

    }


    // Numéro de pièce d'identité
    if (!numeroIdentiteInput.value.trim()) {

        afficherErreur(
            numeroIdentiteInput,
            "numeroIdentiteError",
            "Veuillez entrer le numéro de la pièce."
        );

        valide = false;

    } else {

        supprimerErreur(
            numeroIdentiteInput,
            "numeroIdentiteError"
        );

    }


// Conditions de réservation
const conditionsError =
    document.getElementById("conditionsError");

if (!conditionsInput.checked) {

    conditionsError.textContent =
        "Vous devez accepter les conditions de réservation.";

    valide = false;

} else {

    conditionsError.textContent = "";

}


    // Vérifier les données du trajet
    if (
        !trajetId ||
        passagers < 1 ||
        places.length !== passagers
    ) {

        alert(
            "Les informations du trajet ou les sièges sont invalides. "
            + "Veuillez recommencer la sélection."
        );

        valide = false;

    }

    return valide;
}


/* =========================================
   EFFACER LES ERREURS PENDANT LA SAISIE
========================================= */

const champsAvecErreurs = [
    [nomInput, "nomError"],
    [prenomInput, "prenomError"],
    [telephoneInput, "telephoneError"],
    [emailInput, "emailError"],
    [identiteInput, "identiteError"],
    [numeroIdentiteInput, "numeroIdentiteError"]
];

champsAvecErreurs.forEach(function ([input, errorId]) {

    if (!input) {
        return;
    }

    input.addEventListener("input", function () {
        supprimerErreur(input, errorId);
    });

    input.addEventListener("change", function () {
        supprimerErreur(input, errorId);
    });

});

conditionsInput.addEventListener("change", function () {
    document.getElementById("conditionsError").textContent = "";
});


/* =========================================
   PREPARER LA SOUMISSION
========================================= */

if (reservationForm) {

    reservationForm.addEventListener("submit", function (event) {

        event.preventDefault();

        if (!validerFormulaire()) {
            return;
        }

        /*
         * Les données du formulaire seront envoyées
         * par POST à un script PHP.
         *
         * Le script PHP devra revérifier les sièges,
         * calculer le prix depuis la base de données
         * et enregistrer la réservation dans une
         * transaction SQL.
         */

        let champTrajet =
            reservationForm.querySelector('[name="trajet"]');

        if (!champTrajet) {

            champTrajet = document.createElement("input");
            champTrajet.type = "hidden";
            champTrajet.name = "trajet";

            reservationForm.appendChild(champTrajet);
        }

        champTrajet.value = trajetId;


        let champPassagers =
            reservationForm.querySelector('[name="passagers"]');

        if (!champPassagers) {

            champPassagers = document.createElement("input");
            champPassagers.type = "hidden";
            champPassagers.name = "passagers";

            reservationForm.appendChild(champPassagers);
        }

        champPassagers.value = String(passagers);


        let champPlaces =
            reservationForm.querySelector('[name="places"]');

        if (!champPlaces) {

            champPlaces = document.createElement("input");
            champPlaces.type = "hidden";
            champPlaces.name = "places";

            reservationForm.appendChild(champPlaces);
        }

        champPlaces.value = places.join(",");


        // Les noms correspondent aux champs du formulaire.
        // Le serveur ne doit pas faire confiance au prix
        // ou aux informations du trajet envoyés par le navigateur.

        reservationForm.method = "POST";
        reservationForm.action = "reservation-traitement.php";
		
		console.log(
    "Conditions cochées :",
    conditionsInput.checked
);

console.log(
    "Valeur envoyée :",
    new FormData(reservationForm).get("conditions")
);

        reservationForm.submit();

    });

}


/* =========================================
   RETOUR AUX PLACES
========================================= */

if (backButton) {

    backButton.addEventListener("click", function () {

        if (!trajetId) {

            window.location.href = "trajets.php";

            return;
        }

        const nouveauxParametres = new URLSearchParams();

        nouveauxParametres.set("trajet", trajetId);
        nouveauxParametres.set("passagers", String(passagers));

        window.location.href =
            "places.php?" + nouveauxParametres.toString();

    });

}


/* =========================================
   MENU UTILISATEUR
========================================= */

const userButton = document.getElementById("userButton");
const userMenu = document.getElementById("userMenu");

if (userButton && userMenu) {

    userButton.addEventListener("click", function (event) {

        event.stopPropagation();

        const ouvert = userMenu.classList.toggle("active");

        userButton.setAttribute(
            "aria-expanded",
            String(ouvert)
        );

    });

    document.addEventListener("click", function (event) {

        if (
            !userButton.contains(event.target) &&
            !userMenu.contains(event.target)
        ) {

            userMenu.classList.remove("active");

            userButton.setAttribute("aria-expanded", "false");

        }

    });

}


/* =========================================
   DECONNEXION
========================================= */

const logoutButton = document.getElementById("logoutButton");

if (logoutButton) {

    logoutButton.addEventListener("click", function () {

        const confirmation = confirm(
            "Voulez-vous vraiment vous déconnecter ?"
        );

        if (confirmation) {
            window.location.href = "../logout.php";
        }

    });

}


/* =========================================
   INITIALISATION
========================================= */

afficherResume();

if (donnees.erreur) {

    console.error("Erreur de réservation :", donnees.erreur);

}
