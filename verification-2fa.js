
/* =========================
   ELEMENTS DE LA PAGE
========================= */

const verificationForm = document.getElementById("verificationForm");
const codeInput = document.getElementById("code");
const codeError = document.getElementById("codeError");
const message = document.getElementById("message");
const verifyButton = document.getElementById("verifyButton");


/* =========================
   AFFICHER UN MESSAGE
========================= */

function afficherMessage(texte, type) {
    message.textContent = texte;
    message.className = "message " + type;
    message.hidden = false;
}


/* =========================
   EFFACER LES ERREURS
========================= */

function effacerErreurs() {
    codeError.textContent = "";
    codeInput.classList.remove("input-error");

    message.textContent = "";
    message.hidden = true;
    message.className = "message";
}

codeInput.addEventListener("input", function () {
    // Conserver uniquement les chiffres.
    codeInput.value = codeInput.value
        .replace(/\D/g, "")
        .slice(0, 6);

    codeError.textContent = "";
    codeInput.classList.remove("input-error");
});


/* =========================
   ENVOYER LE CODE AU SERVEUR
========================= */

verificationForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    effacerErreurs();

    const code = codeInput.value.trim();

    // Vérifier le format du code.
    if (!/^\d{6}$/.test(code)) {
        codeError.textContent =
            "Veuillez saisir un code composé de 6 chiffres.";

        codeInput.classList.add("input-error");
        codeInput.focus();

        return;
    }

    verifyButton.disabled = true;
    verifyButton.textContent = "Vérification en cours...";

    try {
        const donnees = new FormData();
        donnees.append("code", code);

        const reponse = await fetch("verifier-code-2fa.php", {
            method: "POST",
            body: donnees,
            credentials: "same-origin",
            headers: {
                "Accept": "application/json"
            }
        });

        let resultat;

        try {
            resultat = await reponse.json();
        } catch {
            throw new Error(
                "Le serveur a retourné une réponse invalide."
            );
        }

        if (!reponse.ok || !resultat.succes) {
            afficherMessage(
                resultat.message || "La vérification a échoué.",
                "error"
            );

            // Ne pas permettre de continuer si la session 2FA
            // a expiré ou si les tentatives sont épuisées.
            if (
                reponse.status === 401 ||
                reponse.status === 429
            ) {
                verifyButton.disabled = true;
                codeInput.disabled = true;
                return;
            }

            codeInput.classList.add("input-error");
            codeInput.value = "";
            codeInput.focus();

            return;
        }

        afficherMessage(
            resultat.message || "Vérification réussie.",
            "success"
        );

        if (resultat.role === "admin") {
            window.location.href =
                "administrateur/admin-dashboard.php";

        } else if (resultat.role === "client") {
            window.location.href =
                "client/client-home.php";

        } else {
            afficherMessage(
                "Le rôle reçu du serveur est invalide.",
                "error"
            );
        }

    } catch (erreur) {
        console.error("Erreur 2FA :", erreur);

        afficherMessage(
            "Impossible de contacter le serveur. Vérifiez que XAMPP est démarré.",
            "error"
        );

    } finally {
        if (!verifyButton.disabled) {
            verifyButton.textContent = "Vérifier le code";
        }
    }
});
