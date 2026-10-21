// ================= DONNÉES DE DÉMONSTRATION =================

// Pour le moment, les réservations sont seulement
// des données de démonstration.
//
// Plus tard :
// JavaScript → PHP → MySQL
//
// et ces données viendront de la base de données.

const reservations = [

    {
        reference: "BT-20261001-4582",

        dateReservation: "01/10/2026",

        depart: "Bujumbura",

        destination: "Gitega",

        dateVoyage: "05/10/2026",

        heure: "08:00",

        passagers: 2,

        places: "12, 13",

        prix: 30000,

        statut: "confirmee"
    },


    {
        reference: "BT-20260928-7215",

        dateReservation: "28/09/2026",

        depart: "Bujumbura",

        destination: "Ngozi",

        dateVoyage: "10/10/2026",

        heure: "07:30",

        passagers: 1,

        places: "8",

        prix: 35000,

        statut: "attente"
    },


    {
        reference: "BT-20260920-3146",

        dateReservation: "20/09/2026",

        depart: "Gitega",

        destination: "Bujumbura",

        dateVoyage: "25/09/2026",

        heure: "14:00",

        passagers: 1,

        places: "20",

        prix: 30000,

        statut: "annulee"
    }

];


// ================= ÉLÉMENTS HTML =================

const reservationsContainer =
    document.getElementById(
        "reservationsContainer"
    );

const emptyState =
    document.getElementById(
        "emptyState"
    );

const filterStatus =
    document.getElementById(
        "filterStatus"
    );


// ================= AFFICHER LES RÉSERVATIONS =================

function afficherReservations(liste) {

    reservationsContainer.innerHTML = "";


    // Si aucune réservation

    if (liste.length === 0) {

        emptyState.style.display = "block";

        return;

    }


    emptyState.style.display = "none";


    // Parcourir les réservations

    liste.forEach(function (reservation) {


        // Traduire le statut

        let texteStatut = "";

        if (reservation.statut === "confirmee") {

            texteStatut = "Confirmée";

        }
        else if (reservation.statut === "attente") {

            texteStatut = "En attente";

        }
        else if (reservation.statut === "annulee") {

            texteStatut = "Annulée";

        }


        // Calcul du prix total

        const total =
            reservation.prix *
            reservation.passagers;


        // Créer la carte

        const card =
            document.createElement("article");

        card.className =
            "reservation-card";


        card.innerHTML = `

            <div class="reservation-top">

                <div>

                    <div class="reservation-reference">

                        ${reservation.reference}

                    </div>

                    <div class="reservation-date">

                        Réservée le
                        ${reservation.dateReservation}

                    </div>

                </div>


                <span class="status ${reservation.statut}">

                    ${texteStatut}

                </span>

            </div>


            <div class="reservation-route">

                <div class="route-location">

                    <span>
                        Départ
                    </span>

                    <strong>
                        ${reservation.depart}
                    </strong>

                </div>


                <div class="route-arrow">
                    →
                </div>


                <div class="route-location">

                    <span>
                        Destination
                    </span>

                    <strong>
                        ${reservation.destination}
                    </strong>

                </div>

            </div>


            <div class="reservation-details">


                <div class="detail">

                    <span>
                        Date du voyage
                    </span>

                    <strong>
                        ${reservation.dateVoyage}
                    </strong>

                </div>


                <div class="detail">

                    <span>
                        Heure
                    </span>

                    <strong>
                        ${reservation.heure}
                    </strong>

                </div>


                <div class="detail">

                    <span>
                        Passagers
                    </span>

                    <strong>
                        ${reservation.passagers}
                    </strong>

                </div>


                <div class="detail">

                    <span>
                        Places
                    </span>

                    <strong>
                        ${reservation.places}
                    </strong>

                </div>


            </div>


            <div class="reservation-bottom">


                <div class="price">

                    ${total.toLocaleString("fr-FR")} BIF

                </div>


                <div class="card-actions">

                    <button
                        class="btn-outline"
                        onclick="voirReservation('${reservation.reference}')"
                    >
                        Voir détails
                    </button>


                    ${
                        reservation.statut !== "annulee"
                        ?
                        `
                        <button
                            class="btn-danger"
                            onclick="annulerReservation('${reservation.reference}')"
                        >
                            Annuler
                        </button>
                        `
                        :
                        ""
                    }

                </div>

            </div>

        `;


        reservationsContainer.appendChild(card);

    });

}


// ================= STATISTIQUES =================

function afficherStatistiques() {

    const total =
        reservations.length;


    const confirmees =
        reservations.filter(function (reservation) {

            return reservation.statut === "confirmee";

        }).length;


    const attente =
        reservations.filter(function (reservation) {

            return reservation.statut === "attente";

        }).length;


    const annulees =
        reservations.filter(function (reservation) {

            return reservation.statut === "annulee";

        }).length;


    document.getElementById(
        "totalReservations"
    ).textContent = total;


    document.getElementById(
        "confirmedReservations"
    ).textContent = confirmees;


    document.getElementById(
        "pendingReservations"
    ).textContent = attente;


    document.getElementById(
        "cancelledReservations"
    ).textContent = annulees;

}


// ================= FILTRER =================

filterStatus.addEventListener(
    "change",
    function () {

        const valeur =
            filterStatus.value;


        if (valeur === "toutes") {

            afficherReservations(
                reservations
            );

            return;

        }


        const resultat =
            reservations.filter(
                function (reservation) {

                    return (
                        reservation.statut ===
                        valeur
                    );

                }
            );


        afficherReservations(resultat);

    }
);


// ================= VOIR DÉTAILS =================

function voirReservation(reference) {

    const reservation =
        reservations.find(
            function (reservation) {

                return (
                    reservation.reference ===
                    reference
                );

            }
        );


    if (!reservation) {

        return;

    }


    alert(
        "Réservation : " +
        reservation.reference +
        "\n\n" +

        "Trajet : " +
        reservation.depart +
        " → " +
        reservation.destination +
        "\n" +

        "Date : " +
        reservation.dateVoyage +
        "\n" +

        "Heure : " +
        reservation.heure +
        "\n" +

        "Passagers : " +
        reservation.passagers +
        "\n" +

        "Places : " +
        reservation.places
    );

}


// ================= ANNULER =================

function annulerReservation(reference) {

    const reservation =
        reservations.find(
            function (reservation) {

                return (
                    reservation.reference ===
                    reference
                );

            }
        );


    if (!reservation) {

        return;

    }


    const confirmer =
        confirm(
            "Voulez-vous vraiment annuler la réservation " +
            reference +
            " ?"
        );


    if (!confirmer) {

        return;

    }


    reservation.statut =
        "annulee";


    afficherStatistiques();


    // Réafficher selon le filtre actuel

    const filtre =
        filterStatus.value;


    if (filtre === "toutes") {

        afficherReservations(
            reservations
        );

    }
    else {

        afficherReservations(
            reservations.filter(
                function (reservation) {

                    return (
                        reservation.statut ===
                        filtre
                    );

                }
            )
        );

    }


    alert(
        "La réservation " +
        reference +
        " a été annulée."
    );

}


// ================= NOUVELLE RÉSERVATION =================

document.getElementById(
    "newReservationButton"
).addEventListener(
    "click",
    function () {

        window.location.href =
            "recherche.html";

    }
);


// ================= EMPTY BUTTON =================

document.getElementById(
    "emptyReservationButton"
).addEventListener(
    "click",
    function () {

        window.location.href =
            "recherche.html";

    }
);


// ================= MENU UTILISATEUR =================

const userButton =
    document.getElementById(
        "userButton"
    );

const userMenu =
    document.getElementById(
        "userMenu"
    );


userButton.addEventListener(
    "click",
    function () {

        userMenu.classList.toggle(
            "active"
        );

    }
);


// Fermer le menu en cliquant ailleurs

document.addEventListener(
    "click",
    function (event) {

        if (
            !userButton.contains(
                event.target
            )
            &&
            !userMenu.contains(
                event.target
            )
        ) {

            userMenu.classList.remove(
                "active"
            );

        }

    }
);


// ================= DÉCONNEXION =================

document.getElementById(
    "logoutButton"
).addEventListener(
    "click",
    function () {

        const confirmer =
            confirm(
                "Voulez-vous vraiment vous déconnecter ?"
            );


        if (confirmer) {

            // Comme nous sommes encore
            // en frontend :

            window.location.href =
                "../index.html";

        }

    }
);


// ================= INITIALISATION =================

afficherStatistiques();

afficherReservations(
    reservations
);