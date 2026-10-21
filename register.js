
const registerForm = document.getElementById("registerForm");

const nom = document.getElementById("nom");
const prenom = document.getElementById("prenom");
const email = document.getElementById("email");
const telephone = document.getElementById("telephone");
const password = document.getElementById("password");
const confirmPassword = document.getElementById("confirmPassword");

const showPassword = document.getElementById("showPassword");
const showConfirmPassword = document.getElementById("showConfirmPassword");

showPassword.addEventListener("click", function () {
    password.type = password.type === "password" ? "text" : "password";
    this.textContent = password.type === "password" ? "Afficher" : "Cacher";
});

showConfirmPassword.addEventListener("click", function () {
    confirmPassword.type =
        confirmPassword.type === "password" ? "text" : "password";

    this.textContent =
        confirmPassword.type === "password" ? "Afficher" : "Cacher";
});

registerForm.addEventListener("submit", function (event) {
    event.preventDefault();

    const erreurs = {
        nomError: "",
        prenomError: "",
        emailError: "",
        telephoneError: "",
        passwordError: "",
        confirmPasswordError: ""
    };

    document.querySelectorAll(".error-message").forEach(function (element) {
        element.textContent = "";
    });

    const message = document.getElementById("registerMessage");
    message.textContent = "";
    message.className = "register-message";

    let valide = true;

    if (nom.value.trim() === "") {
        erreurs.nomError = "Veuillez entrer votre nom.";
        valide = false;
    }

    if (prenom.value.trim() === "") {
        erreurs.prenomError = "Veuillez entrer votre prénom.";
        valide = false;
    }

    if (email.value.trim() === "") {
        erreurs.emailError = "Veuillez entrer votre adresse e-mail.";
        valide = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
        erreurs.emailError = "Veuillez entrer une adresse e-mail valide.";
        valide = false;
    }

    if (telephone.value.trim() === "") {
        erreurs.telephoneError = "Veuillez entrer votre numéro de téléphone.";
        valide = false;
    }

    if (password.value.length < 8) {
        erreurs.passwordError =
            "Le mot de passe doit contenir au moins 8 caractères.";
        valide = false;
    }

    if (confirmPassword.value === "") {
        erreurs.confirmPasswordError =
            "Veuillez confirmer votre mot de passe.";
        valide = false;
    } else if (password.value !== confirmPassword.value) {
        erreurs.confirmPasswordError =
            "Les mots de passe ne correspondent pas.";
        valide = false;
    }

    Object.entries(erreurs).forEach(function ([id, texte]) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = texte;
        }
    });

    if (!valide) {
        message.textContent = "Veuillez corriger les champs signalés.";
        message.classList.add("error");
        return;
    }

    // Envoie réellement les données au serveur PHP.
    // Cette méthode contourne le gestionnaire submit actuel
    // pour ne pas bloquer l'envoi une seconde fois.
    registerForm.submit();
});
