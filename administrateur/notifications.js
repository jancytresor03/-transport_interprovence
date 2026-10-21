
/* =====================================================
   BURUNDI TRANSPORT
   GESTION DES NOTIFICATIONS ADMINISTRATEUR
===================================================== */

// Éléments HTML
const notificationsList = document.getElementById("notificationsList");
const emptyState = document.getElementById("emptyState");

const totalNotifications = document.getElementById("totalNotifications");
const notificationsNonLues = document.getElementById("notificationsNonLues");
const notificationsLues = document.getElementById("notificationsLues");

const searchInput = document.getElementById("searchInput");
const typeFilter = document.getElementById("typeFilter");
const statusFilter = document.getElementById("statusFilter");

const resultCount = document.getElementById("resultCount");

const markAllButton = document.getElementById("markAllButton");
const refreshButton = document.getElementById("refreshButton");

// Liste des notifications chargées depuis la base de données
let notifications = [];

// Évite les doubles clics pendant une action
let actionEnCours = false;


/* =====================================================
   1. CHARGER LES NOTIFICATIONS
===================================================== */

async function chargerNotifications() {
    if (!notificationsList) return;

    try {
        notificationsList.innerHTML = `
            <p class="loading-message">
                Chargement des notifications...
            </p>
        `;

        const reponse = await fetch(
            "notifications-api.php?temps=" + Date.now(),
            {
                method: "GET",
                cache: "no-store",
                headers: {
                    "Accept": "application/json"
                }
            }
        );

        const texte = await reponse.text();

        let donnees;

        try {
            donnees = JSON.parse(texte);
        } catch (erreur) {
            throw new Error(
                "Le serveur n'a pas retourné un JSON valide. Vérifie notifications-api.php."
            );
        }

        if (!reponse.ok || donnees.success !== true) {
            throw new Error(
                donnees.message || "Impossible de charger les notifications."
            );
        }

        notifications = Array.isArray(donnees.notifications)
            ? donnees.notifications
            : [];

        actualiserStatistiques();
        actualiserBadges();
        afficherNotifications();

    } catch (erreur) {
        console.error("Erreur de chargement :", erreur);

        notificationsList.innerHTML = "";

        const message = document.createElement("p");
        message.className = "error-message";
        message.textContent =
            erreur.message || "Une erreur est survenue lors du chargement.";

        notificationsList.appendChild(message);

        if (emptyState) {
            emptyState.style.display = "none";
        }

        if (resultCount) {
            resultCount.textContent = "Erreur de chargement";
        }
    }
}


/* =====================================================
   2. CALCULER LES STATISTIQUES
===================================================== */

function actualiserStatistiques() {
    const total = notifications.length;

    const nonLues = notifications.filter(
        notification => notification.statut === "non_lue"
    ).length;

    const lues = total - nonLues;

    if (totalNotifications) {
        totalNotifications.textContent = total;
    }

    if (notificationsNonLues) {
        notificationsNonLues.textContent = nonLues;
    }

    if (notificationsLues) {
        notificationsLues.textContent = lues;
    }
}


/* =====================================================
   3. METTRE À JOUR LES BADGES
===================================================== */

function actualiserBadges() {
    const badges = [
        document.getElementById("menuBadge"),
        document.getElementById("headerBadge"),
        document.getElementById("notificationBadge")
    ];

    const nonLues = notifications.filter(
        notification => notification.statut === "non_lue"
    ).length;

    badges.forEach(badge => {
        if (!badge) return;

        badge.textContent = nonLues;
        badge.style.display = nonLues > 0 ? "inline-flex" : "none";
    });
}


/* =====================================================
   4. OBTENIR LE NOM DU TYPE
===================================================== */

function obtenirNomType(type) {
    const types = {
        reservation: "Réservation",
        paiement: "Paiement",
        trajet: "Trajet",
        securite: "Sécurité",
        systeme: "Système"
    };

    return types[type] || "Système";
}


/* =====================================================
   5. OBTENIR L'ICÔNE DU TYPE
===================================================== */

function obtenirIcone(type) {
    const icones = {
        reservation: "fa-ticket-alt",
        paiement: "fa-credit-card",
        trajet: "fa-bus",
        securite: "fa-shield-alt",
        systeme: "fa-bell"
    };

    return icones[type] || "fa-bell";
}


/* =====================================================
   6. OBTENIR LA CLASSE CSS DU TYPE
===================================================== */

function obtenirClasseType(type) {
    const classes = {
        reservation: "reservation",
        paiement: "paiement",
        trajet: "trajet",
        securite: "securite",
        systeme: "systeme"
    };

    return classes[type] || "systeme";
}


/* =====================================================
   7. FORMATER LA DATE
===================================================== */

function formaterDate(date) {
    if (!date) return "Date inconnue";

    // Convertit le format MySQL "2026-10-21 15:30:00"
    const dateNormalisee = String(date).replace(" ", "T");
    const dateObjet = new Date(dateNormalisee);

    if (Number.isNaN(dateObjet.getTime())) {
        return String(date);
    }

    return dateObjet.toLocaleString("fr-FR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit"
    });
}


/* =====================================================
   8. AFFICHER LES NOTIFICATIONS
===================================================== */

function afficherNotifications() {
    if (!notificationsList) return;

    notificationsList.innerHTML = "";

    const recherche = searchInput
        ? searchInput.value.trim().toLowerCase()
        : "";

    const typeChoisi = typeFilter
        ? typeFilter.value
        : "tous";

    const statutChoisi = statusFilter
        ? statusFilter.value
        : "tous";

    const notificationsFiltrees = notifications.filter(notification => {
        const titre = String(notification.titre || "").toLowerCase();
        const message = String(notification.message || "").toLowerCase();

        const correspondRecherche =
            titre.includes(recherche) ||
            message.includes(recherche);

        const type = notification.type_notification || "systeme";

        const correspondType =
            typeChoisi === "tous" ||
            type === typeChoisi;

        let correspondStatut = true;

        if (statutChoisi === "non-lue") {
            correspondStatut = notification.statut === "non_lue";
        } else if (statutChoisi === "lue") {
            correspondStatut = notification.statut !== "non_lue";
        }

        return (
            correspondRecherche &&
            correspondType &&
            correspondStatut
        );
    });

    if (resultCount) {
        resultCount.textContent =
            `${notificationsFiltrees.length} notification(s)`;
    }

    if (notificationsFiltrees.length === 0) {
        if (emptyState) {
            emptyState.style.display = "block";
        }

        return;
    }

    if (emptyState) {
        emptyState.style.display = "none";
    }

    notificationsFiltrees.forEach(notification => {
        const estNonLue = notification.statut === "non_lue";
        const type = notification.type_notification || "systeme";

        // Conteneur principal
        const carte = document.createElement("div");

        carte.className = "notification-item";

        if (estNonLue) {
            carte.classList.add("unread");
        }

        // Icône
        const iconeConteneur = document.createElement("div");
        iconeConteneur.className =
            `notification-icon ${obtenirClasseType(type)}`;

        const icone = document.createElement("i");
        icone.className = `fas ${obtenirIcone(type)}`;

        iconeConteneur.appendChild(icone);

        // Contenu
        const contenu = document.createElement("div");
        contenu.className = "notification-content";

        const titre = document.createElement("h3");
        titre.className = "notification-title";
        titre.textContent = notification.titre || "Notification";

        const message = document.createElement("p");
        message.className = "notification-message";
        message.textContent = notification.message || "";

        const informations = document.createElement("div");
        informations.className = "notification-meta";

        const typeElement = document.createElement("span");
        typeElement.className = "notification-type";
        typeElement.textContent = obtenirNomType(type);

        const dateElement = document.createElement("span");
        dateElement.className = "notification-date";
        dateElement.textContent = formaterDate(notification.date_creation);

        const statutElement = document.createElement("span");
        statutElement.className = "notification-status";
        statutElement.textContent = estNonLue ? "Non lue" : "Lue";

        informations.appendChild(typeElement);
        informations.appendChild(dateElement);
        informations.appendChild(statutElement);

        contenu.appendChild(titre);
        contenu.appendChild(message);
        contenu.appendChild(informations);

        carte.appendChild(iconeConteneur);
        carte.appendChild(contenu);

        // Bouton pour marquer comme lue
        if (estNonLue) {
            const bouton = document.createElement("button");

            bouton.type = "button";
            bouton.className = "mark-read-button";
            bouton.textContent = "Marquer comme lue";
            bouton.setAttribute(
                "aria-label",
                "Marquer la notification comme lue"
            );

            bouton.addEventListener("click", () => {
                marquerCommeLue(notification.notification_id);
            });

            carte.appendChild(bouton);
        }

        notificationsList.appendChild(carte);
    });
}


/* =====================================================
   9. ENVOYER UNE ACTION AU SERVEUR
===================================================== */

async function envoyerAction(action, notificationId = null) {
    if (actionEnCours) return;

    actionEnCours = true;

    if (markAllButton) {
        markAllButton.disabled = true;
    }

    try {
        const donnees = { action: action };

        if (notificationId !== null) {
            donnees.notification_id = notificationId;
        }

        const reponse = await fetch("notifications-action.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify(donnees)
        });

        const texte = await reponse.text();

        let resultat;

        try {
            resultat = JSON.parse(texte);
        } catch (erreur) {
            throw new Error(
                "Le serveur n'a pas retourné un JSON valide. Vérifie notifications-action.php."
            );
        }

        if (!reponse.ok || resultat.success !== true) {
            throw new Error(
                resultat.message || "L'action n'a pas pu être effectuée."
            );
        }

        await chargerNotifications();

    } catch (erreur) {
        console.error("Erreur pendant l'action :", erreur);

        alert(
            erreur.message ||
            "Une erreur est survenue. Réessaie."
        );

    } finally {
        actionEnCours = false;

        if (markAllButton) {
            markAllButton.disabled = false;
        }
    }
}


/* =====================================================
   10. MARQUER UNE NOTIFICATION COMME LUE
===================================================== */

function marquerCommeLue(notificationId) {
    if (!notificationId) {
        alert("Identifiant de notification invalide.");
        return;
    }

    envoyerAction("marquer_lue", notificationId);
}


/* =====================================================
   11. MARQUER TOUTES LES NOTIFICATIONS COMME LUES
===================================================== */

function marquerToutesCommeLues() {
    const existeNonLue = notifications.some(
        notification => notification.statut === "non_lue"
    );

    if (!existeNonLue) {
        alert("Toutes les notifications sont déjà lues.");
        return;
    }

    envoyerAction("tout_marquer_lue");
}


/* =====================================================
   12. ÉVÉNEMENTS DES FILTRES ET DES BOUTONS
===================================================== */

if (searchInput) {
    searchInput.addEventListener("input", afficherNotifications);
}

if (typeFilter) {
    typeFilter.addEventListener("change", afficherNotifications);
}

if (statusFilter) {
    statusFilter.addEventListener("change", afficherNotifications);
}

if (markAllButton) {
    markAllButton.addEventListener("click", marquerToutesCommeLues);
}

if (refreshButton) {
    refreshButton.addEventListener("click", chargerNotifications);
}


/* =====================================================
   13. CHARGEMENT INITIAL
===================================================== */

chargerNotifications();
