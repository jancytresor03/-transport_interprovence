
document.addEventListener("DOMContentLoaded", async function () {

    // Éléments de l'en-tête principal
    const headerAvatar = document.getElementById("headerAvatar");
    const headerAdminName = document.getElementById("headerAdminName");
    const headerAdminRole = document.getElementById("headerAdminRole");

    // Éléments de la barre latérale
    const sidebarAvatar = document.getElementById("sidebarAvatar");
    const sidebarName = document.getElementById("sidebarName");
    const sidebarRole = document.getElementById("sidebarRole");

    // Badge de la barre supérieure
    const topbarAdminName = document.getElementById("topbarAdminName");

    // Vérifier si la page utilise au moins un de ces éléments
    if (
        !headerAvatar &&
        !headerAdminName &&
        !headerAdminRole &&
        !sidebarAvatar &&
        !sidebarName &&
        !sidebarRole &&
        !topbarAdminName
    ) {
        return;
    }

    try {

        // Récupérer le profil depuis l'API PHP
        const reponse = await fetch("profil-api.php?action=lire", {
            method: "GET",
            credentials: "same-origin",
            headers: {
                "Accept": "application/json"
            }
        });

        if (!reponse.ok) {
            throw new Error("Impossible de charger le profil.");
        }

        const resultat = await reponse.json();

        if (!resultat.succes || !resultat.profil) {
            throw new Error("Les informations du profil sont indisponibles.");
        }

        const profil = resultat.profil;

        // Récupérer les informations réelles
        const prenom = (profil.prenom || "").trim();
        const nom = (profil.nom || "").trim();
        const email = (profil.email || "").trim();

        const nomComplet = [prenom, nom]
            .filter(Boolean)
            .join(" ");

        const nomAffiche = nomComplet || email || "Administrateur";

        // Construire les initiales
        const initiales = [prenom, nom]
            .filter(Boolean)
            .map(partie => partie.charAt(0).toLocaleUpperCase("fr"))
            .join("");

        const avatar = initiales ||
            (email ? email.substring(0, 2).toUpperCase() : "A");

        // Utiliser le rôle retourné par l'API s'il existe.
        // Sinon, afficher le rôle par défaut.
        const role = profil.role || "Administrateur";

        // Mettre à jour l'en-tête principal
        if (headerAvatar) {
            headerAvatar.textContent = avatar;
        }

        if (headerAdminName) {
            headerAdminName.textContent = nomAffiche;
        }

        if (headerAdminRole) {
            headerAdminRole.textContent = role;
        }

        // Mettre à jour la barre latérale
        if (sidebarAvatar) {
            sidebarAvatar.textContent = avatar;
        }

        if (sidebarName) {
            sidebarName.textContent = nomAffiche;
        }

        if (sidebarRole) {
            sidebarRole.textContent = role;
        }

        // Mettre à jour le badge de la barre supérieure
        if (topbarAdminName) {
            topbarAdminName.textContent = nomAffiche;
        }

    } catch (erreur) {
        console.error(
            "Erreur de chargement des informations administrateur :",
            erreur
        );
    }

});
