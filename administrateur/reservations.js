
/* =========================================
   GESTION DES RESERVATIONS
   BURUNDI TRANSPORT
   Connexion avec PHP et MariaDB
========================================= */


/* =========================================
   VARIABLES
========================================= */

let reservations = [];


/* =========================================
   ELEMENTS HTML
========================================= */

const reservationsTableBody =
    document.getElementById("reservationsTableBody");

const searchInput =
    document.getElementById("searchInput");

const statusFilter =
    document.getElementById("statusFilter");

const dateFilter =
    document.getElementById("dateFilter");

const resultCount =
    document.getElementById("resultCount");

const emptyState =
    document.getElementById("emptyState");

const reservationModal =
    document.getElementById("reservationModal");

const reservationDetails =
    document.getElementById("reservationDetails");


/* =========================================
   CHARGER LES RESERVATIONS DEPUIS LA BDD
========================================= */

async function chargerReservations() {

    try {

        const reponse = await fetch("reservations-api.php");

        const donnees = await reponse.json();

        if (!reponse.ok || !donnees.succes) {
            throw new Error(
                donnees.message || "Erreur de chargement."
            );
        }

        reservations = donnees.reservations.map(function (r) {

            return {
                id: r.id,
                reference: r.reference,

                nom: r.nom,
                prenom: r.prenom,

                telephone: r.telephone,
                email: r.email,

                depart: r.depart,
                destination: r.destination,

                dateVoyage: r.date_voyage,
                heure: r.heure,

                passagers: Number(r.passagers),
                places: Number(r.passagers),

                prixUnitaire: Number(r.prix_unitaire),
                total: Number(r.total),

                identite: r.identite,
                numeroIdentite: r.numero_identite,

                statut: r.statut === "en_attente"
                    ? "attente"
                    : r.statut,

                dateReservation: r.date_reservation,
                remarque: r.remarque
            };

        });

        filtrerReservations();
        actualiserStatistiques();

    } catch (erreur) {

        console.error("Erreur de chargement :", erreur);

        reservationsTableBody.innerHTML = "";

        emptyState.classList.add("show");

        resultCount.textContent = "Erreur de chargement";

        alert(
            "Impossible de charger les réservations. " +
            "Vérifie reservations-api.php et la connexion à la base de données."
        );

    }

}


/* =========================================
   AFFICHER LES RESERVATIONS
========================================= */

function afficherReservations(liste = reservations) {

    reservationsTableBody.innerHTML = "";

    resultCount.textContent =
        liste.length +
        (liste.length > 1 ? " réservations" : " réservation");

    if (liste.length === 0) {

        emptyState.classList.add("show");

        return;

    }

    emptyState.classList.remove("show");

    liste.forEach(function (reservation) {

        const ligne = document.createElement("tr");

        ligne.innerHTML = `

            <td>
                <span class="reference">
                    ${echapperHTML(reservation.reference)}
                </span>
            </td>

            <td>
                <div class="client-name">
                    ${echapperHTML(reservation.prenom)}
                    ${echapperHTML(reservation.nom)}
                </div>

                <small>
                    ${echapperHTML(reservation.telephone)}
                </small>
            </td>

            <td>
                <div class="route">
                    ${echapperHTML(reservation.depart)}
                    <i class="fa-solid fa-arrow-right"></i>
                    ${echapperHTML(reservation.destination)}
                </div>
            </td>

            <td>
                ${formaterDate(reservation.dateVoyage)}
            </td>

            <td>
                ${echapperHTML(reservation.heure || "-")}
            </td>

            <td>
                ${reservation.passagers}
            </td>

            <td>
                <span class="price">
                    ${formaterPrix(reservation.total)}
                </span>
            </td>

            <td>
                ${afficherStatut(reservation.statut)}
            </td>

            <td>
                <div class="actions">

                    <button
                        class="action-button"
                        title="Voir les détails"
                        onclick="voirDetails('${echapperAttribut(reservation.id)}')">
                        <i class="fa-solid fa-eye"></i>
                    </button>

                    ${
                        reservation.statut === "attente"
                        ? `
                        <button
                            class="action-button confirm"
                            title="Confirmer"
                            onclick="confirmerReservation('${echapperAttribut(reservation.id)}')">
                            <i class="fa-solid fa-check"></i>
                        </button>
                        `
                        : ""
                    }

                    ${
                        reservation.statut !== "annulee"
                        ? `
                        <button
                            class="action-button cancel"
                            title="Annuler"
                            onclick="annulerReservation('${echapperAttribut(reservation.id)}')">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                        `
                        : ""
                    }

                    <button
                        class="action-button cancel"
                        title="Suppression désactivée"
                        onclick="supprimerReservation('${echapperAttribut(reservation.id)}')">
                        <i class="fa-solid fa-trash"></i>
                    </button>

                </div>
            </td>

        `;

        reservationsTableBody.appendChild(ligne);

    });

}


/* =========================================
   AFFICHER LE STATUT
========================================= */

function afficherStatut(statut) {

    if (statut === "confirmee") {

        return `
            <span class="status confirmee">
                <i class="fa-solid fa-circle-check"></i>
                Confirmée
            </span>
        `;

    }

    if (statut === "attente") {

        return `
            <span class="status attente">
                <i class="fa-solid fa-clock"></i>
                En attente
            </span>
        `;

    }

    if (statut === "annulee") {

        return `
            <span class="status annulee">
                <i class="fa-solid fa-circle-xmark"></i>
                Annulée
            </span>
        `;

    }

    if (statut === "expiree") {

        return `
            <span class="status annulee">
                <i class="fa-solid fa-clock"></i>
                Expirée
            </span>
        `;

    }

    return echapperHTML(statut || "Inconnu");

}


/* =========================================
   FILTRER LES RESERVATIONS
========================================= */

function filtrerReservations() {

    const recherche =
        searchInput.value.toLowerCase().trim();

    const statut =
        statusFilter.value;

    const date =
        dateFilter.value;

    const resultat = reservations.filter(function (reservation) {

        const texteRecherche = (

            reservation.reference + " " +
            reservation.nom + " " +
            reservation.prenom + " " +
            reservation.depart + " " +
            reservation.destination + " " +
            reservation.telephone

        ).toLowerCase();

        const correspondRecherche =
            texteRecherche.includes(recherche);

        let statutReservation = reservation.statut;

        if (statutReservation === "en_attente") {
            statutReservation = "attente";
        }

        const correspondStatut =
            statut === "tous" ||
            statutReservation === statut;

        const correspondDate =
            date === "" ||
            reservation.dateVoyage === date;

        return (
            correspondRecherche &&
            correspondStatut &&
            correspondDate
        );

    });

    afficherReservations(resultat);

}


/* =========================================
   STATISTIQUES
========================================= */

function actualiserStatistiques() {

    const total = reservations.length;

    const confirmees = reservations.filter(
        function (reservation) {
            return reservation.statut === "confirmee";
        }
    ).length;

    const attente = reservations.filter(
        function (reservation) {
            return reservation.statut === "attente";
        }
    ).length;

    const annulees = reservations.filter(
        function (reservation) {
            return reservation.statut === "annulee";
        }
    ).length;

    document.getElementById("totalReservations").textContent =
        total;

    document.getElementById("reservationsConfirmees").textContent =
        confirmees;

    document.getElementById("reservationsAttente").textContent =
        attente;

    document.getElementById("reservationsAnnulees").textContent =
        annulees;

}


/* =========================================
   VOIR LES DETAILS
========================================= */

function voirDetails(id) {

    const reservation = reservations.find(
        function (r) {
            return String(r.id) === String(id);
        }
    );

    if (!reservation) {
        return;
    }

    reservationDetails.innerHTML = `

        <div class="detail-grid">

            <div class="detail-item">
                <span>Référence</span>
                <strong>${echapperHTML(reservation.reference)}</strong>
            </div>

            <div class="detail-item">
                <span>Statut</span>
                ${afficherStatut(reservation.statut)}
            </div>

            <div class="detail-item">
                <span>Nom complet</span>
                <strong>
                    ${echapperHTML(reservation.prenom)}
                    ${echapperHTML(reservation.nom)}
                </strong>
            </div>

            <div class="detail-item">
                <span>Téléphone</span>
                <strong>${echapperHTML(reservation.telephone)}</strong>
            </div>

            <div class="detail-item">
                <span>Email</span>
                <strong>${echapperHTML(reservation.email || "Non renseigné")}</strong>
            </div>

            <div class="detail-item">
                <span>Document</span>
                <strong>${echapperHTML(reservation.identite || "-")}</strong>
            </div>

            <div class="detail-item">
                <span>Numéro du document</span>
                <strong>${echapperHTML(reservation.numeroIdentite || "-")}</strong>
            </div>

            <div class="detail-item">
                <span>Date de réservation</span>
                <strong>${formaterDate(reservation.dateReservation)}</strong>
            </div>

            <div class="detail-item">
                <span>Départ</span>
                <strong>${echapperHTML(reservation.depart)}</strong>
            </div>

            <div class="detail-item">
                <span>Destination</span>
                <strong>${echapperHTML(reservation.destination)}</strong>
            </div>

            <div class="detail-item">
                <span>Date du voyage</span>
                <strong>${formaterDate(reservation.dateVoyage)}</strong>
            </div>

            <div class="detail-item">
                <span>Heure</span>
                <strong>${echapperHTML(reservation.heure || "-")}</strong>
            </div>

            <div class="detail-item">
                <span>Nombre de passagers</span>
                <strong>${reservation.passagers}</strong>
            </div>

            <div class="detail-item">
                <span>Places</span>
                <strong>${reservation.passagers}</strong>
            </div>

            <div class="detail-item">
                <span>Prix unitaire estimé</span>
                <strong>${formaterPrix(reservation.prixUnitaire)}</strong>
            </div>

            <div class="detail-item">
                <span>Total</span>
                <strong>${formaterPrix(reservation.total)}</strong>
            </div>

            <div class="detail-item">
                <span>Remarque</span>
                <strong>${echapperHTML(reservation.remarque || "Aucune")}</strong>
            </div>

        </div>

    `;

    reservationModal.classList.add("show");

}


/* =========================================
   FERMER LA FENETRE DES DETAILS
========================================= */

function fermerModal() {

    reservationModal.classList.remove("show");

}


/* =========================================
   MODIFIER LE STATUT DANS LA BASE DE DONNEES
========================================= */

async function modifierStatutReservation(id, statut) {

    const message = statut === "confirmee"
        ? "Voulez-vous confirmer cette réservation ?"
        : "Voulez-vous annuler cette réservation ?";

    if (!confirm(message)) {
        return;
    }

    try {

        const reponse = await fetch("reservations-api.php", {

            method: "POST",

            headers: {
                "Content-Type": "application/json"
            },

            body: JSON.stringify({
                id: id,
                statut: statut
            })

        });

        const resultat = await reponse.json();

        if (!reponse.ok || !resultat.succes) {
            throw new Error(
                resultat.message || "La modification a échoué."
            );
        }

        alert(resultat.message);

        await chargerReservations();

    } catch (erreur) {

        console.error("Erreur de modification :", erreur);

        alert(
            "Impossible de modifier la réservation.\n" +
            erreur.message +
            "\nVérifie aussi les triggers SQL."
        );

    }

}


/* =========================================
   CONFIRMER UNE RESERVATION
========================================= */

function confirmerReservation(id) {

    modifierStatutReservation(id, "confirmee");

}


/* =========================================
   ANNULER UNE RESERVATION
========================================= */

function annulerReservation(id) {

    modifierStatutReservation(id, "annulee");

}


/* =========================================
   SUPPRESSION DESACTIVEE
========================================= */

function supprimerReservation(id) {

    alert(
        "La suppression définitive est désactivée. " +
        "Pour le moment, utilise l'annulation."
    );

}


/* =========================================
   FORMATER LE PRIX
========================================= */

function formaterPrix(prix) {

    const montant = Number(prix);

    if (!Number.isFinite(montant)) {
        return "-";
    }

    return montant.toLocaleString("fr-FR") + " BIF";

}


/* =========================================
   FORMATER LA DATE
========================================= */

function formaterDate(date) {

    if (!date) {
        return "-";
    }

    const morceaux = String(date).substring(0, 10).split("-");

    if (morceaux.length !== 3) {
        return date;
    }

    return morceaux[2] + "/" + morceaux[1] + "/" + morceaux[0];

}


/* =========================================
   PROTEGER L'AFFICHAGE DU TEXTE
========================================= */

function echapperHTML(valeur) {

    const element = document.createElement("div");

    element.textContent = valeur === null || valeur === undefined
        ? ""
        : String(valeur);

    return element.innerHTML;

}

function echapperAttribut(valeur) {

    return String(valeur)
        .replace(/&/g, "&amp;")
        .replace(/'/g, "&#39;")
        .replace(/"/g, "&quot;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");

}


/* =========================================
   REINITIALISER LES FILTRES
========================================= */

function reinitialiserFiltres() {

    searchInput.value = "";
    statusFilter.value = "tous";
    dateFilter.value = "";

    filtrerReservations();

}


/* =========================================
   ACTUALISER LA PAGE
========================================= */

async function actualiserPage() {

    await chargerReservations();

}


/* =========================================
   EVENEMENTS DES FILTRES
========================================= */

searchInput.addEventListener(
    "input",
    filtrerReservations
);

statusFilter.addEventListener(
    "change",
    filtrerReservations
);

dateFilter.addEventListener(
    "change",
    filtrerReservations
);

document.getElementById("resetFilters").addEventListener(
    "click",
    reinitialiserFiltres
);

document.getElementById("refreshButton").addEventListener(
    "click",
    actualiserPage
);


/* =========================================
   EVENEMENTS DE LA FENETRE DES DETAILS
========================================= */

document.getElementById("closeModal").addEventListener(
    "click",
    fermerModal
);

document.getElementById("closeModalBottom").addEventListener(
    "click",
    fermerModal
);

reservationModal.addEventListener(
    "click",
    function (event) {

        if (event.target === reservationModal) {
            fermerModal();
        }

    }
);


/* =========================================
   MENU MOBILE
========================================= */

const menuButton = document.getElementById("menuButton");

const sidebar = document.querySelector(".sidebar");

menuButton.addEventListener(
    "click",
    function () {
        sidebar.classList.toggle("open");
    }
);


/* =========================================
   DECONNEXION
========================================= */

document.getElementById("logoutButton").addEventListener(
    "click",
    function (event) {

        // Le lien de deconnexion est dans le bouton.
        const lien = event.currentTarget.querySelector("a");

        if (!confirm("Voulez-vous vous déconnecter ?")) {
            event.preventDefault();
        } else if (lien) {
            window.location.href = lien.href;
        }

    }
);


/* =========================================
   INITIALISATION
========================================= */

chargerReservations();
