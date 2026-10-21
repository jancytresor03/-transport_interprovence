
/* ================= DONNEES DU TRAJET ================= */

// Les données proviennent de places.php et de MySQL.

const donnees = window.placesPageData;

const erreurPage = window.placesPageError || "";

const parametres = new URLSearchParams(
    window.location.search
);

const trajetId = donnees ? donnees.trajetId : "";

const depart = donnees ? donnees.depart : "";

const destination = donnees ? donnees.destination : "";

const date = donnees ? donnees.date : "";

const heure = donnees ? donnees.heure : "";

const prix = donnees ? Number(donnees.prix) : 0;

const passagers = donnees ? Number(donnees.passagers) : 1;

const sieges = donnees ? donnees.sieges : [];


/* ================= ELEMENTS HTML ================= */

const departElement = document.getElementById("depart");

const destinationElement = document.getElementById("destination");

const dateElement = document.getElementById("date");

const heureDepartElement = document.getElementById("heureDepart");

const passagersElement = document.getElementById("passagers");

const prixElement = document.getElementById("prix");

const seatsContainer = document.getElementById("seatsContainer");

const selectedSeatsElement = document.getElementById("selectedSeats");

const passengerNumberElement = document.getElementById("passengerNumber");

const pricePerSeatElement = document.getElementById("pricePerSeat");

const totalPriceElement = document.getElementById("totalPrice");

const continueButton = document.getElementById("continueButton");

const backButton = document.getElementById("backButton");

const pageMessage = document.getElementById("pageMessage");


/* ================= PLACES SELECTIONNEES ================= */

let placesSelectionnees = [];


/* ================= AFFICHER UN MESSAGE ================= */

function afficherMessage(message) {

    if (pageMessage) {
        pageMessage.textContent = message;
        pageMessage.hidden = false;
    }

}


/* ================= FORMAT PRIX ================= */

function formaterPrix(montant) {

    return montant.toLocaleString("fr-FR") + " FBu";

}


/* ================= AFFICHER INFORMATIONS TRAJET ================= */

function afficherInfosTrajet() {

    if (!donnees) {
        return;
    }

    departElement.textContent = depart;

    destinationElement.textContent = destination;

    heureDepartElement.textContent = heure;

    passagersElement.textContent =
        passagers + " passager(s)";

    if (date) {

        const dateObj = new Date(date + "T00:00:00");

        dateElement.textContent =
            dateObj.toLocaleDateString("fr-FR");

    } else {

        dateElement.textContent = "-";

    }

    prixElement.textContent = formaterPrix(prix);

    pricePerSeatElement.textContent = formaterPrix(prix);

}


/* ================= CREER LES 35 SIEGES ================= */

function afficherPlaces() {

    seatsContainer.innerHTML = "";

    if (!donnees) {

        continueButton.disabled = true;

        return;

    }

    /*
       Les données des sièges viennent de MySQL.
       Nous affichons les numéros de 1 à 35.
    */

    for (let numero = 1; numero <= 35; numero++) {

        /*
           Ajouter un espace central tous les quatre sièges.
        */

        if (
            numero > 1 &&
            (numero - 1) % 4 === 0
        ) {

            const aisle = document.createElement("div");

            aisle.className = "aisle";

            seatsContainer.appendChild(aisle);

        }

        const seat = document.createElement("button");

        seat.type = "button";

        seat.className = "seat";

        seat.textContent = numero;

        /*
           Rechercher ce siège dans les données reçues de PHP.
        */

        const siege = sieges.find(function (element) {

            return Number(element.numero) === numero;

        });

        /*
           Si le siège n'existe pas dans la base,
           il ne doit pas être sélectionnable.
        */

        if (!siege) {

            seat.classList.add("occupied");

            seat.disabled = true;

            seat.title = "Siège indisponible";

        } else if (siege.statut !== "disponible") {

            /*
               Un siège réservé ou bloqué est désactivé.
            */

            seat.classList.add("occupied");

            seat.disabled = true;

            seat.title = "Siège occupé ou bloqué";

        } else {

            /*
               Le siège est disponible.
            */

            seat.title = "Siège " + numero + " disponible";

            seat.addEventListener("click", function () {

                selectionnerPlace(numero, seat);

            });

        }

        seatsContainer.appendChild(seat);

    }

}


/* ================= SELECTIONNER UNE PLACE ================= */

function selectionnerPlace(numero, element) {

    /*
       Vérifier si la place est déjà sélectionnée.
    */

    const index = placesSelectionnees.indexOf(numero);

    if (index !== -1) {

        placesSelectionnees.splice(index, 1);

        element.classList.remove("selected");

        mettreAJourResume();

        return;

    }

    /*
       Limiter le nombre de sièges au nombre de passagers.
    */

    if (placesSelectionnees.length >= passagers) {

        alert(
            "Vous devez sélectionner seulement " +
            passagers +
            " place(s)."
        );

        return;

    }

    /*
       Ajouter la place à la sélection.
    */

    placesSelectionnees.push(numero);

    element.classList.add("selected");

    mettreAJourResume();

}


/* ================= METTRE A JOUR LE RESUME ================= */

function mettreAJourResume() {

    placesSelectionnees.sort(function (a, b) {

        return a - b;

    });

    /*
       Afficher les numéros sélectionnés.
    */

    if (placesSelectionnees.length === 0) {

        selectedSeatsElement.textContent = "Aucune";

    } else {

        selectedSeatsElement.textContent =
            placesSelectionnees.join(", ");

    }

    /*
       Afficher le nombre de places sélectionnées.
    */

    passengerNumberElement.textContent =
        placesSelectionnees.length;

    /*
       Calculer le prix total.
    */

    const total = placesSelectionnees.length * prix;

    totalPriceElement.textContent = formaterPrix(total);

    /*
       Autoriser la suite uniquement si le nombre demandé
       de sièges a été sélectionné.
    */

    continueButton.disabled = !(
        donnees &&
        !erreurPage &&
        placesSelectionnees.length === passagers
    );

}


/* ================= CONTINUER VERS LA RESERVATION ================= */

continueButton.addEventListener("click", function () {

    if (!donnees || erreurPage) {

        afficherMessage(
            "Impossible de continuer. Veuillez vérifier le trajet."
        );

        return;

    }

    if (placesSelectionnees.length !== passagers) {

        alert(
            "Veuillez sélectionner " +
            passagers +
            " place(s)."
        );

        return;

    }

    /*
       Transmettre uniquement les identifiants nécessaires.
       reservation.php devra récupérer et vérifier les
       informations réelles depuis MySQL.
    */

    const nouveauxParametres = new URLSearchParams();

    nouveauxParametres.set("trajet", trajetId);

    nouveauxParametres.set("passagers", String(passagers));

    nouveauxParametres.set(
        "places",
        placesSelectionnees.join(",")
    );

    window.location.href =
        "reservation.php?" + nouveauxParametres.toString();

});


/* ================= RETOUR AUX TRAJETS ================= */

backButton.addEventListener("click", function () {

    const nouveauxParametres = new URLSearchParams();

    nouveauxParametres.set("trajet", trajetId);

    nouveauxParametres.set("passagers", String(passagers));

    if (depart) {
        nouveauxParametres.set("depart", depart);
    }

    if (destination) {
        nouveauxParametres.set("destination", destination);
    }

    if (date) {
        nouveauxParametres.set("date", date);
    }

    window.location.href =
        "trajets.php?" + nouveauxParametres.toString();

});


/* ================= MENU UTILISATEUR ================= */

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


/* ================= DECONNEXION ================= */

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


/* ================= INITIALISATION ================= */

if (erreurPage || !donnees) {

    continueButton.disabled = true;

    seatsContainer.innerHTML = "";

    if (erreurPage && pageMessage && pageMessage.hidden) {

        afficherMessage(erreurPage);

    }

} else {

    afficherInfosTrajet();

    afficherPlaces();

    mettreAJourResume();

}
