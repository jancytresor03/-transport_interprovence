
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

/* =========================
   LIMITER LA SAISIE A 6 CHIFFRES
========================= */

codeInput.addEventListener("input", function () {
    codeInput.value = codeInput.value
        .replace(/\D/g, "")
        .slice(0, 6);

    codeError.textContent = "";
    codeInput.classList.remove("input-error");
});

/* =========================
   VERIFIER LE CODE 2FA
========================= */

verificationForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    effacerErreurs();

    const code = codeInput.value.trim();

    /* Vérifier le format du code */

    if (!/^\d{6}$/.test(code)) {
        codeError.textContent =
            "Veuillez saisir un code composé de 6 chiffres.";

        codeInput.classList.add("input-error");
        codeInput.focus();

        return;
    }

    /* Désactiver le bouton pendant la vérification */

    verifyButton.disabled = true;
    verifyButton.textContent = "Vérification en cours...";

    try {
        /* Préparer les données */

        const donnees = new FormData();
        donnees.append("code", code);

        /* Envoyer le code au serveur PHP */

        const reponse = await fetch("verifier-code-2fa.php", {
            method: "POST",
            body: donnees,
            credentials: "same-origin",
            headers: {
                "Accept": "application/json"
            }
        });

        /* Lire la réponse UNE SEULE FOIS */

        const texteReponse = await reponse.text();

        console.log("Statut HTTP :", reponse.status);
        console.log("Réponse brute du serveur :", texteReponse);

        /* Convertir la réponse en JSON */

        let resultat;

        try {
            resultat = JSON.parse(texteReponse);
        } catch (erreur) {
            console.error(
                "Réponse JSON invalide :",
                texteReponse
            );

            throw new Error(
                "Le serveur a retourné une réponse invalide."
            );
        }

        /* Vérifier le résultat */

        if (!reponse.ok || !resultat.succes) {
            afficherMessage(
                resultat.message || "La vérification a échoué.",
                "error"
            );

            /* Session expirée ou trop de tentatives */

            if (
                reponse.status === 401 ||
                reponse.status === 429
            ) {
                codeInput.disabled = true;
                verifyButton.disabled = true;
                return;
            }

            /* Autoriser une nouvelle tentative */

            codeInput.classList.add("input-error");
            codeInput.value = "";
            codeInput.focus();

            return;
        }

        /* Vérification réussie */

        afficherMessage(
            resultat.message || "Vérification réussie.",
            "success"
        );

        /* Rediriger selon le rôle */

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
            erreur.message ||
            "Une erreur est survenue pendant la vérification.",
            "error"
        );

    } finally {
        /* Réactiver le bouton si une nouvelle tentative est possible */

        if (!verifyButton.disabled) {
            verifyButton.textContent = "Vérifier le code";
        }
    }
});