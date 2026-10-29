
"use strict";

// ================= ÉLÉMENTS HTML =================

const profileForm = document.getElementById("profileForm");

const nomInput = document.getElementById("nom");
const prenomInput = document.getElementById("prenom");
const telephoneInput = document.getElementById("telephone");
const emailInput = document.getElementById("email");
const adresseInput = document.getElementById("adresse");

const nomError = document.getElementById("nomError");
const prenomError = document.getElementById("prenomError");
const telephoneError = document.getElementById("telephoneError");
const emailError = document.getElementById("emailError");

const profileMessage = document.getElementById("profileMessage");
const saveProfileButton = document.getElementById("saveProfileButton");


// ================= AFFICHAGE DES MESSAGES =================

function afficherMessage(element, message, succes = false) {
    if (!element) {
        alert(message);
        return;
    }

    element.textContent = message;
    element.hidden = false;
    element.style.display = "block";

    element.classList.toggle("success-message", succes);
    element.classList.toggle("error-message", !succes);

    // Compatibilité avec le style actuel de la page.
    element.style.color = succes ? "green" : "red";
}

function masquerMessage(element) {
    if (!element) return;

    element.textContent = "";
    element.hidden = true;
    element.style.display = "none";
}

function effacerErreurs() {
    if (nomError) nomError.textContent = "";
    if (prenomError) prenomError.textContent = "";
    if (telephoneError) telephoneError.textContent = "";
    if (emailError) emailError.textContent = "";

    masquerMessage(profileMessage);
}


// ================= LECTURE DE LA RÉPONSE PHP =================

async function lireReponse(reponse) {
    const texte = await reponse.text();

    let resultat;

    try {
        resultat = JSON.parse(texte);
    } catch {
        throw new Error(
            "Le serveur n'a pas renvoyé une réponse JSON valide. Vérifiez profil-api.php."
        );
    }

    // Ton API utilise "success", et non "succes".
    if (!reponse.ok || resultat.success !== true) {
        throw new Error(
            resultat.message || "Une erreur est survenue."
        );
    }

    return resultat;
}


// ================= VALIDATION DU PROFIL =================

function validerFormulaire() {
    effacerErreurs();

    let valide = true;

    const nom = nomInput.value.trim();
    const prenom = prenomInput.value.trim();
    const telephone = telephoneInput.value.trim();
    const email = emailInput.value.trim();

    if (nom === "") {
        nomError.textContent = "Le nom est obligatoire.";
        valide = false;
    }

    if (prenom === "") {
        prenomError.textContent = "Le prénom est obligatoire.";
        valide = false;
    }

    const chiffresTelephone = telephone.replace(/\D/g, "");

    if (chiffresTelephone.length < 8) {
        telephoneError.textContent =
            "Entrez un numéro de téléphone valide.";
        valide = false;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!emailRegex.test(email)) {
        emailError.textContent =
            "Entrez une adresse email valide.";
        valide = false;
    }

    return valide;
}


// ================= ACTUALISER L'EN-TÊTE =================

function actualiserProfil(donnees) {
    const nomComplet =
        `${donnees.prenom} ${donnees.nom}`.trim();

    const profileName = document.getElementById("profileName");
    const profileEmail = document.getElementById("profileEmail");

    if (profileName) {
        profileName.textContent = nomComplet;
    }

    if (profileEmail) {
        profileEmail.textContent = donnees.email;
    }

    // Actualiser le nom dans le menu utilisateur.
    const userButton = document.getElementById("userButton");

    if (userButton) {
        const textes = userButton.querySelectorAll("span");

        if (textes.length >= 2) {
            textes[1].textContent = nomComplet;
        }

        // Actualiser l'initiale affichée dans l'avatar.
        if (textes.length >= 1) {
            const initiale = (
                donnees.prenom.charAt(0) ||
                donnees.nom.charAt(0) ||
                "C"
            ).toUpperCase();

            textes[0].textContent = initiale;
        }
    }

    // Actualiser aussi l'avatar du profil.
    const avatarProfil = document.querySelector(".profile-avatar");

    if (avatarProfil) {
        avatarProfil.textContent = (
            donnees.prenom.charAt(0) ||
            donnees.nom.charAt(0) ||
            "C"
        ).toUpperCase();
    }
}


// ================= ENREGISTRER LE PROFIL =================

if (profileForm) {
    profileForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        if (!validerFormulaire()) {
            return;
        }

        const texteOriginal = saveProfileButton.textContent;

        saveProfileButton.disabled = true;
        saveProfileButton.textContent = "Enregistrement...";

        const donnees = {
            nom: nomInput.value.trim(),
            prenom: prenomInput.value.trim(),
            telephone: telephoneInput.value.trim(),
            email: emailInput.value.trim(),
            adresse: adresseInput.value.trim()
        };

        try {
            const reponse = await fetch("profil-api.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                credentials: "same-origin",
                body: JSON.stringify(donnees)
            });

            const resultat = await lireReponse(reponse);

            actualiserProfil(donnees);

            afficherMessage(
                profileMessage,
                resultat.message ||
                    "Votre profil a été modifié avec succès.",
                true
            );

        } catch (erreur) {
            console.error("Erreur du profil :", erreur);

            afficherMessage(
                profileMessage,
                erreur.message ||
                    "Impossible d'enregistrer les modifications."
            );

        } finally {
            saveProfileButton.disabled = false;
            saveProfileButton.textContent = texteOriginal;
        }
    });
}


// ================= MOT DE PASSE =================

const passwordButton = document.getElementById("passwordButton");
const passwordFormContainer =
    document.getElementById("passwordFormContainer");
const passwordForm = document.getElementById("passwordForm");
const passwordMessage = document.getElementById("passwordMessage");

const cancelPasswordButton =
    document.getElementById("cancelPasswordButton");

const savePasswordButton =
    document.getElementById("savePasswordButton");

const ancienMotDePasseInput =
    document.getElementById("ancienMotDePasse");

const nouveauMotDePasseInput =
    document.getElementById("nouveauMotDePasse");

const confirmationMotDePasseInput =
    document.getElementById("confirmationMotDePasse");


// Ouvrir le formulaire.
if (passwordButton && passwordFormContainer) {
    passwordButton.addEventListener("click", function () {
        passwordFormContainer.hidden = false;
        passwordButton.setAttribute("aria-expanded", "true");

        masquerMessage(passwordMessage);

        ancienMotDePasseInput.focus();
    });
}


// Fermer le formulaire.
if (cancelPasswordButton && passwordFormContainer && passwordForm) {
    cancelPasswordButton.addEventListener("click", function () {
        passwordForm.reset();

        passwordFormContainer.hidden = true;
        passwordButton.setAttribute("aria-expanded", "false");

        masquerMessage(passwordMessage);
    });
}


// Envoyer la demande de changement du mot de passe.
if (passwordForm) {
    passwordForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        masquerMessage(passwordMessage);

        const ancienMotDePasse = ancienMotDePasseInput.value;
        const nouveauMotDePasse = nouveauMotDePasseInput.value;
        const confirmationMotDePasse =
            confirmationMotDePasseInput.value;

        if (nouveauMotDePasse.length < 8) {
            afficherMessage(
                passwordMessage,
                "Le nouveau mot de passe doit contenir au moins 8 caractères."
            );
            return;
        }

        if (nouveauMotDePasse.length > 72) {
            afficherMessage(
                passwordMessage,
                "Le nouveau mot de passe est trop long."
            );
            return;
        }

        if (nouveauMotDePasse !== confirmationMotDePasse) {
            afficherMessage(
                passwordMessage,
                "Les deux nouveaux mots de passe ne correspondent pas."
            );
            return;
        }

        if (nouveauMotDePasse === ancienMotDePasse) {
            afficherMessage(
                passwordMessage,
                "Le nouveau mot de passe doit être différent de l'ancien."
            );
            return;
        }

        savePasswordButton.disabled = true;
        savePasswordButton.textContent = "Modification en cours...";

        try {
            const reponse = await fetch("profil-api.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                credentials: "same-origin",
                body: JSON.stringify({
                    action: "mot_de_passe",
                    ancienMotDePasse: ancienMotDePasse,
                    nouveauMotDePasse: nouveauMotDePasse,
                    confirmationMotDePasse: confirmationMotDePasse
                })
            });

            const resultat = await lireReponse(reponse);

            afficherMessage(
                passwordMessage,
                resultat.message ||
                    "Votre mot de passe a été modifié avec succès.",
                true
            );

            // Effacer les mots de passe des champs.
            passwordForm.reset();

        } catch (erreur) {
            console.error("Erreur changement de mot de passe :", erreur);

            afficherMessage(
                passwordMessage,
                erreur.message ||
                    "Impossible de modifier le mot de passe."
            );

        } finally {
            savePasswordButton.disabled = false;
            savePasswordButton.textContent =
                "Enregistrer le nouveau mot de passe";
        }
    });
}


// ================= AUTHENTIFICATION À DEUX FACTEURS =================

const twoFactorButton = document.getElementById("twoFactorButton");

if (twoFactorButton) {
    twoFactorButton.addEventListener("click", function () {
        alert(
            "L'authentification à deux facteurs n'est pas encore activée."
        );
    });
}


// ================= GESTION DES SESSIONS =================

const sessionsButton = document.getElementById("sessionsButton");

if (sessionsButton) {
    sessionsButton.addEventListener("click", function () {
        alert(
            "La gestion des sessions sera ajoutée dans une prochaine étape."
        );
    });
}


// ================= PRÉFÉRENCES DE NOTIFICATION =================

// Les préférences restent visuelles jusqu'à la mise en place
// d'une API qui les enregistre dans MySQL.

const notificationSwitch =
    document.getElementById("notificationSwitch");

const emailSwitch = document.getElementById("emailSwitch");

if (notificationSwitch) {
    notificationSwitch.addEventListener("change", function () {
        console.log(
            "Notifications :",
            this.checked ? "activées" : "désactivées"
        );
    });
}

if (emailSwitch) {
    emailSwitch.addEventListener("change", function () {
        console.log(
            "Notifications email :",
            this.checked ? "activées" : "désactivées"
        );
    });
}


// ================= SUPPRESSION DU COMPTE =================

// Aucun compte n'est supprimé par ce bouton pour le moment.

const deleteAccountButton =
    document.getElementById("deleteAccountButton");

if (deleteAccountButton) {
    deleteAccountButton.addEventListener("click", function () {
        alert(
            "La suppression du compte n'est pas encore disponible. " +
            "Aucune donnée n'a été supprimée."
        );
    });
}


// ================= MENU UTILISATEUR =================

const userButton = document.getElementById("userButton");
const userMenu = document.getElementById("userMenu");

if (userButton && userMenu) {
    userButton.addEventListener("click", function (event) {
        event.stopPropagation();

        const ouvert = userMenu.classList.toggle("active");

        userButton.setAttribute("aria-expanded", String(ouvert));
    });

    document.addEventListener("click", function (event) {
        if (
            !userButton.contains(event.target) &&
            !userMenu.contains(event.target)
        ) {
            userMenu.classList.remove("active");
            userButton.setAttribute("aria-expanded", "false");
        }
    });
}
