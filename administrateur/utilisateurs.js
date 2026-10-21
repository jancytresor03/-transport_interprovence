
let utilisateurs = [];

const api = "utilisateurs-api.php";

// Récupérer les éléments HTML
const usersTableBody = document.getElementById("usersTableBody");
const searchInput = document.getElementById("searchInput");
const roleFilter = document.getElementById("roleFilter");
const statusFilter = document.getElementById("statusFilter");
const resultCount = document.getElementById("resultCount");
const emptyState = document.getElementById("emptyState");
const userModal = document.getElementById("userModal");
const userForm = document.getElementById("userForm");

// --------------------------------------------------
// 1. NORMALISER LES STATUTS
// --------------------------------------------------

function normaliserStatut(statut) {
    if (statut === "actif" || statut === "active") {
        return "active";
    }

    if (statut === "bloque") {
        return "bloque";
    }

    return "inactif";
}

// --------------------------------------------------
// 2. SÉCURISER LE TEXTE
// --------------------------------------------------

function securiser(texte) {
    return String(texte ?? "").replace(/[&<>"']/g, function (caractere) {
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

// --------------------------------------------------
// 3. CHARGER LES UTILISATEURS DEPUIS MYSQL
// --------------------------------------------------

async function chargerUtilisateurs() {
    try {
        const reponse = await fetch(api, {
            method: "GET",
            cache: "no-store"
        });

        const resultat = await reponse.json();

        if (!reponse.ok || resultat.succes !== true) {
            throw new Error(
                resultat.message || "Impossible de charger les utilisateurs."
            );
        }

        utilisateurs = resultat.utilisateurs.map(function (u) {
            return {
                ...u,
                nom: u.nom || "",
                prenom: u.prenom || "",
                email: u.email || "",
                telephone: u.telephone || "",
                role: String(u.role || "client").toLowerCase(),
                statut: normaliserStatut(u.statut)
            };
        });

        filtrerUtilisateurs();
        actualiserStatistiques();

    } catch (erreur) {
        console.error("Erreur de chargement :", erreur);

        alert(
            "Impossible de charger les utilisateurs. " +
            "Vérifiez utilisateurs-api.php et la connexion à MySQL."
        );
    }
}

// --------------------------------------------------
// 4. AFFICHER LE TABLEAU
// --------------------------------------------------

function afficherUtilisateurs(liste) {
    usersTableBody.innerHTML = "";

    resultCount.textContent =
        liste.length + (liste.length > 1
            ? " utilisateurs"
            : " utilisateur");

    emptyState.classList.toggle("show", liste.length === 0);

    liste.forEach(function (u) {
        const ligne = document.createElement("tr");

        const statut = normaliserStatut(u.statut);

        let statutTexte = "Inactif";

        if (statut === "active") {
            statutTexte = "Actif";
        } else if (statut === "bloque") {
            statutTexte = "Bloqué";
        }

        const roleTexte =
            u.role === "admin" ? "Administrateur" : "Client";

        const initiales =
            (u.prenom.charAt(0) + u.nom.charAt(0)).toUpperCase();

        const statutSuivant =
            statut === "active" ? "bloque" : "active";

        const texteBouton =
            statut === "active" ? "Bloquer" : "Débloquer";

        ligne.innerHTML = `
            <td>
                <div class="user-cell">
                    <div class="user-avatar">
                        ${securiser(initiales)}
                    </div>

                    <div>
                        <div class="user-name">
                            ${securiser(u.prenom)} ${securiser(u.nom)}
                        </div>

                        <div class="user-email">
                            ${securiser(u.email)}
                        </div>
                    </div>
                </div>
            </td>

            <td>${securiser(u.email)}</td>
            <td>${securiser(u.telephone)}</td>

            <td>
                <span class="role ${securiser(u.role)}">
                    ${roleTexte}
                </span>
            </td>

            <td>${securiser(formaterDate(u.dateInscription))}</td>

            <td>
                <span class="status ${securiser(statut)}">
                    ${statutTexte}
                </span>
            </td>

            <td>
                <div class="actions">
                    <button type="button"
                        class="action-button"
                        data-action="modifier"
                        data-id="${securiser(u.user_id)}"
                        title="Modifier">
                        <i class="fa-solid fa-pen"></i>
                    </button>

                    <button type="button"
                        class="action-button block"
                        data-action="statut"
                        data-id="${securiser(u.user_id)}"
                        data-statut="${statutSuivant}"
                        title="${texteBouton}">
                        <i class="fa-solid ${
                            statut === "active"
                                ? "fa-lock"
                                : "fa-lock-open"
                        }"></i>
                    </button>
                </div>
            </td>
        `;

        usersTableBody.appendChild(ligne);
    });
}

// --------------------------------------------------
// 5. ACTUALISER LES STATISTIQUES
// --------------------------------------------------

function actualiserStatistiques() {
    document.getElementById("totalUtilisateurs").textContent =
        utilisateurs.length;

    document.getElementById("utilisateursActifs").textContent =
        utilisateurs.filter(function (u) {
            return normaliserStatut(u.statut) === "active";
        }).length;

    document.getElementById("utilisateursBloques").textContent =
        utilisateurs.filter(function (u) {
            return normaliserStatut(u.statut) === "bloque";
        }).length;

    document.getElementById("nombreAdmins").textContent =
        utilisateurs.filter(function (u) {
            return u.role === "admin";
        }).length;
}

// --------------------------------------------------
// 6. RECHERCHE ET FILTRES
// --------------------------------------------------

function filtrerUtilisateurs() {
    const recherche = searchInput.value.toLowerCase().trim();
    const role = roleFilter.value;
    const statut = statusFilter.value;

    const resultat = utilisateurs.filter(function (u) {
        const texte = (
            u.nom + " " +
            u.prenom + " " +
            u.email + " " +
            u.telephone
        ).toLowerCase();

        const correspondRole =
            role === "tous" || u.role === role;

        const statutNormalise =
            statut === "actif" ? "active" : statut;

        const correspondStatut =
            statut === "tous" ||
            normaliserStatut(u.statut) === statutNormalise;

        return texte.includes(recherche) &&
            correspondRole &&
            correspondStatut;
    });

    afficherUtilisateurs(resultat);
}

// --------------------------------------------------
// 7. FORMATER LA DATE
// --------------------------------------------------

function formaterDate(date) {
    if (!date) {
        return "-";
    }

    const morceaux = String(date).substring(0, 10).split("-");

    if (morceaux.length !== 3) {
        return date;
    }

    return morceaux[2] + "/" +
        morceaux[1] + "/" +
        morceaux[0];
}

// --------------------------------------------------
// 8. ENVOYER LES DONNÉES À PHP
// --------------------------------------------------

async function envoyerAction(donnees) {
    const reponse = await fetch(api, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(donnees)
    });

    const resultat = await reponse.json();

    if (!reponse.ok || resultat.succes !== true) {
        throw new Error(
            resultat.message || "L'opération a échoué."
        );
    }

    return resultat;
}

// --------------------------------------------------
// 9. OUVRIR LE FORMULAIRE D'AJOUT
// --------------------------------------------------

document.getElementById("addUserButton").addEventListener(
    "click",
    function () {
        userForm.reset();

        document.getElementById("userId").value = "";

        document.getElementById("role").disabled = false;
        document.getElementById("statut").disabled = false;

        document.getElementById("role").value = "client";
        document.getElementById("statut").value = "actif";

        document.getElementById("passwordGroup").style.display = "block";
        document.getElementById("password").required = true;
        document.getElementById("password").value = "";

        document.getElementById("modalTitle").textContent =
            "Ajouter un utilisateur";

        // Utiliser la classe CSS pour ouvrir le formulaire
        userModal.classList.add("show");
    }
);

// --------------------------------------------------
// 10. BOUTONS DU TABLEAU
// --------------------------------------------------

usersTableBody.addEventListener("click", async function (event) {
    const bouton = event.target.closest("button[data-action]");

    if (!bouton) {
        return;
    }

    const id = bouton.dataset.id;

    const utilisateur = utilisateurs.find(function (u) {
        return u.user_id === id;
    });

    if (!utilisateur) {
        return;
    }

    // MODIFIER UN UTILISATEUR
    if (bouton.dataset.action === "modifier") {
        userForm.reset();

        document.getElementById("modalTitle").textContent =
            "Modifier l'utilisateur";

        document.getElementById("userId").value = utilisateur.user_id;
        document.getElementById("nom").value = utilisateur.nom;
        document.getElementById("prenom").value = utilisateur.prenom;
        document.getElementById("email").value = utilisateur.email;
        document.getElementById("telephone").value = utilisateur.telephone;

        document.getElementById("role").value = utilisateur.role;
        document.getElementById("role").disabled = true;

        const statut = normaliserStatut(utilisateur.statut);

        document.getElementById("statut").value =
            statut === "active" ? "actif" : statut;

        document.getElementById("statut").disabled = true;

        // Le mot de passe est inutile pour une modification
        document.getElementById("passwordGroup").style.display = "none";
        document.getElementById("password").required = false;
        document.getElementById("password").value = "";

        userModal.classList.add("show");
    }

    // BLOQUER OU DÉBLOQUER
    if (bouton.dataset.action === "statut") {
        const nouveauStatut = bouton.dataset.statut;

        const question = nouveauStatut === "bloque"
            ? "Voulez-vous bloquer cet utilisateur ?"
            : "Voulez-vous débloquer cet utilisateur ?";

        if (!confirm(question)) {
            return;
        }

        try {
            bouton.disabled = true;

            await envoyerAction({
                action: "statut",
                user_id: id,
                statut: nouveauStatut
            });

            await chargerUtilisateurs();

            alert("Le statut a été mis à jour.");

        } catch (erreur) {
            alert(erreur.message);

        } finally {
            bouton.disabled = false;
        }
    }
});

// --------------------------------------------------
// 11. ENREGISTRER UN UTILISATEUR
// --------------------------------------------------

userForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const id = document.getElementById("userId").value.trim();
    const motDePasse = document.getElementById("password").value;

    // Déterminer s'il s'agit d'un ajout ou d'une modification
    const action = id === "" ? "ajouter" : "modifier";

    const donnees = {
        action: action,
        user_id: id,
        nom: document.getElementById("nom").value.trim(),
        prenom: document.getElementById("prenom").value.trim(),
        email: document.getElementById("email").value.trim(),
        telephone: document.getElementById("telephone").value.trim(),
        role: document.getElementById("role").value,
        statut: document.getElementById("statut").value
    };

    // Envoyer le mot de passe uniquement lors de l'ajout
    if (action === "ajouter") {
        donnees.password = motDePasse;
    }

    // Vérifier les champs obligatoires
    if (
        donnees.nom === "" ||
        donnees.prenom === "" ||
        donnees.email === "" ||
        donnees.telephone === "" ||
        donnees.role === "" ||
        donnees.statut === "" ||
        (action === "ajouter" && motDePasse.trim() === "")
    ) {
        alert("Tous les champs obligatoires doivent être remplis.");
        return;
    }

    try {
        const boutonEnregistrer =
            userForm.querySelector('button[type="submit"]');

        if (boutonEnregistrer) {
            boutonEnregistrer.disabled = true;
        }

        const resultat = await envoyerAction(donnees);

        fermerModal();

        await chargerUtilisateurs();

        alert(
            resultat.message ||
            (action === "ajouter"
                ? "Utilisateur ajouté avec succès."
                : "Utilisateur modifié avec succès.")
        );

    } catch (erreur) {
        console.error("Erreur d'enregistrement :", erreur);
        alert(erreur.message);

    } finally {
        const boutonEnregistrer =
            userForm.querySelector('button[type="submit"]');

        if (boutonEnregistrer) {
            boutonEnregistrer.disabled = false;
        }
    }
});

// --------------------------------------------------
// 12. FERMER ET RÉINITIALISER LE FORMULAIRE
// --------------------------------------------------

function fermerModal() {
    userModal.classList.remove("show");

    // Réinitialiser le formulaire
    userForm.reset();

    document.getElementById("userId").value = "";

    // Réactiver les champs pour le prochain ajout
    document.getElementById("role").disabled = false;
    document.getElementById("statut").disabled = false;

    // Masquer le mot de passe par défaut
    document.getElementById("passwordGroup").style.display = "none";
    document.getElementById("password").required = false;
    document.getElementById("password").value = "";
}

// Croix de fermeture
document.getElementById("closeModal").addEventListener(
    "click",
    fermerModal
);

// Bouton Annuler
document.getElementById("cancelModal").addEventListener(
    "click",
    fermerModal
);

// Fermer en cliquant sur le fond du formulaire
userModal.addEventListener("click", function (event) {
    if (event.target === userModal) {
        fermerModal();
    }
});

// --------------------------------------------------
// 13. RECHERCHE ET FILTRES
// --------------------------------------------------

searchInput.addEventListener("input", filtrerUtilisateurs);
roleFilter.addEventListener("change", filtrerUtilisateurs);
statusFilter.addEventListener("change", filtrerUtilisateurs);

// Réinitialiser les filtres
document.getElementById("resetFilters").addEventListener(
    "click",
    function () {
        searchInput.value = "";
        roleFilter.value = "tous";
        statusFilter.value = "tous";

        filtrerUtilisateurs();
    }
);

// Actualiser la liste
document.getElementById("refreshButton").addEventListener(
    "click",
    chargerUtilisateurs
);

// --------------------------------------------------
// 14. MENU MOBILE
// --------------------------------------------------

document.getElementById("menuButton").addEventListener(
    "click",
    function () {
        document.querySelector(".sidebar").classList.toggle("open");
    }
);

// --------------------------------------------------
// 15. DÉCONNEXION
// --------------------------------------------------

document.getElementById("logoutButton").addEventListener(
    "click",
    function () {
        if (confirm("Voulez-vous vous déconnecter ?")) {
            window.location.href = "../logout.php";
        }
    }
);

// --------------------------------------------------
// 16. DÉMARRER LA PAGE
// --------------------------------------------------

chargerUtilisateurs();