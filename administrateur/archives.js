
/* =========================================
   CONFIGURATION
========================================= */

const API_ARCHIVES = "archives-api.php";
const API_ACTIONS = "archives-action.php";

/* =========================================
   VARIABLES
========================================= */

let archives = [];
let chargementEnCours = false;

/* =========================================
   SÉCURISER L'AFFICHAGE HTML
========================================= */

function echapperHTML(valeur) {
    return String(valeur ?? "").replace(/[&<>"']/g, caractere => {
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

/* =========================================
   NOMS DES TYPES ET DES STATUTS
========================================= */

function nomType(type) {
    const types = {
        trajet: "Trajet",
        reservation: "Réservation",
        paiement: "Paiement",
        document: "Document",
        utilisateur: "Utilisateur",
        autre: "Autre"
    };

    return types[type] || type || "Inconnu";
}

function nomStatut(statut) {
    return statut === "restauree"
        ? "Restaurée"
        : "À restaurer";
}

/* =========================================
   CHARGER LES ARCHIVES DEPUIS MYSQL
========================================= */

async function chargerArchives() {
    if (chargementEnCours) {
        return;
    }

    chargementEnCours = true;

    const tbody = document.getElementById("listeArchives");

    tbody.innerHTML = `
        <tr>
            <td colspan="7">Chargement des archives...</td>
        </tr>
    `;

    try {
        const reponse = await fetch(API_ARCHIVES, {
            method: "GET",
            headers: {
                "Accept": "application/json"
            },
            credentials: "same-origin",
            cache: "no-store"
        });

        const resultat = await reponse.json();

        if (!reponse.ok || resultat.succes !== true) {
            throw new Error(
                resultat.message || "Impossible de charger les archives."
            );
        }

        archives = Array.isArray(resultat.archives)
            ? resultat.archives
            : [];

        afficherStatistiques(resultat.statistiques);
        afficherArchives();

    } catch (erreur) {
        console.error("Erreur de chargement des archives :", erreur);

        tbody.innerHTML = `
            <tr>
                <td colspan="7">
                    Impossible de charger les archives.
                    Vérifie archives-api.php et ta connexion.
                </td>
            </tr>
        `;

        document.getElementById("messageVide")
            .classList.add("hidden");

        document.getElementById("nombreResultats").textContent =
            "Erreur de chargement";

    } finally {
        chargementEnCours = false;
    }
}

/* =========================================
   AFFICHER LES STATISTIQUES
========================================= */

function afficherStatistiques(stats) {
    stats = stats || {};

    document.getElementById("totalArchives").textContent =
        Number(stats.total || 0);

    document.getElementById("archivesActives").textContent =
        Number(stats.actives || 0);

    document.getElementById("archivesRestaurees").textContent =
        Number(stats.restaurees || 0);
}

/* =========================================
   RECHERCHER ET FILTRER LES ARCHIVES
========================================= */

function filtrerArchives() {
    const recherche = document
        .getElementById("rechercheArchive")
        .value
        .trim()
        .toLowerCase();

    const typeChoisi =
        document.getElementById("filtreType").value;

    const statutChoisi =
        document.getElementById("filtreStatut").value;

    return archives.filter(archive => {
        const texteRecherche = [
            archive.archive_id,
            archive.type_donnee,
            archive.table_origine,
            archive.id_origine,
            archive.raison,
            archive.user_id
        ].join(" ").toLowerCase();

        const correspondRecherche =
            texteRecherche.includes(recherche);

        const correspondType =
            typeChoisi === "" ||
            archive.type_donnee === typeChoisi;

        const correspondStatut =
            statutChoisi === "" ||
            archive.statut === statutChoisi;

        return correspondRecherche &&
               correspondType &&
               correspondStatut;
    });
}

/* =========================================
   AFFICHER LES ARCHIVES DANS LE TABLEAU
========================================= */

function afficherArchives() {
    const tbody = document.getElementById("listeArchives");
    const messageVide = document.getElementById("messageVide");

    const resultats = filtrerArchives();

    tbody.innerHTML = "";

    if (resultats.length === 0) {
        messageVide.classList.remove("hidden");
    } else {
        messageVide.classList.add("hidden");
    }

    resultats.forEach(archive => {
        const ligne = document.createElement("tr");

        const statutClasse =
            archive.statut === "restauree"
                ? "restauree"
                : "archive";

        const boutonRestauration =
            archive.statut === "archive"
                ? `
                    <button
                        type="button"
                        class="btn-action btn-restore"
                        title="Restaurer cette archive"
                        aria-label="Restaurer cette archive"
                        data-action="restaurer"
                        data-id="${echapperHTML(archive.archive_id)}">
                        <i class="fa-solid fa-rotate-left"></i>
                    </button>
                  `
                : `
                    <button
                        type="button"
                        class="btn-action btn-restore"
                        title="Cette archive est déjà restaurée"
                        aria-label="Déjà restaurée"
                        disabled>
                        <i class="fa-solid fa-check"></i>
                    </button>
                  `;

        ligne.innerHTML = `
            <td class="id-cell">
                ${echapperHTML(archive.archive_id)}
            </td>

            <td>
                ${echapperHTML(nomType(archive.type_donnee))}
            </td>

            <td>
                ${echapperHTML(archive.table_origine)}
            </td>

            <td>
                ${echapperHTML(archive.id_origine)}
            </td>

            <td>
                ${echapperHTML(archive.date_archivage)}
            </td>

            <td>
                <span class="status ${statutClasse}">
                    ${echapperHTML(nomStatut(archive.statut))}
                </span>
            </td>

            <td>
                <div class="actions">

                    <button
                        type="button"
                        class="btn-action btn-details"
                        title="Voir les détails"
                        aria-label="Voir les détails"
                        data-action="details"
                        data-id="${echapperHTML(archive.archive_id)}">
                        <i class="fa-solid fa-eye"></i>
                    </button>

                    ${boutonRestauration}

                    <button
                        type="button"
                        class="btn-action btn-delete"
                        title="Supprimer définitivement cette archive"
                        aria-label="Supprimer cette archive"
                        data-action="supprimer"
                        data-id="${echapperHTML(archive.archive_id)}">
                        <i class="fa-solid fa-trash"></i>
                    </button>

                </div>
            </td>
        `;

        tbody.appendChild(ligne);
    });

    document.getElementById("nombreResultats").textContent =
        resultats.length +
        (resultats.length > 1 ? " résultats" : " résultat");
}

/* =========================================
   AFFICHER LES DÉTAILS D'UNE ARCHIVE
========================================= */

function afficherDetails(id) {
    const archive = archives.find(
        element => element.archive_id === id
    );

    if (!archive) {
        alert("Archive introuvable.");
        return;
    }

    let donnees = archive.donnees_archivees;

    // Convertir le JSON de MySQL en objet JavaScript
    if (typeof donnees === "string") {
        try {
            donnees = JSON.parse(donnees);
        } catch (erreur) {
            console.error("Données JSON invalides :", erreur);
        }
    }

    const contenu = document.getElementById("contenuDetails");

    contenu.innerHTML = `
        <div class="detail-row">
            <span class="detail-label">Identifiant de l'archive</span>
            <span class="detail-value">
                ${echapperHTML(archive.archive_id)}
            </span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Utilisateur associé</span>
            <span class="detail-value">
                ${echapperHTML(archive.user_id || "Non renseigné")}
            </span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Type de donnée</span>
            <span class="detail-value">
                ${echapperHTML(nomType(archive.type_donnee))}
            </span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Table d'origine</span>
            <span class="detail-value">
                ${echapperHTML(archive.table_origine)}
            </span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Identifiant d'origine</span>
            <span class="detail-value">
                ${echapperHTML(archive.id_origine)}
            </span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Raison de l'archivage</span>
            <span class="detail-value">
                ${echapperHTML(archive.raison || "Non précisée")}
            </span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Date d'archivage</span>
            <span class="detail-value">
                ${echapperHTML(archive.date_archivage)}
            </span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Date de restauration</span>
            <span class="detail-value">
                ${echapperHTML(
                    archive.date_restauration || "Non restaurée"
                )}
            </span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Données archivées</span>
            <pre class="json-data">${echapperHTML(
                JSON.stringify(donnees, null, 2)
            )}</pre>
        </div>
    `;

    document.getElementById("modalDetails")
        .classList.remove("hidden");
}

/* =========================================
   FERMER LA FENÊTRE DES DÉTAILS
========================================= */

function fermerDetails() {
    document.getElementById("modalDetails")
        .classList.add("hidden");
}

/* =========================================
   RESTAURER OU SUPPRIMER UNE ARCHIVE
========================================= */

async function executerActionArchive(action, id, bouton) {
    let messageConfirmation = "";

    if (action === "restaurer") {
        messageConfirmation =
            "Voulez-vous restaurer cette donnée dans sa table d'origine ?";

    } else if (action === "supprimer") {
        messageConfirmation =
            "Voulez-vous supprimer définitivement cette archive ?\n\n" +
            "Cette action supprime l'enregistrement de la table archives. " +
            "Elle ne supprime pas automatiquement la donnée d'origine.";
    } else {
        return;
    }

    if (!confirm(messageConfirmation)) {
        return;
    }

    const boutonOriginal = bouton.innerHTML;

    bouton.disabled = true;
    bouton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

    try {
        const reponse = await fetch(API_ACTIONS, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            credentials: "same-origin",
            body: JSON.stringify({
                action: action,
                archive_id: id
            })
        });

        const resultat = await reponse.json();

        if (!reponse.ok || resultat.succes !== true) {
            throw new Error(
                resultat.message || "L'opération a échoué."
            );
        }

        alert(resultat.message || "Opération réussie.");

        // Actualiser la liste et les statistiques depuis MySQL
        await chargerArchives();

    } catch (erreur) {
        console.error("Erreur pendant l'action :", erreur);

        alert(
            erreur.message ||
            "Impossible de contacter le serveur."
        );

        bouton.disabled = false;
        bouton.innerHTML = boutonOriginal;
    }
}

/* =========================================
   GÉRER LES CLICS SUR LES ACTIONS
========================================= */

document.getElementById("listeArchives").addEventListener(
    "click",
    function (evenement) {
        const bouton = evenement.target.closest(
            "button[data-action]"
        );

        if (!bouton || bouton.disabled) {
            return;
        }

        const id = bouton.dataset.id;
        const action = bouton.dataset.action;

        if (action === "details") {
            afficherDetails(id);

        } else if (
            action === "restaurer" ||
            action === "supprimer"
        ) {
            executerActionArchive(action, id, bouton);
        }
    }
);

/* =========================================
   RECHERCHE ET FILTRES
========================================= */

document.getElementById("rechercheArchive").addEventListener(
    "input",
    afficherArchives
);

document.getElementById("filtreType").addEventListener(
    "change",
    afficherArchives
);

document.getElementById("filtreStatut").addEventListener(
    "change",
    afficherArchives
);

/* Réinitialiser tous les filtres */
document.getElementById("reinitialiserFiltres").addEventListener(
    "click",
    function () {
        document.getElementById("rechercheArchive").value = "";
        document.getElementById("filtreType").value = "";
        document.getElementById("filtreStatut").value = "";

        afficherArchives();
    }
);

/* =========================================
   FERMER LA FENÊTRE DES DÉTAILS
========================================= */

document.getElementById("fermerModal").addEventListener(
    "click",
    fermerDetails
);

document.getElementById("fermerDetails").addEventListener(
    "click",
    fermerDetails
);

/* Fermer en cliquant sur l'arrière-plan */
document.getElementById("modalDetails").addEventListener(
    "click",
    function (evenement) {
        if (evenement.target === this) {
            fermerDetails();
        }
    }
);

/* Fermer avec la touche Échap */
document.addEventListener("keydown", function (evenement) {
    if (evenement.key === "Escape") {
        fermerDetails();
    }
});

/* =========================================
   PREMIER CHARGEMENT
========================================= */

chargerArchives();
