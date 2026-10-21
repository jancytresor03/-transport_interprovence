
async function actualiserClocheNotifications() {
    try {
        const reponse = await fetch(
            "notifications-count-api.php?temps=" + Date.now(),
            {
                cache: "no-store",
                headers: {
                    "Accept": "application/json"
                }
            }
        );

        if (!reponse.ok) {
            throw new Error("Erreur lors du chargement du compteur.");
        }

        const donnees = await reponse.json();

        if (donnees.success !== true) {
            throw new Error("Compteur indisponible.");
        }

        const nombre = Number(donnees.non_lues);

        if (!Number.isFinite(nombre) || nombre < 0) {
            return;
        }

        const badges = [
            document.getElementById("menuBadge"),
            document.getElementById("headerBadge"),
            document.getElementById("notificationBadge")
        ];

        badges.forEach(badge => {
            if (!badge) return;

            badge.textContent = nombre > 99 ? "99+" : String(nombre);
            badge.style.display = nombre > 0 ? "inline-flex" : "none";
        });

    } catch (erreur) {
        console.error("Compteur des notifications :", erreur);
    }
}

// Actualisation immédiate
actualiserClocheNotifications();

// Actualisation automatique toutes les 15 secondes
setInterval(actualiserClocheNotifications, 15000);
