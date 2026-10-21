
/* =========================================
   STATISTIQUES
   BURUNDI TRANSPORT
========================================= */

// Données venant de l'API PHP
let statistiques = {};
let reservationsParMois = [];
let destinations = [];
let revenusParMois = [];

// Graphiques
let reservationsChart = null;
let statutChart = null;
let destinationChart = null;
let revenusChart = null;


/* =========================================
   FORMATER LES PRIX
========================================= */

function formaterPrix(prix) {
    return Number(prix || 0).toLocaleString("fr-FR") + " BIF";
}


/* =========================================
   CHARGER LES DONNEES DEPUIS MYSQL
========================================= */

async function chargerDonnees() {
    try {
        // L'API doit être dans le même dossier que statistiques.php
        const reponse = await fetch("statistiques-api.php", {
            method: "GET",
            cache: "no-store",
            headers: {
                "Accept": "application/json"
            }
        });

        if (!reponse.ok) {
            throw new Error("Erreur HTTP : " + reponse.status);
        }

        const donnees = await reponse.json();

        if (donnees.erreur) {
            throw new Error(donnees.erreur);
        }

        // Vérifier que l'API renvoie les statistiques
        if (!donnees.statistiques) {
            throw new Error("Les statistiques sont absentes de la réponse PHP.");
        }

        statistiques = donnees.statistiques;
        reservationsParMois = donnees.reservationsParMois || [];
        destinations = donnees.destinations || [];
        revenusParMois = donnees.revenusParMois || [];

        console.log("Statistiques reçues depuis PHP :", donnees);

        return true;

    } catch (erreur) {
        console.error("Erreur de chargement des statistiques :", erreur);

        return false;
    }
}


/* =========================================
   AFFICHER LES STATISTIQUES GENERALES
========================================= */

function afficherStatistiquesGenerales() {
    document.getElementById("totalReservations").textContent =
        Number(statistiques.totalReservations || 0);

    document.getElementById("reservationsConfirmees").textContent =
        Number(statistiques.reservationsConfirmees || 0);

    document.getElementById("totalRevenus").textContent =
        formaterPrix(statistiques.totalRevenus);

    document.getElementById("totalUtilisateurs").textContent =
        Number(statistiques.totalUtilisateurs || 0);

    document.getElementById("tableTrajets").textContent =
        Number(statistiques.totalTrajets || 0);

    document.getElementById("tableReservations").textContent =
        Number(statistiques.totalReservations || 0);

    document.getElementById("tableUtilisateurs").textContent =
        Number(statistiques.totalUtilisateurs || 0);

    document.getElementById("tableRevenus").textContent =
        formaterPrix(statistiques.totalRevenus);
}


/* =========================================
   PREPARER LES DONNEES DES 12 MOIS
========================================= */

function preparerMois(donnees) {
    const valeurs = Array(12).fill(0);

    donnees.forEach(function (ligne) {
        const mois = Number(ligne.mois);

        if (mois >= 1 && mois <= 12) {
            valeurs[mois - 1] = Number(ligne.total || 0);
        }
    });

    return valeurs;
}


/* =========================================
   GRAPHIQUE DES RESERVATIONS PAR MOIS
========================================= */

function creerGraphiqueReservations() {
    const canvas = document.getElementById("reservationsChart");

    if (!canvas) return;

    reservationsChart = new Chart(canvas, {
        type: "line",

        data: {
            labels: [
                "Jan", "Fév", "Mar", "Avr",
                "Mai", "Juin", "Juil", "Août",
                "Sep", "Oct", "Nov", "Déc"
            ],

            datasets: [{
                label: "Réservations",
                data: preparerMois(reservationsParMois),
                borderColor: "#2c6e9b",
                backgroundColor: "rgba(44, 110, 155, 0.10)",
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}


/* =========================================
   GRAPHIQUE DES STATUTS
========================================= */

function creerGraphiqueStatuts() {
    const canvas = document.getElementById("statutChart");

    if (!canvas) return;

    statutChart = new Chart(canvas, {
        type: "doughnut",

        data: {
            labels: [
                "Confirmées",
                "En attente",
                "Annulées",
                "Expirées"
            ],

            datasets: [{
                data: [
                    Number(statistiques.reservationsConfirmees || 0),
                    Number(statistiques.reservationsAttente || 0),
                    Number(statistiques.reservationsAnnulees || 0),
                    Number(statistiques.reservationsExpirees || 0)
                ],

                backgroundColor: [
                    "#4a9d7f",
                    "#e8b04b",
                    "#d9534f",
                    "#888888"
                ],

                borderWidth: 0
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    position: "bottom"
                }
            }
        }
    });
}


/* =========================================
   GRAPHIQUE DES DESTINATIONS
========================================= */

function creerGraphiqueDestinations() {
    const canvas = document.getElementById("destinationChart");

    if (!canvas) return;

    const labels = [];
    const valeurs = [];

    destinations.forEach(function (ligne) {
        labels.push(ligne.destination || "Inconnue");
        valeurs.push(Number(ligne.total || 0));
    });

    destinationChart = new Chart(canvas, {
        type: "bar",

        data: {
            labels: labels,

            datasets: [{
                label: "Réservations",
                data: valeurs,
                backgroundColor: "#2c6e9b",
                borderRadius: 7
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    display: false
                }
            },

            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}


/* =========================================
   GRAPHIQUE DES REVENUS PAR MOIS
========================================= */

function creerGraphiqueRevenus() {
    const canvas = document.getElementById("revenusChart");

    if (!canvas) return;

    revenusChart = new Chart(canvas, {
        type: "bar",

        data: {
            labels: [
                "Jan", "Fév", "Mar", "Avr",
                "Mai", "Juin", "Juil", "Août",
                "Sep", "Oct", "Nov", "Déc"
            ],

            datasets: [{
                label: "Revenus (BIF)",
                data: preparerMois(revenusParMois),
                backgroundColor: "#e8b04b",
                borderRadius: 7
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            scales: {
                y: {
                    beginAtZero: true,

                    ticks: {
                        callback: function (valeur) {
                            return Number(valeur).toLocaleString("fr-FR") + " BIF";
                        }
                    }
                }
            }
        }
    });
}


/* =========================================
   DETRUIRE LES ANCIENS GRAPHIQUES
========================================= */

function detruireGraphiques() {
    if (reservationsChart) reservationsChart.destroy();
    if (statutChart) statutChart.destroy();
    if (destinationChart) destinationChart.destroy();
    if (revenusChart) revenusChart.destroy();

    reservationsChart = null;
    statutChart = null;
    destinationChart = null;
    revenusChart = null;
}


/* =========================================
   ACTUALISER LA PAGE
========================================= */

async function actualiserPage() {
    const bouton = document.getElementById("refreshButton");

    if (bouton) bouton.disabled = true;

    const chargementReussi = await chargerDonnees();

    if (chargementReussi) {
        detruireGraphiques();

        afficherStatistiquesGenerales();

        creerGraphiqueReservations();
        creerGraphiqueStatuts();
        creerGraphiqueDestinations();
        creerGraphiqueRevenus();
    } else {
        alert(
            "Impossible de charger les données depuis statistiques-api.php. " +
            "Consulte la console du navigateur avec F12."
        );
    }

    if (bouton) bouton.disabled = false;
}


/* =========================================
   BOUTON ACTUALISER
========================================= */

const refreshButton = document.getElementById("refreshButton");

if (refreshButton) {
    refreshButton.addEventListener("click", actualiserPage);
}


/* =========================================
   MENU MOBILE
========================================= */

const menuButton = document.getElementById("menuButton");
const sidebar = document.querySelector(".sidebar");

if (menuButton && sidebar) {
    menuButton.addEventListener("click", function () {
        sidebar.classList.toggle("open");
    });
}


/* =========================================
   DECONNEXION
========================================= */

const logoutButton = document.getElementById("logoutButton");

if (logoutButton) {
    logoutButton.addEventListener("click", function (event) {
        // Le bouton contient déjà un lien vers logout.php.
        // On laisse ce lien effectuer la déconnexion.
    });
}


/* =========================================
   INITIALISATION
========================================= */

actualiserPage();
