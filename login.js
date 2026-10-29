
/* =========================
   ELEMENTS DU FORMULAIRE
========================= */

const loginForm = document.getElementById("loginForm");

const emailInput = document.getElementById("email");
const passwordInput = document.getElementById("password");

const emailError = document.getElementById("emailError");
const passwordError = document.getElementById("passwordError");
const errorMessage = document.getElementById("errorMessage");

const showPassword = document.getElementById("showPassword");
const forgotPassword = document.getElementById("forgotPassword");


/* =========================
   AFFICHER / CACHER LE MOT DE PASSE
========================= */

if (showPassword) {
    showPassword.addEventListener("click", function () {
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            showPassword.textContent = "Masquer";
        } else {
            passwordInput.type = "password";
            showPassword.textContent = "Afficher";
        }
    });
}


/* =========================
   VALIDATION DE L'EMAIL
========================= */

function emailValide(email) {
    const expression = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return expression.test(email);
}


/* =========================
   EFFACER LES ERREURS
========================= */

function effacerErreurs() {
    emailError.textContent = "";
    passwordError.textContent = "";

    emailInput.classList.remove("input-error");
    passwordInput.classList.remove("input-error");

    errorMessage.textContent = "";
    errorMessage.style.display = "none";
}

effacerErreurs();


/* =========================
   CONNEXION AVEC PHP
========================= */

loginForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    effacerErreurs();

    const email = emailInput.value.trim();
    const motDePasse = passwordInput.value;

    let formulaireValide = true;

    // Vérifier l'email
    if (email === "") {
        emailError.textContent =
            "Veuillez entrer votre adresse email.";

        emailInput.classList.add("input-error");
        formulaireValide = false;

    } else if (!emailValide(email)) {
        emailError.textContent =
            "Veuillez entrer une adresse email valide.";

        emailInput.classList.add("input-error");
        formulaireValide = false;
    }

    // Vérifier le mot de passe
    if (motDePasse === "") {
        passwordError.textContent =
            "Veuillez entrer votre mot de passe.";

        passwordInput.classList.add("input-error");
        formulaireValide = false;
    }

    if (!formulaireValide) {
        return;
    }

    const bouton = loginForm.querySelector(
        'button[type="submit"]'
    );

    if (bouton) {
        bouton.disabled = true;
        bouton.textContent = "Connexion en cours...";
    }

    try {
        // Envoyer les champs à login.php
        const reponse = await fetch("login.php", {
            method: "POST",
            body: new FormData(loginForm),
            credentials: "same-origin"
        });

        const resultat = await reponse.json();
		console.log("Réponse du serveur :", resultat);
console.log("Vérification requise :", resultat.verification_requise);
console.log(
    "Type de verification_requise :",
    typeof resultat.verification_requise
);

        // Afficher les erreurs renvoyées par PHP
        if (!reponse.ok || !resultat.succes) {
            errorMessage.textContent =
                resultat.message || "La connexion a échoué.";

            errorMessage.style.display = "block";
            passwordInput.classList.add("input-error");
            return;
        }

        /*
         * DOUBLE AUTHENTIFICATION REQUISE
         */
        if (resultat.verification_requise === true) {
            window.location.href = "verification-2fa.html";
            return;
        }

        /*
         * CONNEXION NORMALE
         */
        if (resultat.role === "admin") {
            window.location.href =
                "administrateur/admin-dashboard.php";

        } else if (resultat.role === "client") {
            window.location.href =
                "client/client-home.php";

        } else {
            errorMessage.textContent =
                "Le rôle reçu du serveur est invalide.";

            errorMessage.style.display = "block";
        }

    } catch (erreur) {
        console.error("Erreur de connexion :", erreur);

        errorMessage.textContent =
            "Impossible de contacter le serveur. Vérifiez que XAMPP est démarré.";

        errorMessage.style.display = "block";

    } finally {
        if (bouton) {
            bouton.disabled = false;
            bouton.textContent = "Se connecter";
        }
    }
});


/* =========================
   MOT DE PASSE OUBLIE
========================= */

if (forgotPassword) {
    forgotPassword.addEventListener("click", function (event) {
        event.preventDefault();

        window.location.href = "mot-de-passe-oublie.html";
    });
}
