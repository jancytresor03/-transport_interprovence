
const resetForm = document.getElementById("resetForm");
const emailInput = document.getElementById("email");
const submitButton = document.getElementById("submitButton");
const message = document.getElementById("message");

resetForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const email = emailInput.value.trim();

    if (!email) {
        message.textContent = "Veuillez saisir votre adresse e-mail.";
        message.style.color = "#b42318";
        return;
    }

    submitButton.disabled = true;
    submitButton.textContent = "Envoi en cours...";
    message.textContent = "";

    try {
        const response = await fetch("mot-de-passe-oublie.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ email: email })
        });

        // Lire la réponse du serveur.
        const texte = await response.text();

        console.log("Code HTTP :", response.status);
        console.log("Réponse du serveur :", texte);

        // Convertir la réponse en JSON.
        let data;

        try {
            data = JSON.parse(texte);
        } catch (erreurJSON) {
            console.error("Réponse PHP non JSON :", texte);

            throw new Error(
                "Le serveur PHP a renvoyé une réponse incorrecte. " +
                "Consultez la console (F12)."
            );
        }

        if (!response.ok || !data.success) {
            throw new Error(
                data.message ||
                data.erreur ||
                "Une erreur est survenue. Réessayez plus tard."
            );
        }

        message.textContent = data.message;
        message.style.color = "#247a43";

        resetForm.reset();

    } catch (error) {
        console.error("Erreur de réinitialisation :", error);

        message.textContent = error.message ||
            "Impossible de contacter le serveur.";

        message.style.color = "#b42318";

    } finally {
        submitButton.disabled = false;
        submitButton.textContent = "Envoyer le lien";
    }
});
