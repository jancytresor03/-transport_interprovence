// ================= DONNÉES DU CLIENT =================

// Pour le moment les informations sont simulées.
// Plus tard elles viendront de PHP/MySQL.

let client = {

    nom: "Bryant",

    prenom: "Aarre",

    telephone: "+257 79 00 00 00",

    email: "client@example.com",

    adresse: "Bujumbura"

};


// ================= ÉLÉMENTS HTML =================

const nomInput =
    document.getElementById("nom");

const prenomInput =
    document.getElementById("prenom");

const telephoneInput =
    document.getElementById("telephone");

const emailInput =
    document.getElementById("email");

const adresseInput =
    document.getElementById("adresse");


// ================= AFFICHER LES DONNÉES =================

nomInput.value =
    client.nom;

prenomInput.value =
    client.prenom;

telephoneInput.value =
    client.telephone;

emailInput.value =
    client.email;

adresseInput.value =
    client.adresse;


// Afficher le nom dans l'en-tête

document.getElementById(
    "profileName"
).textContent =
    client.prenom + " " + client.nom;


// Afficher l'email

document.getElementById(
    "profileEmail"
).textContent =
    client.email;


// ================= FORMULAIRE =================

const profileForm =
    document.getElementById(
        "profileForm"
    );


profileForm.addEventListener(
    "submit",
    function (event) {

        event.preventDefault();


        // Supprimer les anciennes erreurs

        document.getElementById(
            "nomError"
        ).textContent = "";

        document.getElementById(
            "prenomError"
        ).textContent = "";

        document.getElementById(
            "telephoneError"
        ).textContent = "";

        document.getElementById(
            "emailError"
        ).textContent = "";


        let valide = true;


        // ================= NOM =================

        if (nomInput.value.trim() === "") {

            document.getElementById(
                "nomError"
            ).textContent =
                "Le nom est obligatoire.";

            valide = false;

        }


        // ================= PRÉNOM =================

        if (prenomInput.value.trim() === "") {

            document.getElementById(
                "prenomError"
            ).textContent =
                "Le prénom est obligatoire.";

            valide = false;

        }


        // ================= TÉLÉPHONE =================

        if (
            telephoneInput.value.trim() === "" ||
            telephoneInput.value.trim().length < 8
        ) {

            document.getElementById(
                "telephoneError"
            ).textContent =
                "Entrez un numéro de téléphone valide.";

            valide = false;

        }


        // ================= EMAIL =================

        const email =
            emailInput.value.trim();


        const emailRegex =
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/;


        if (
            email === "" ||
            !emailRegex.test(email)
        ) {

            document.getElementById(
                "emailError"
            ).textContent =
                "Entrez une adresse email valide.";

            valide = false;

        }


        // ================= ENREGISTRER =================

        if (!valide) {

            return;

        }


        // Modifier les données

        client.nom =
            nomInput.value.trim();

        client.prenom =
            prenomInput.value.trim();

        client.telephone =
            telephoneInput.value.trim();

        client.email =
            emailInput.value.trim();

        client.adresse =
            adresseInput.value.trim();


        // Actualiser l'affichage

        document.getElementById(
            "profileName"
        ).textContent =
            client.prenom + " " + client.nom;


        document.getElementById(
            "profileEmail"
        ).textContent =
            client.email;


        alert(
            "Vos informations ont été modifiées avec succès."
        );

    }
);


// ================= MOT DE PASSE =================

document.getElementById(
    "passwordButton"
).addEventListener(
    "click",
    function () {

        alert(
            "La modification du mot de passe sera disponible avec PHP/MySQL."
        );

    }
);


// ================= 2FA =================

document.getElementById(
    "twoFactorButton"
).addEventListener(
    "click",
    function () {

        alert(
            "L'authentification à deux facteurs sera ajoutée dans la partie sécurité."
        );

    }
);


// ================= SESSIONS =================

document.getElementById(
    "sessionsButton"
).addEventListener(
    "click",
    function () {

        alert(
            "La gestion des sessions sera disponible avec PHP/MySQL."
        );

    }
);


// ================= NOTIFICATIONS =================

document.getElementById(
    "notificationSwitch"
).addEventListener(
    "change",
    function () {

        if (this.checked) {

            console.log(
                "Notifications activées"
            );

        }
        else {

            console.log(
                "Notifications désactivées"
            );

        }

    }
);


// ================= EMAIL =================

document.getElementById(
    "emailSwitch"
).addEventListener(
    "change",
    function () {

        if (this.checked) {

            console.log(
                "Notifications email activées"
            );

        }
        else {

            console.log(
                "Notifications email désactivées"
            );

        }

    }
);


// ================= SUPPRESSION COMPTE =================

document.getElementById(
    "deleteAccountButton"
).addEventListener(
    "click",
    function () {

        const confirmation =
            confirm(
                "Attention ! Voulez-vous vraiment supprimer votre compte ?"
            );


        if (!confirmation) {

            return;

        }


        const deuxiemeConfirmation =
            confirm(
                "Cette action est définitive. Confirmer la suppression ?"
            );


        if (!deuxiemeConfirmation) {

            return;

        }


        alert(
            "La suppression réelle du compte sera effectuée avec PHP/MySQL."
        );

    }
);


// ================= MENU UTILISATEUR =================

const userButton =
    document.getElementById(
        "userButton"
    );

const userMenu =
    document.getElementById(
        "userMenu"
    );


userButton.addEventListener(
    "click",
    function () {

        userMenu.classList.toggle(
            "active"
        );

    }
);


// Fermer le menu en cliquant ailleurs

document.addEventListener(
    "click",
    function (event) {

        if (
            !userButton.contains(event.target)
            &&
            !userMenu.contains(event.target)
        ) {

            userMenu.classList.remove(
                "active"
            );

        }

    }
);


// ================= DÉCONNEXION =================

document.getElementById(
    "logoutButton"
).addEventListener(
    "click",
    function () {

        const confirmation =
            confirm(
                "Voulez-vous vraiment vous déconnecter ?"
            );


        if (confirmation) {

            window.location.href =
                "../index.html";

        }

    }
);