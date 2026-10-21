
/* =====================================================
   DASHBOARD ADMINISTRATEUR
===================================================== */

/* ÉLÉMENTS HTML */

const sidebar = document.getElementById("sidebar");
const openMenu = document.getElementById("openMenu");
const closeMenu = document.getElementById("closeMenu");
const overlay = document.getElementById("overlay");
const logoutButton = document.getElementById("logoutButton");
const addTripButton = document.getElementById("addTripButton");
const notificationButton = document.getElementById("notificationButton");

const menuLinks = document.querySelectorAll(".menu-link");

/* =====================================================
   MENU MOBILE
===================================================== */

if (openMenu) {
    openMenu.addEventListener("click", function () {
        if (sidebar) sidebar.classList.add("open");
        if (overlay) overlay.classList.add("active");
    });
}

function fermerSidebar() {
    if (sidebar) sidebar.classList.remove("open");
    if (overlay) overlay.classList.remove("active");
}

if (closeMenu) {
    closeMenu.addEventListener("click", fermerSidebar);
}

if (overlay) {
    overlay.addEventListener("click", fermerSidebar);
}

menuLinks.forEach(function (link) {
    link.addEventListener("click", function () {
        if (window.innerWidth <= 900) {
            fermerSidebar();
        }
    });
});

/* =====================================================
   NAVIGATION
===================================================== */

if (addTripButton) {
    addTripButton.addEventListener("click", function () {
        window.location.href = "trajets.php";
    });
}

if (notificationButton) {
    notificationButton.addEventListener("click", function () {
        window.location.href = "notifications.php";
    });
}

/* =====================================================
   DÉCONNEXION
   Fonctionne avec un bouton ou un lien HTML
===================================================== */

if (logoutButton) {
    logoutButton.addEventListener("click", function (event) {
        const lienLogout = logoutButton.matches("a")
            ? logoutButton
            : logoutButton.querySelector("a");

        if (lienLogout) {
            event.preventDefault();

            if (confirm("Voulez-vous vraiment vous déconnecter ?")) {
                window.location.href = lienLogout.href;
            }
        }
    });
}

/* =====================================================
   PAGE ACTIVE
===================================================== */

const pageActuelle = window.location.pathname.split("/").pop();

menuLinks.forEach(function (link) {
    if (link.getAttribute("href") === pageActuelle) {
        menuLinks.forEach(function (element) {
            element.classList.remove("active");
        });

        link.classList.add("active");
    }
});

/* =====================================================
   RESPONSIVE
===================================================== */

window.addEventListener("resize", function () {
    if (window.innerWidth > 900) {
        fermerSidebar();
    }
});

/* =====================================================
   FORMATAGE DES DONNÉES
===================================================== */

function formaterNombre(nombre) {
    return Number(nombre ?? 0).toLocaleString("fr-FR");
}

function formaterMontant(montant) {
    return Number(montant ?? 0).toLocaleString("fr-FR", {
        maximumFractionDigits: 0
    }) + " BIF";
}

function echapperHTML(valeur) {
    return String(valeur ?? "").replace(/[&<>"']/g, function (caractere) {
        const caracteres = {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;"
        };

        return caracteres[caractere];
    });
}

/* Format de date : YYYY-MM-DD vers DD/MM/YYYY */

function formaterDate(dateTexte) {
    if (!dateTexte) return "Date inconnue";

    const parties = String(dateTexte).split("-");

    if (parties.length !== 3) {
        return echapperHTML(dateTexte);
    }

    return echapperHTML(
        parties[2] + "/" + parties[1] + "/" + parties[0]
    );
}

/* Initiales du client */

function obtenirInitiales(nom, prenom) {
    const premiere = String(prenom ?? "").trim().charAt(0);
    const seconde = String(nom ?? "").trim().charAt(0);

    return (premiere + seconde).toUpperCase() || "CL";
}

/* =====================================================
   STATUT DES RÉSERVATIONS
===================================================== */

function afficherStatut(statut) {
    const statuts = {
        confirmee: {
            texte: "Confirmée",
            classe: "confirmed"
        },
        en_attente: {
            texte: "En attente",
            classe: "waiting"
        },
        annulee: {
            texte: "Annulée",
            classe: "cancelled"
        },
        expiree: {
            texte: "Expirée",
            classe: "cancelled"
        }
    };

    return statuts[statut] || {
        texte: statut || "Inconnu",
        classe: "waiting"
    };
}

/* =====================================================
   AFFICHER LES STATISTIQUES
===================================================== */

function afficherStatistiques(statistiques) {
    const totalUtilisateurs = document.getElementById("totalUtilisateurs");
    const reservationsMois = document.getElementById("reservationsMois");
    const totalTrajets = document.getElementById("totalTrajets");
    const revenusMois = document.getElementById("revenusMois");

    if (!statistiques) {
        console.error("Les statistiques sont absentes de la réponse API.");
        return;
    }

    if (totalUtilisateurs) {
        totalUtilisateurs.textContent =
            formaterNombre(statistiques.total_utilisateurs);
    }

    if (reservationsMois) {
        reservationsMois.textContent =
            formaterNombre(statistiques.reservations_mois);
    }

    if (totalTrajets) {
        totalTrajets.textContent =
            formaterNombre(statistiques.trajets_actifs);
    }

    if (revenusMois) {
        revenusMois.textContent =
            formaterMontant(statistiques.revenus_mois);
    }

    console.log("Statistiques affichées :", statistiques);
}

/* =====================================================
   AFFICHER LES RÉSERVATIONS RÉCENTES
===================================================== */

function afficherReservations(reservations) {
    const tableau = document.getElementById("reservationsTableBody");

    if (!tableau) {
        console.warn("Élément reservationsTableBody introuvable.");
        return;
    }

    if (!Array.isArray(reservations) || reservations.length === 0) {
        tableau.innerHTML = `
            <tr>
                <td colspan="6">Aucune réservation trouvée.</td>
            </tr>
        `;
        return;
    }

    tableau.innerHTML = reservations.map(function (reservation) {
        const statut = afficherStatut(reservation.statut);

        const client =
            echapperHTML(reservation.prenom_passager) + " " +
            echapperHTML(reservation.nom_passager);

        const trajet =
            echapperHTML(reservation.depart) + " → " +
            echapperHTML(reservation.destination);

        const date = reservation.date_creation
            ? String(reservation.date_creation).split(" ")[0]
            : "";

        const initiales = echapperHTML(
            obtenirInitiales(
                reservation.nom_passager,
                reservation.prenom_passager
            )
        );

        return `
            <tr>
                <td>
                    <strong class="reference">
                        ${echapperHTML(reservation.reference)}
                    </strong>
                </td>

                <td>
                    <div class="client">
                        <div class="client-avatar">${initiales}</div>
                        ${client}
                    </div>
                </td>

                <td>${trajet}</td>

                <td>${formaterDate(date)}</td>

                <td>${formaterMontant(reservation.montant_total)}</td>

                <td>
                    <span class="status ${statut.classe}">
                        ${echapperHTML(statut.texte)}
                    </span>
                </td>
            </tr>
        `;
    }).join("");
}

/* =====================================================
   AFFICHER LES PROCHAINS TRAJETS
===================================================== */

function afficherTrajets(trajets) {
    const conteneur = document.getElementById("prochainsTrajets");

    if (!conteneur) {
        console.warn("Élément prochainsTrajets introuvable.");
        return;
    }

    if (!Array.isArray(trajets) || trajets.length === 0) {
        conteneur.innerHTML = "<p>Aucun trajet à venir.</p>";
        return;
    }

    const mois = [
        "JAN", "FÉV", "MAR", "AVR", "MAI", "JUN",
        "JUL", "AOÛ", "SEP", "OCT", "NOV", "DÉC"
    ];

    conteneur.innerHTML = trajets.map(function (trajet) {
        const parties = trajet.date_depart
            ? String(trajet.date_depart).split("-")
            : [];

        const jour = parties[2] || "--";
        const numeroMois = Number(parties[1]);
        const moisTexte = mois[numeroMois - 1] || "";

        const heure = trajet.heure_depart
            ? String(trajet.heure_depart).substring(0, 5)
            : "--:--";

        return `
            <div class="trip">
                <div class="trip-date">
                    <strong>${echapperHTML(jour)}</strong>
                    <span>${echapperHTML(moisTexte)}</span>
                </div>

                <div class="trip-info">
                    <strong>
                        ${echapperHTML(trajet.depart)}
                        → ${echapperHTML(trajet.destination)}
                    </strong>

                    <span>
                        <i class="fa-regular fa-clock"></i>
                        ${echapperHTML(heure)}
                    </span>
                </div>

                <div class="places">
                    <strong>
                        ${formaterNombre(trajet.places_disponibles)}
                    </strong>
                    <span>places</span>
                </div>
            </div>
        `;
    }).join("");
}

/* =====================================================
   CHARGER LE DASHBOARD DEPUIS L'API PHP
===================================================== */

async function chargerDashboard() {
    const tableau = document.getElementById("reservationsTableBody");
    const conteneurTrajets = document.getElementById("prochainsTrajets");

    try {
        const reponse = await fetch("dashboard-api.php", {
            method: "GET",
            headers: {
                "Accept": "application/json"
            },
            credentials: "same-origin",
            cache: "no-store"
        });

        const texte = await reponse.text();

        let donnees;

        try {
            donnees = JSON.parse(texte);
        } catch (erreurJSON) {
            console.error("Réponse reçue au lieu du JSON :", texte);
            throw new Error(
                "L'API n'a pas renvoyé un JSON valide. Vérifie dashboard-api.php."
            );
        }

        if (!reponse.ok || !donnees.succes) {
            throw new Error(
                donnees.message || "Impossible de charger le Dashboard."
            );
        }

        afficherStatistiques(donnees.statistiques);
        afficherReservations(donnees.reservations);
        afficherTrajets(donnees.trajets);

        console.log("Dashboard chargé avec les données MySQL.");

    } catch (erreur) {
        console.error("Erreur Dashboard :", erreur);

        if (tableau) {
            tableau.innerHTML = `
                <tr>
                    <td colspan="6">
                        Impossible de charger les réservations.
                        Consulte la console du navigateur.
                    </td>
                </tr>
            `;
        }

        if (conteneurTrajets) {
            conteneurTrajets.innerHTML =
                "<p>Impossible de charger les trajets.</p>";
        }
    }
}

/* =====================================================
   INITIALISATION
===================================================== */

document.addEventListener("DOMContentLoaded", function () {
    chargerDashboard();
});

console.log("Dashboard administrateur initialisé.");
