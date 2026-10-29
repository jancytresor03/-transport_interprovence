
"use strict";

/* =========================================
   1. RÉCUPÉRATION DES ÉLÉMENTS HTML
========================================= */

const totalConnexions = document.getElementById("totalConnexions");
const connexionsReussies = document.getElementById("connexionsReussies");
const connexionsEchouees = document.getElementById("connexionsEchouees");

const searchInput = document.getElementById("searchInput");
const statusFilter = document.getElementById("statusFilter");
const limitSelect = document.getElementById("limitSelect");

const historyMessage = document.getElementById("historyMessage");
const historyTableBody = document.getElementById("historyTableBody");

const resultCount = document.getElementById("resultCount");
const paginationInfo = document.getElementById("paginationInfo");
const currentPage = document.getElementById("currentPage");

const previousPage = document.getElementById("previousPage");
const nextPage = document.getElementById("nextPage");
const refreshButton = document.getElementById("refreshButton");

const sidebar = document.getElementById("sidebar");
const menuToggle = document.getElementById("menuToggle");


/* =========================================
   2. VARIABLES DE PAGINATION
========================================= */

let pageActuelle = 1;
let nombrePages = 1;
let minuterieRecherche = null;


/* =========================================
   3. AFFICHER UN MESSAGE
========================================= */

function afficherMessage(message, type = "info") {
    historyMessage.textContent = message;

    historyMessage.className = "history-message";

    if (type === "erreur") {
        historyMessage.classList.add("message-error");
    } else if (type === "succes") {
        historyMessage.classList.add("message-success");
    } else {
        historyMessage.classList.add("message-info");
    }

    historyMessage.hidden = false;
}


/* =========================================
   4. AFFICHER LES STATISTIQUES
========================================= */

function afficherStatistiques(statistiques) {
    totalConnexions.textContent =
        statistiques.total ?? 0;

    connexionsReussies.textContent =
        statistiques.reussies ?? 0;

    connexionsEchouees.textContent =
        statistiques.echouees ?? 0;
}


/* =========================================
   5. CRÉER UNE CELLULE DU TABLEAU
========================================= */

function creerCellule(texte, classe = "") {
    const cellule = document.createElement("td");

    // textContent évite d'interpréter les données comme du HTML.
    cellule.textContent = texte ?? "Non renseigné";

    if (classe !== "") {
        cellule.className = classe;
    }

    return cellule;
}


/* =========================================
   6. FORMATER LA DATE
========================================= */

function formaterDate(dateSQL) {
    if (!dateSQL) {
        return "Date inconnue";
    }

    // La date MySQL arrive généralement sous la forme :
    // 2026-10-21 14:30:00

    const date = new Date(
        String(dateSQL).replace(" ", "T")
    );

    if (Number.isNaN(date.getTime())) {
        return String(dateSQL);
    }

    return new Intl.DateTimeFormat("fr-FR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit"
    }).format(date);
}


/* =========================================
   7. AFFICHER UNE CONNEXION DANS LE TABLEAU
========================================= */

function afficherConnexion(connexion) {
    const ligne = document.createElement("tr");

    /* Utilisateur */

    const celluleUtilisateur = document.createElement("td");
    celluleUtilisateur.className = "user-cell";

    const avatar = document.createElement("div");
    avatar.className = "user-cell-avatar";

    const icone = document.createElement("i");
    icone.className = "fa-solid fa-user";

    avatar.appendChild(icone);

    const informationsUtilisateur = document.createElement("div");
    informationsUtilisateur.className = "user-cell-name";

    const nomUtilisateur =
        connexion.nom_complet ||
        connexion.email_saisi ||
        "Utilisateur inconnu";

    informationsUtilisateur.textContent = nomUtilisateur;

    celluleUtilisateur.appendChild(avatar);
    celluleUtilisateur.appendChild(informationsUtilisateur);

    ligne.appendChild(celluleUtilisateur);


    /* E-mail */

    ligne.appendChild(
        creerCellule(
            connexion.email_saisi || "Non renseigné",
            "email-cell"
        )
    );


    /* Statut */

    const celluleStatut = document.createElement("td");

    const badgeStatut = document.createElement("span");
    badgeStatut.className = "status-badge";

    if (connexion.statut === "reussie") {
        badgeStatut.classList.add("status-success");
        badgeStatut.textContent = "Réussie";
    } else {
        badgeStatut.classList.add("status-failed");
        badgeStatut.textContent = "Échouée";
    }

    celluleStatut.appendChild(badgeStatut);
    ligne.appendChild(celluleStatut);


    /* Adresse IP */

    ligne.appendChild(
        creerCellule(
            connexion.adresse_ip || "Inconnue",
            "ip-cell"
        )
    );


    /* Navigateur */

    ligne.appendChild(
        creerCellule(
            connexion.navigateur || "Non renseigné",
            "browser-cell"
        )
    );


    /* Date */

    ligne.appendChild(
        creerCellule(
            formaterDate(connexion.date_connexion),
            "date-cell"
        )
    );


    historyTableBody.appendChild(ligne);
}


/* =========================================
   8. AFFICHER UN TABLEAU VIDE
========================================= */

function afficherTableauVide(message) {
    historyTableBody.replaceChildren();

    const ligne = document.createElement("tr");
    const cellule = document.createElement("td");

    cellule.colSpan = 6;
    cellule.className = "empty-cell";
    cellule.textContent = message;

    ligne.appendChild(cellule);
    historyTableBody.appendChild(ligne);
}


/* =========================================
   9. METTRE À JOUR LA PAGINATION
========================================= */

function mettreAJourPagination(pagination) {
    pageActuelle = Number(pagination.page) || 1;
    nombrePages = Number(pagination.total_pages) || 1;

    const totalResultats =
        Number(pagination.total_resultats) || 0;

    currentPage.textContent = pageActuelle;

    paginationInfo.textContent =
        `Page ${pageActuelle} sur ${nombrePages}`;

    resultCount.textContent =
        `${totalResultats} résultat${totalResultats > 1 ? "s" : ""}`;

    previousPage.disabled = pageActuelle <= 1;
    nextPage.disabled = pageActuelle >= nombrePages;
}


/* =========================================
   10. CHARGER LES CONNEXIONS DEPUIS L'API
========================================= */

async function chargerHistorique() {
    afficherMessage("Chargement de l'historique...");

    refreshButton.disabled = true;

    try {
        const recherche = searchInput.value.trim();
        const statut = statusFilter.value;
        const limite = limitSelect.value;

        const parametres = new URLSearchParams();

        parametres.set("recherche", recherche);
        parametres.set("statut", statut);
        parametres.set("page", pageActuelle);
        parametres.set("limite", limite);

        const url =
            "historique-connexions-api.php?" +
            parametres.toString();

        const reponse = await fetch(url, {
            method: "GET",
            headers: {
                "Accept": "application/json"
            },
            credentials: "same-origin"
        });

        if (!reponse.ok) {
            throw new Error(
                `Erreur HTTP ${reponse.status}`
            );
        }

        const donnees = await reponse.json();

        if (!donnees.succes) {
            throw new Error(
                donnees.message ||
                "Impossible de récupérer l'historique."
            );
        }

        // Afficher les statistiques.
        afficherStatistiques(donnees.statistiques || {});

        // Effacer les anciennes lignes.
        historyTableBody.replaceChildren();

        const connexions = Array.isArray(donnees.connexions)
            ? donnees.connexions
            : [];

        if (connexions.length === 0) {
            afficherTableauVide(
                "Aucune connexion ne correspond à votre recherche."
            );
        } else {
            connexions.forEach(function (connexion) {
                afficherConnexion(connexion);
            });
        }

        // Mettre à jour les boutons de navigation.
        mettreAJourPagination(donnees.pagination || {});

        afficherMessage(
            connexions.length > 0
                ? "Historique chargé avec succès."
                : "Aucun résultat trouvé.",
            connexions.length > 0 ? "succes" : "info"
        );

    } catch (erreur) {
        console.error(
            "Erreur lors du chargement de l'historique :",
            erreur
        );

        afficherTableauVide(
            "Impossible de charger l'historique."
        );

        afficherMessage(
            "Une erreur est survenue. Vérifiez votre connexion, votre session administrateur et l'API.",
            "erreur"
        );

    } finally {
        refreshButton.disabled = false;
    }
}


/* =========================================
   11. RECHERCHE AVEC UN PETIT DÉLAI
========================================= */

searchInput.addEventListener("input", function () {
    clearTimeout(minuterieRecherche);

    minuterieRecherche = setTimeout(function () {
        pageActuelle = 1;
        chargerHistorique();
    }, 300);
});


/* =========================================
   12. FILTRER PAR STATUT
========================================= */

statusFilter.addEventListener("change", function () {
    pageActuelle = 1;
    chargerHistorique();
});


/* =========================================
   13. CHANGER LE NOMBRE DE RÉSULTATS
========================================= */

limitSelect.addEventListener("change", function () {
    pageActuelle = 1;
    chargerHistorique();
});


/* =========================================
   14. BOUTON PRÉCÉDENT
========================================= */

previousPage.addEventListener("click", function () {
    if (pageActuelle > 1) {
        pageActuelle--;
        chargerHistorique();
    }
});


/* =========================================
   15. BOUTON SUIVANT
========================================= */

nextPage.addEventListener("click", function () {
    if (pageActuelle < nombrePages) {
        pageActuelle++;
        chargerHistorique();
    }
});


/* =========================================
   16. ACTUALISER L'HISTORIQUE
========================================= */

refreshButton.addEventListener("click", function () {
    chargerHistorique();
});


/* =========================================
   17. MENU LATÉRAL SUR MOBILE
========================================= */

if (sidebar && menuToggle) {
    menuToggle.addEventListener("click", function () {
        sidebar.classList.toggle("open");
    });

    // Fermer le menu après avoir sélectionné une page.
    const liensMenu = sidebar.querySelectorAll(".nav-link");

    liensMenu.forEach(function (lien) {
        lien.addEventListener("click", function () {
            sidebar.classList.remove("open");
        });
    });
}


/* =========================================
   18. CHARGEMENT INITIAL
========================================= */

chargerHistorique();
