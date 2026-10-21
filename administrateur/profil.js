
const API_PROFIL = "profil-api.php";

// Profil chargé depuis la base de données.
let profil = null;

// Champs du profil.
const nomInput = document.getElementById("nom");
const prenomInput = document.getElementById("prenom");
const emailInput = document.getElementById("email");
const telephoneInput = document.getElementById("telephone");
const adresseInput = document.getElementById("adresse");

const profileForm = document.getElementById("profileForm");
const cancelProfileButton = document.getElementById("cancelProfileButton");

// Double authentification.
const twoFactorButton = document.getElementById("twoFactorButton");
const twoFactorText = document.getElementById("twoFactorText");

// Affichage du profil.
const profileAvatar = document.getElementById("profileAvatar");
const profileFullName = document.getElementById("profileFullName");
const profileEmail = document.getElementById("profileEmail");

const sidebarAvatar = document.getElementById("sidebarAvatar");
const sidebarName = document.getElementById("sidebarName");
const sidebarEmail = document.getElementById("sidebarEmail");

const headerAvatar = document.getElementById("headerAvatar");
const headerName = document.getElementById("headerName");

// Changement de mot de passe.
const passwordButton = document.getElementById("passwordButton");
const passwordModal = document.getElementById("passwordModal");
const passwordForm = document.getElementById("passwordForm");

const oldPasswordInput = document.getElementById("oldPassword");
const newPasswordInput = document.getElementById("newPassword");
const confirmPasswordInput = document.getElementById("confirmPassword");

const closePasswordModal = document.getElementById("closePasswordModal");
const cancelPasswordButton = document.getElementById("cancelPasswordButton");

// Menu mobile.
const menuButton = document.getElementById("menuButton");
const sidebar = document.getElementById("sidebar");

/*
==================================================
1. LIRE UNE RÉPONSE JSON
==================================================
*/

async function lireReponseJSON(reponse) {
    try {
        return await reponse.json();
    } catch (erreur) {
        throw new Error(
            "Réponse invalide du serveur. Vérifiez le fichier PHP."
        );
    }
}

/*
==================================================
2. CHARGER LE PROFIL
==================================================
*/

async function chargerProfil() {
    try {
        const reponse = await fetch(
            API_PROFIL + "?action=lire",
            {
                method: "GET",
                credentials: "same-origin",
                cache: "no-store",
                headers: {
                    "Accept": "application/json"
                }
            }
        );

        const donnees = await lireReponseJSON(reponse);

        if (!reponse.ok || !donnees.succes) {
            throw new Error(
                donnees.message || "Impossible de charger le profil."
            );
        }

        profil = donnees.profil;
        afficherProfil();

        return true;

    } catch (erreur) {
        console.error("Erreur de chargement du profil :", erreur);

        alert(
            erreur.message ||
            "Impossible de charger le profil."
        );

        return false;
    }
}

/*
==================================================
3. AFFICHER LE PROFIL
==================================================
*/

function afficherProfil() {
    if (!profil) {
        return;
    }

    if (nomInput) nomInput.value = profil.nom || "";
    if (prenomInput) prenomInput.value = profil.prenom || "";
    if (emailInput) emailInput.value = profil.email || "";
    if (telephoneInput) telephoneInput.value = profil.telephone || "";
    if (adresseInput) adresseInput.value = profil.adresse || "";

    mettreAJourInterface();
}

/*
==================================================
4. METTRE À JOUR L'INTERFACE
==================================================
*/

function mettreAJourInterface() {
    if (!profil) {
        return;
    }

    const nomComplet = (
        (profil.prenom || "") + " " + (profil.nom || "")
    ).trim();

    const nomAffiche = nomComplet || "Administrateur";
    const initiales = obtenirInitiales(nomAffiche);

    if (profileAvatar) {
        profileAvatar.textContent = initiales;
    }

    if (profileFullName) {
        profileFullName.textContent = nomAffiche;
    }

    if (profileEmail) {
        profileEmail.textContent = profil.email || "";
    }

    if (sidebarAvatar) {
        sidebarAvatar.textContent = initiales;
    }

    if (sidebarName) {
        sidebarName.textContent = nomAffiche;
    }

    if (sidebarEmail) {
        sidebarEmail.textContent = profil.email || "";
    }

    if (headerAvatar) {
        headerAvatar.textContent = initiales;
    }

    if (headerName) {
        headerName.textContent = nomAffiche;
    }

    // Actualiser l'état de la double authentification.
    const deuxFacteursActifs =
        Number(profil.deux_facteurs) === 1;

    if (twoFactorText) {
        twoFactorText.textContent = deuxFacteursActifs
            ? "Activée"
            : "Non activée";
    }

    if (twoFactorButton) {
        twoFactorButton.textContent = deuxFacteursActifs
            ? "Désactiver"
            : "Activer";

        twoFactorButton.disabled = false;
    }
}

/*
==================================================
5. OBTENIR LES INITIALES
==================================================
*/

function obtenirInitiales(nom) {
    const parties = nom.trim().split(/\s+/);

    if (parties.length === 1) {
        return parties[0].substring(0, 2).toUpperCase();
    }

    return (
        parties[0][0] +
        parties[parties.length - 1][0]
    ).toUpperCase();
}

/*
==================================================
6. MODIFIER LE PROFIL
==================================================
*/

if (profileForm) {
    profileForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        const nouvellesDonnees = {
            nom: nomInput.value.trim(),
            prenom: prenomInput.value.trim(),
            email: emailInput.value.trim(),
            telephone: telephoneInput.value.trim(),
            adresse: adresseInput.value.trim()
        };

        if (
            !nouvellesDonnees.nom ||
            !nouvellesDonnees.prenom ||
            !nouvellesDonnees.email ||
            !nouvellesDonnees.telephone
        ) {
            alert(
                "Veuillez remplir le nom, le prénom, l'email et le téléphone."
            );
            return;
        }

        const boutonEnvoi = profileForm.querySelector(
            'button[type="submit"]'
        );

        const ancienTexte = boutonEnvoi
            ? boutonEnvoi.textContent
            : "";

        if (boutonEnvoi) {
            boutonEnvoi.disabled = true;
            boutonEnvoi.textContent = "Enregistrement...";
        }

        try {
            const reponse = await fetch(
                API_PROFIL + "?action=modifier",
                {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json"
                    },
                    body: JSON.stringify(nouvellesDonnees)
                }
            );

            const donnees = await lireReponseJSON(reponse);

            if (!reponse.ok || !donnees.succes) {
                throw new Error(
                    donnees.message || "La modification a échoué."
                );
            }

            const profilRecharge = await chargerProfil();

            if (profilRecharge) {
                alert(donnees.message);
            }

        } catch (erreur) {
            console.error("Erreur de modification :", erreur);
            alert(erreur.message || "Une erreur est survenue.");
        } finally {
            if (boutonEnvoi) {
                boutonEnvoi.disabled = false;
                boutonEnvoi.textContent = ancienTexte;
            }
        }
    });
}

/*
==================================================
7. ANNULER LES MODIFICATIONS
==================================================
*/

if (cancelProfileButton) {
    cancelProfileButton.addEventListener("click", function () {
        afficherProfil();
    });
}

/*
==================================================
8. OUVRIR LA FENÊTRE DU MOT DE PASSE
==================================================
*/

function ouvrirFenetreMotDePasse() {
    if (!passwordModal) {
        alert("La fenêtre du mot de passe est introuvable.");
        return;
    }

    passwordModal.style.display = "flex";

    if (passwordForm) {
        passwordForm.reset();
    }
}

/*
==================================================
9. FERMER LA FENÊTRE DU MOT DE PASSE
==================================================
*/

function fermerFenetreMotDePasse() {
    if (passwordModal) {
        passwordModal.style.display = "none";
    }

    if (passwordForm) {
        passwordForm.reset();
    }
}

if (passwordButton) {
    passwordButton.addEventListener(
        "click",
        ouvrirFenetreMotDePasse
    );
}

if (closePasswordModal) {
    closePasswordModal.addEventListener(
        "click",
        fermerFenetreMotDePasse
    );
}

if (cancelPasswordButton) {
    cancelPasswordButton.addEventListener(
        "click",
        fermerFenetreMotDePasse
    );
}

if (passwordModal) {
    passwordModal.addEventListener("click", function (event) {
        if (event.target === passwordModal) {
            fermerFenetreMotDePasse();
        }
    });
}

document.addEventListener("keydown", function (event) {
    if (
        event.key === "Escape" &&
        passwordModal &&
        passwordModal.style.display !== "none"
    ) {
        fermerFenetreMotDePasse();
    }
});

/*
==================================================
10. CHANGER LE MOT DE PASSE
==================================================
*/

if (passwordForm) {
    passwordForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        const ancienMotDePasse = oldPasswordInput.value;
        const nouveauMotDePasse = newPasswordInput.value;
        const confirmation = confirmPasswordInput.value;

        if (
            !ancienMotDePasse ||
            !nouveauMotDePasse ||
            !confirmation
        ) {
            alert("Veuillez remplir tous les champs.");
            return;
        }

        if (nouveauMotDePasse.length < 8) {
            alert(
                "Le nouveau mot de passe doit contenir au moins 8 caractères."
            );
            return;
        }

        if (nouveauMotDePasse !== confirmation) {
            alert("Les nouveaux mots de passe ne correspondent pas.");
            return;
        }

        const boutonEnvoi = passwordForm.querySelector(
            'button[type="submit"]'
        );

        const ancienTexte = boutonEnvoi
            ? boutonEnvoi.textContent
            : "";

        if (boutonEnvoi) {
            boutonEnvoi.disabled = true;
            boutonEnvoi.textContent = "Modification...";
        }

        try {
            const reponse = await fetch(
                API_PROFIL + "?action=changer_mot_de_passe",
                {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({
                        ancienMotDePasse: ancienMotDePasse,
                        nouveauMotDePasse: nouveauMotDePasse,
                        confirmation: confirmation
                    })
                }
            );

            const donnees = await lireReponseJSON(reponse);

            if (!reponse.ok || !donnees.succes) {
                throw new Error(
                    donnees.message ||
                    "Le changement de mot de passe a échoué."
                );
            }

            fermerFenetreMotDePasse();
            alert(donnees.message);

        } catch (erreur) {
            console.error(
                "Erreur de changement du mot de passe :",
                erreur
            );

            alert(
                erreur.message ||
                "Une erreur est survenue pendant le changement du mot de passe."
            );
        } finally {
            if (boutonEnvoi) {
                boutonEnvoi.disabled = false;
                boutonEnvoi.textContent = ancienTexte;
            }
        }
    });
}

/*
==================================================
11. ACTIVER OU DÉSACTIVER LA DOUBLE AUTHENTIFICATION
==================================================
*/

if (twoFactorButton) {
    twoFactorButton.addEventListener("click", async function () {
        if (!profil) {
            alert("Le profil n'est pas encore chargé.");
            return;
        }

        const actuellementActif =
            Number(profil.deux_facteurs) === 1;

        const nouvelEtat = !actuellementActif;

        const confirmation = confirm(
            nouvelEtat
                ? "Voulez-vous activer la double authentification par e-mail ?"
                : "Voulez-vous désactiver la double authentification ?"
        );

        if (!confirmation) {
            return;
        }

        twoFactorButton.disabled = true;
        twoFactorButton.textContent = "Enregistrement...";

        try {
            const reponse = await fetch(
                API_PROFIL + "?action=changer_2fa",
                {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({
                        activer: nouvelEtat
                    })
                }
            );

            const donnees = await lireReponseJSON(reponse);

            if (!reponse.ok || !donnees.succes) {
                throw new Error(
                    donnees.message ||
                    "Impossible de modifier la double authentification."
                );
            }

            // Actualiser l'état depuis la base de données.
            const profilRecharge = await chargerProfil();

            if (!profilRecharge) {
                throw new Error(
                    "Le réglage a été enregistré, mais le profil n'a pas pu être rechargé. Actualisez la page."
                );
            }

            alert(donnees.message);

        } catch (erreur) {
            console.error("Erreur 2FA :", erreur);

            alert(
                erreur.message ||
                "Une erreur est survenue pendant la modification du 2FA."
            );
        } finally {
            if (twoFactorButton) {
                twoFactorButton.disabled = false;

                if (profil) {
                    twoFactorButton.textContent =
                        Number(profil.deux_facteurs) === 1
                            ? "Désactiver"
                            : "Activer";
                }
            }
        }
    });
}

/*
==================================================
12. MENU MOBILE
==================================================
*/

if (menuButton && sidebar) {
    menuButton.addEventListener("click", function () {
        sidebar.classList.toggle("open");
    });
}

/*
==================================================
13. CHARGEMENT INITIAL
==================================================
*/

chargerProfil();
