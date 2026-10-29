
"use strict";

// ================= ÉLÉMENTS HTML =================

const reservationsContainer = document.getElementById(
    "reservationsContainer"
);

const emptyState = document.getElementById("emptyState");

const filterStatus = document.getElementById("filterStatus");

const newReservationButton = document.getElementById(
    "newReservationButton"
);

const emptyReservationButton = document.getElementById(
    "emptyReservationButton"
);

// ================= FILTRER LES RÉSERVATIONS =================

function filtrerReservations() {

    if (!reservationsContainer || !filterStatus) {
        return;
    }

    const statutChoisi = filterStatus.value;

    // Récupérer les cartes générées par PHP
    const cartes = reservationsContainer.querySelectorAll(
        ".reservation-card"
    );

    let nombreVisible = 0;

    cartes.forEach(function (carte) {

        // Lire le statut fourni par PHP
        const statut = carte.dataset.status;

        // Afficher toutes les réservations ou filtrer par statut
        const afficher =
            statutChoisi === "toutes" ||
            statut === statutChoisi;

        carte.style.display = afficher ? "" : "none";

        if (afficher) {
            nombreVisible++;
        }

    });

    // Afficher le message si aucune réservation ne correspond
    if (emptyState) {

        if (cartes.length === 0) {
            // Le PHP affiche déjà le message initial si la liste est vide.
            emptyState.style.display = "block";
        } else {
            emptyState.style.display =
                nombreVisible === 0 ? "block" : "none";
        }

    }

}

// ================= ÉVÉNEMENT DU FILTRE =================

if (filterStatus) {

    filterStatus.addEventListener(
        "change",
        filtrerReservations
    );

}

// ================= NOUVELLE RÉSERVATION =================

function allerVersRecherche() {

    window.location.href = "recherche.php";

}

if (newReservationButton) {

    newReservationButton.addEventListener(
        "click",
        allerVersRecherche
    );

}

if (emptyReservationButton) {

    emptyReservationButton.addEventListener(
        "click",
        allerVersRecherche
    );

}

// ================= MENU UTILISATEUR =================

const userButton = document.getElementById("userButton");

const userMenu = document.getElementById("userMenu");

if (userButton && userMenu) {

    userButton.addEventListener("click", function (event) {

        event.stopPropagation();

        userMenu.classList.toggle("active");

    });

    // Fermer le menu quand on clique ailleurs
    document.addEventListener("click", function (event) {

        if (
            !userButton.contains(event.target) &&
            !userMenu.contains(event.target)
        ) {

            userMenu.classList.remove("active");

        }

    });

}

// ================= INITIALISATION =================

// Les réservations et les statistiques sont chargées par PHP.
// JavaScript gère uniquement le filtre et les interactions.

filtrerReservations();
