
"use strict";

// ================= MENU UTILISATEUR =================

const userButton = document.getElementById("userButton");
const userMenu = document.getElementById("userMenu");

if (userButton && userMenu) {

    userButton.addEventListener("click", function (event) {

        event.stopPropagation();

        userMenu.classList.toggle("active");

    });

    // Fermer le menu si on clique ailleurs
    document.addEventListener("click", function (event) {

        if (
            !userButton.contains(event.target) &&
            !userMenu.contains(event.target)
        ) {

            userMenu.classList.remove("active");

        }

    });

}

// ================= BOUTON ACCUEIL =================

const homeButton = document.getElementById("homeButton");

if (homeButton) {

    homeButton.addEventListener("click", function () {

        window.location.href = "client-home.php";

    });

}

// ================= MES RÉSERVATIONS =================

const reservationsButton =
    document.getElementById("reservationsButton");

if (reservationsButton) {

    reservationsButton.addEventListener("click", function () {

        window.location.href = "mes-reservations.php";

    });

}

// ================= DÉCONNEXION =================

// La déconnexion est gérée par le lien ../logout.php
// dans confirmation.php. Aucun traitement supplémentaire
// n'est nécessaire ici.
