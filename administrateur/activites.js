
/* =========================================
   JOURNAL DES ACTIVITES
   Burundi Transport
========================================= */

// Adresse de notre API PHP
const URL_API = "activites-api.php";

// Tableau qui contiendra les activités venant de MySQL
let activites = [];


/* =========================================
   ELEMENTS HTML
========================================= */

const activitiesTableBody =
    document.getElementById("activitiesTableBody");

const emptyState =
    document.getElementById("emptyState");

const searchInput =
    document.getElementById("searchInput");

const typeFilter =
    document.getElementById("typeFilter");

const userFilter =
    document.getElementById("userFilter");

const totalActivites =
    document.getElementById("totalActivites");

const totalConnexions =
    document.getElementById("totalConnexions");

const totalModifications =
    document.getElementById("totalModifications");

const totalSuppressions =
    document.getElementById("totalSuppressions");

const resultCount =
    document.getElementById("resultCount");

const refreshButton =
    document.getElementById("refreshButton");

const clearButton =
    document.getElementById("clearButton");

const menuButton =
    document.getElementById("menuButton");

const sidebar =
    document.getElementById("sidebar");

const logoutButton =
    document.getElementById("logoutButton");

const notificationBadge =
    document.getElementById("notificationBadge");


/* =========================================
   CONVERTIR LES DONNEES DE MYSQL
========================================= */

function convertirActivite(donnee) {

    let type = donnee.type_activite;

    // Adapter les types de MySQL à ceux de la page
    if (type === "creation") {
        type = "ajout";
    }

    if (
        type === "paiement" ||
        type === "securite" ||
        type === "autre"
    ) {
        type = "autre";
    }

    const nom =
        (donnee.prenom || "") + " " + (donnee.nom || "");

    const utilisateur =
        nom.trim() || "Utilisateur inconnu";

    // La table users doit fournir le rôle pour
    // que le filtre par rôle soit précis.
    const role =
        donnee.role ||
        donnee.nom_role ||
        donnee.role_nom ||
        donnee.role_id ||
        "Utilisateur";

    const dateHeure =
        donnee.date_creation || "";

    const parties =
        dateHeure.split(" ");

    return {
        id: donnee.activite_id,
        userId: donnee.user_id,
        utilisateur: utilisateur,
        role: role,
        type: type,
        typeOriginal: donnee.type_activite,
        description: donnee.description || "",
        date: parties[0] || "",
        heure: parties[1] || "",
        statut: "Enregistrée",
        adresseIp: donnee.adresse_ip || ""
    };
}


/* =========================================
   CHARGER LES ACTIVITES DEPUIS MYSQL
========================================= */

async function chargerActivites() {

    try {

        const reponse = await fetch(URL_API, {
            method: "GET",
            headers: {
                "Accept": "application/json"
            },
            cache: "no-store"
        });

        if (!reponse.ok) {
            throw new Error("Erreur lors du chargement.");
        }

        const donnees = await reponse.json();

        if (!Array.isArray(donnees)) {
            throw new Error(
                donnees.erreur || "Réponse incorrecte de l'API."
            );
        }

        activites = donnees.map(convertirActivite);

        actualiserStatistiques();
        filtrerActivites();

    } catch (erreur) {

        console.error(erreur);

        activitiesTableBody.innerHTML = "";

        emptyState.style.display = "block";
        emptyState.textContent =
            "Impossible de charger les activités. Vérifiez la connexion au serveur.";

        resultCount.textContent = "Erreur de chargement";

    }

}


/* =========================================
   ICONES DES ACTIONS
========================================= */

function obtenirIcone(type) {

    if (type === "connexion") {
        return "fa-right-to-bracket";
    }

    if (type === "deconnexion") {
        return "fa-right-from-bracket";
    }

    if (type === "ajout") {
        return "fa-plus";
    }

    if (type === "modification") {
        return "fa-pen";
    }

    if (type === "suppression") {
        return "fa-trash";
    }

    if (type === "reservation") {
        return "fa-calendar-check";
    }

    if (type === "paiement") {
        return "fa-money-bill";
    }

    if (type === "securite") {
        return "fa-shield-halved";
    }

    return "fa-circle-info";
}


/* =========================================
   NOMS DES ACTIONS
========================================= */

function obtenirNomAction(type) {

    if (type === "connexion") {
        return "Connexion";
    }

    if (type === "deconnexion") {
        return "Déconnexion";
    }

    if (type === "ajout") {
        return "Création / ajout";
    }

    if (type === "creation") {
        return "Création";
    }

    if (type === "modification") {
        return "Modification";
    }

    if (type === "suppression") {
        return "Suppression";
    }

    if (type === "reservation") {
        return "Réservation";
    }

    if (type === "paiement") {
        return "Paiement";
    }

    if (type === "securite") {
        return "Sécurité";
    }

    return "Autre";
}


/* =========================================
   SECURISER LE TEXTE HTML
========================================= */

function securiserTexte(texte) {

    const element = document.createElement("span");

    element.textContent = String(texte ?? "");

    return element.innerHTML;
}


/* =========================================
   AFFICHER LES ACTIVITES
========================================= */

function afficherActivites(liste) {

    activitiesTableBody.innerHTML = "";

    if (liste.length === 0) {

        emptyState.style.display = "block";
        resultCount.textContent = "0 activité";

        return;
    }

    emptyState.style.display = "none";

    resultCount.textContent =
        liste.length === 1
            ? "1 activité"
            : liste.length + " activités";

    liste.forEach(function (activite) {

        const ligne = document.createElement("tr");

        const initiales =
            obtenirInitiales(activite.utilisateur);

        const icone =
            obtenirIcone(activite.typeOriginal);

        const nomAction =
            obtenirNomAction(activite.typeOriginal);

        const idSecurise =
            securiserTexte(activite.id);

        ligne.innerHTML = `
            <td>
                <div class="user-cell">
                    <div class="user-avatar">
                        ${securiserTexte(initiales)}
                    </div>

                    <div>
                        <span class="user-name">
                            ${securiserTexte(activite.utilisateur)}
                        </span>

                        <span class="user-role">
                            ${securiserTexte(activite.role)}
                        </span>
                    </div>
                </div>
            </td>

            <td>
                <span class="action-cell action-${securiserTexte(activite.type)}">
                    <i class="fa-solid ${icone}"></i>
                    ${securiserTexte(nomAction)}
                </span>
            </td>

            <td>
                <div class="description">
                    ${securiserTexte(activite.description)}
                </div>
            </td>

            <td>
                <div class="activity-date">
                    ${securiserTexte(activite.date)}

                    <span>
                        ${securiserTexte(activite.heure)}
                    </span>
                </div>
            </td>

            <td>
                <span class="status success">
                    <i class="fa-solid fa-circle"></i>
                    ${securiserTexte(activite.statut)}
                </span>
            </td>

            <td>
                <button
                    class="delete-activity"
                    type="button"
                    title="Supprimer"
                    data-id="${idSecurise}"
                >
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        `;

        activitiesTableBody.appendChild(ligne);

    });

}


/* =========================================
   OBTENIR LES INITIALES
========================================= */

function obtenirInitiales(nom) {

    const morceaux = nom.trim().split(/\s+/);

    if (morceaux.length === 0 || !morceaux[0]) {
        return "?";
    }

    if (morceaux.length === 1) {
        return morceaux[0].substring(0, 2).toUpperCase();
    }

    return (
        morceaux[0].charAt(0) +
        morceaux[morceaux.length - 1].charAt(0)
    ).toUpperCase();

}


/* =========================================
   ACTUALISER LES STATISTIQUES
========================================= */

function actualiserStatistiques() {

    totalActivites.textContent = activites.length;

    const connexions = activites.filter(function (activite) {

        return (
            activite.typeOriginal === "connexion" ||
            activite.typeOriginal === "deconnexion"
        );

    }).length;

    const modifications = activites.filter(function (activite) {

        return activite.typeOriginal === "modification";

    }).length;

    const suppressions = activites.filter(function (activite) {

        return activite.typeOriginal === "suppression";

    }).length;

    totalConnexions.textContent = connexions;
    totalModifications.textContent = modifications;
    totalSuppressions.textContent = suppressions;

}


/* =========================================
   FILTRER LES ACTIVITES
========================================= */

function filtrerActivites() {

    const recherche = searchInput.value
        .trim()
        .toLowerCase();

    const typeChoisi = typeFilter.value;
    const roleChoisi = userFilter.value;

    const resultat = activites.filter(function (activite) {

        const texte = (
            activite.utilisateur + " " +
            activite.role + " " +
            activite.description + " " +
            obtenirNomAction(activite.typeOriginal) + " " +
            activite.adresseIp
        ).toLowerCase();

        const correspondRecherche =
            texte.includes(recherche);

        const correspondType =
            typeChoisi === "tous" ||
            typeChoisi === activite.type ||
            typeChoisi === activite.typeOriginal;

        const correspondRole =
            roleChoisi === "tous" ||
            roleChoisi === activite.role;

        return (
            correspondRecherche &&
            correspondType &&
            correspondRole
        );

    });

    afficherActivites(resultat);

}


/* =========================================
   SUPPRIMER UNE ACTIVITE
========================================= */

// Cette fonction exige une action POST dans activites-api.php.
async function supprimerActivite(id) {

    const confirmation = confirm(
        "Voulez-vous vraiment supprimer cette activité du journal ?"
    );

    if (!confirmation) {
        return;
    }

    try {

        const reponse = await fetch(URL_API, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify({
                action: "supprimer",
                activite_id: id
            })
        });

        const resultat = await reponse.json();

        if (!reponse.ok || resultat.succes !== true) {
            throw new Error(
                resultat.erreur ||
                "La suppression n'est pas prise en charge par l'API."
            );
        }

        alert("Activité supprimée.");

        await chargerActivites();

    } catch (erreur) {

        console.error(erreur);

        alert(
            "La suppression a échoué. Vérifiez activites-api.php."
        );

    }

}


/* =========================================
   VIDER LE JOURNAL
========================================= */

// Cette fonction exige une action POST dans activites-api.php.
async function viderJournal() {

    if (activites.length === 0) {

        alert("Le journal est déjà vide.");

        return;
    }

    const confirmation = confirm(
        "Voulez-vous vraiment supprimer toutes les activités ?"
    );

    if (!confirmation) {
        return;
    }

    try {

        const reponse = await fetch(URL_API, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify({
                action: "vider"
            })
        });

        const resultat = await reponse.json();

        if (!reponse.ok || resultat.succes !== true) {
            throw new Error(
                resultat.erreur ||
                "Le vidage du journal n'est pas pris en charge par l'API."
            );
        }

        alert("Le journal a été vidé.");

        await chargerActivites();

    } catch (erreur) {

        console.error(erreur);

        alert(
            "Impossible de vider le journal. Vérifiez activites-api.php."
        );

    }

}


/* =========================================
   ACTUALISER LA PAGE
========================================= */

function actualiserPage() {

    chargerActivites();

}


/* =========================================
   EVENEMENTS DE RECHERCHE ET FILTRES
========================================= */

if (searchInput) {
    searchInput.addEventListener("input", filtrerActivites);
}

if (typeFilter) {
    typeFilter.addEventListener("change", filtrerActivites);
}

if (userFilter) {
    userFilter.addEventListener("change", filtrerActivites);
}

if (refreshButton) {
    refreshButton.addEventListener("click", actualiserPage);
}

if (clearButton) {
    clearButton.addEventListener("click", viderJournal);
}

if (activitiesTableBody) {

    activitiesTableBody.addEventListener("click", function (event) {

        const bouton = event.target.closest(".delete-activity");

        if (!bouton) {
            return;
        }

        supprimerActivite(bouton.dataset.id);

    });

}


/* =========================================
   MENU MOBILE
========================================= */

if (menuButton && sidebar) {

    menuButton.addEventListener("click", function () {

        sidebar.classList.toggle("open");

    });

}


/* =========================================
   DECONNEXION
========================================= */

if (logoutButton) {

    logoutButton.addEventListener("click", function () {

        const confirmation = confirm(
            "Voulez-vous vous déconnecter ?"
        );

        if (confirmation) {

            // Adapter cette adresse si ton projet
            // possède une page PHP de déconnexion.
            window.location.href = "../index.html";

        }

    });

}


/* =========================================
   NOTIFICATIONS
========================================= */

function afficherNombreNotifications() {

    if (!notificationBadge) {
        return;
    }

    try {

        const donnees = localStorage.getItem(
            "burundiTransportNotifications"
        );

        if (!donnees) {

            notificationBadge.style.display = "none";

            return;
        }

        const notifications = JSON.parse(donnees);

        if (!Array.isArray(notifications)) {
            notificationBadge.style.display = "none";
            return;
        }

        const nonLues = notifications.filter(
            function (notification) {

                return notification.lue === false;

            }
        ).length;

        if (nonLues === 0) {

            notificationBadge.style.display = "none";

        } else {

            notificationBadge.style.display = "flex";
            notificationBadge.textContent = nonLues;

        }

    } catch (erreur) {

        console.error(
            "Erreur lors du chargement des notifications :",
            erreur
        );

        notificationBadge.style.display = "none";

    }

}


/* =========================================
   INITIALISATION
========================================= */

chargerActivites();

afficherNombreNotifications();
