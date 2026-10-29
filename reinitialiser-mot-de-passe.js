
const resetForm = document.getElementById("resetForm");
const passwordInput = document.getElementById("password");
const confirmPasswordInput = document.getElementById("confirmPassword");
const submitButton = document.getElementById("submitButton");
const message = document.getElementById("message");

// Récupérer le jeton envoyé dans le lien par e-mail.
const params = new URLSearchParams(window.location.search);
const token = params.get("token");

if (!token) {
    resetForm.hidden = true;
    message.textContent =
        "Le lien est incomplet. Demandez une nouvelle réinitialisation.";
    message.style.color = "#b42318";
}

resetForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const password = passwordInput.value;
    const confirmPassword = confirmPasswordInput.value;

    if (!token) {
        message.textContent =
            "Le jeton de réinitialisation est absent.";
        message.style.color = "#b42318";
        return;
    }

    if (password.length < 8) {
        message.textContent =
            "Le mot de passe doit contenir au moins 8 caractères.";
        message.style.color = "#b42318";
        return;
    }

    if (password !== confirmPassword) {
        message.textContent =
            "Les deux mots de passe ne correspondent pas.";
        message.style.color = "#b42318";
        return;
    }

    submitButton.disabled = true;
    submitButton.textContent = "Enregistrement...";
    message.textContent = "";

    try {
        const response = await fetch("reinitialiser-mot-de-passe.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                token: token,
                password: password,
                confirmPassword: confirmPassword
            })
        });

        const texte = await response.text();
        let data;

        try {
            data = JSON.parse(texte);
        } catch (erreurJSON) {
            console.error("Réponse PHP non JSON :", texte);
            throw new Error(
                "Le serveur a renvoyé une réponse incorrecte. Consultez la console."
            );
        }

        if (!response.ok || !data.success) {
            throw new Error(
                data.message || "La réinitialisation a échoué."
            );
        }

        message.textContent = data.message;
        message.style.color = "#247a43";
        resetForm.reset();

        // Retirer le jeton de la barre d'adresse après succès.
        window.history.replaceState(
            {},
            document.title,
            window.location.pathname
        );

        passwordInput.disabled = true;
        confirmPasswordInput.disabled = true;
        submitButton.disabled = true;
        submitButton.textContent = "Mot de passe modifié";

    } catch (error) {
        console.error("Erreur de réinitialisation :", error);
        message.textContent =
            error.message || "Impossible de contacter le serveur.";
        message.style.color = "#b42318";

        submitButton.disabled = false;
        submitButton.textContent =
            "Enregistrer le nouveau mot de passe";
    }
});
