
/* ================= DONNEES VENANT DE PHP ================= */

const trajets = window.trajetsDepuisPHP || [];
const recherche = window.rechercheDepuisPHP || {};

const depart = recherche.depart || "";
const destination = recherche.destination || "";
const date = recherche.date || "";
const passagers = Number(recherche.passagers || 1);


/* ================= ELEMENTS HTML ================= */

const summaryDepart = document.getElementById("summaryDepart");
const summaryDestination = document.getElementById("summaryDestination");
const summaryDate = document.getElementById("summaryDate");
const summaryPassagers = document.getElementById("summaryPassagers");

const tripsList = document.getElementById("tripsList");
const resultCount = document.getElementById("resultCount");
const emptyState = document.getElementById("emptyState");

const sortSelect = document.getElementById("sortSelect");

const filterMorning = document.getElementById("filterMorning");
const filterAfternoon = document.getElementById("filterAfternoon");
const filterEvening = document.getElementById("filterEvening");

const modifySearch = document.getElementById("modifySearch");
const newSearchButton = document.getElementById("newSearchButton");

const userButton = document.getElementById("userButton");
const userMenu = document.getElementById("userMenu");
const logoutButton = document.getElementById("logoutButton");


/* ================= SECURITE HTML ================= */

// Evite d'interpreter les valeurs comme du code HTML.
function echapperHTML(valeur) {
    return String(valeur ?? "").replace(/[&<>"']/g, function (caractere) {
        const caracteres = {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#39;"
        };

        return caracteres[caractere];
    });
}


/* ================= AFFICHER LE RESUME ================= */

function afficherResume() {
    summaryDepart.textContent = depart || "-";
    summaryDestination.textContent = destination || "-";

    summaryPassagers.textContent =
        passagers + (passagers > 1 ? " passagers" : " passager");

    if (date) {
        // Evite les décalages de date liés au fuseau horaire.
        const morceaux = date.split("-");

        if (morceaux.length === 3) {
            summaryDate.textContent =
                morceaux[2] + "/" + morceaux[1] + "/" + morceaux[0];
        } else {
            summaryDate.textContent = date;
        }
    } else {
        summaryDate.textContent = "Toutes les dates";
    }
}


/* ================= FILTRER LES TRAJETS ================= */

function filtrerTrajets() {
    let resultats = trajets.filter(function (trajet) {
        return Number(trajet.places) >= passagers;
    });

    const matin = filterMorning.checked;
    const apresMidi = filterAfternoon.checked;
    const soir = filterEvening.checked;

    // Si aucun filtre n'est coché, on conserve tous les horaires.
    if (matin || apresMidi || soir) {
        resultats = resultats.filter(function (trajet) {
            const heure = parseInt(
                trajet.heureDepart.split(":")[0],
                10
            );

            return (
                (matin && heure >= 5 && heure < 12) ||
                (apresMidi && heure >= 12 && heure < 18) ||
                (soir && heure >= 18 && heure <= 23)
            );
        });
    }

    return resultats;
}


/* ================= TRIER LES TRAJETS ================= */

function trierTrajets(resultats) {
    const typeTri = sortSelect.value;

    if (typeTri === "departure") {
        resultats.sort(function (a, b) {
            const comparaisonDate =
                a.dateDepart.localeCompare(b.dateDepart);

            if (comparaisonDate !== 0) {
                return comparaisonDate;
            }

            return a.heureDepart.localeCompare(b.heureDepart);
        });
    }

    if (typeTri === "price-asc") {
        resultats.sort(function (a, b) {
            return a.prix - b.prix;
        });
    }

    if (typeTri === "price-desc") {
        resultats.sort(function (a, b) {
            return b.prix - a.prix;
        });
    }

    return resultats;
}


/* ================= AFFICHER LES TRAJETS ================= */

function afficherTrajets() {
    tripsList.innerHTML = "";

    let resultats = filtrerTrajets();
    resultats = trierTrajets(resultats);

    resultCount.textContent =
        resultats.length +
        (resultats.length > 1
            ? " trajet(s) disponible(s)"
            : " trajet disponible");

    if (recherche.erreur) {
        emptyState.style.display = "none";
        return;
    }

    if (resultats.length === 0) {
        emptyState.style.display = "block";
        return;
    }

    emptyState.style.display = "none";

    resultats.forEach(function (trajet) {
        const card = document.createElement("div");
        card.className = "trip-card";

        const dateTrajet = trajet.dateDepart || "";

        card.innerHTML = `
            <div>
                <div class="company">
                    <div class="company-logo">BT</div>

                    <div>
                        <div class="company-name">
                            Burundi Transport
                        </div>

                        <span class="company-type">
                            Transport interprovincial
                        </span>
                    </div>
                </div>

                <div class="trip-route">
                    <div>
                        <div class="time">
                            ${echapperHTML(trajet.heureDepart)}
                        </div>

                        <div class="city">
                            ${echapperHTML(trajet.depart)}
                        </div>
                    </div>

                    <div class="route-line"></div>

                    <div>
                        <div class="time">
                            À confirmer
                        </div>

                        <div class="city">
                            ${echapperHTML(trajet.destination)}
                        </div>
                    </div>
                </div>
            </div>

            <div class="trip-info">
                <div class="info-item">
                    Date :
                    <strong>
                        ${echapperHTML(dateTrajet)}
                    </strong>
                </div>

                <div class="info-item">
                    Durée :
                    <strong>À confirmer</strong>
                </div>

                <div class="info-item">
                    Places disponibles :
                    <strong>
                        ${echapperHTML(trajet.places)}
                    </strong>
                </div>
            </div>

            <div class="trip-price">
                <div class="price">
                    ${Number(trajet.prix).toLocaleString("fr-FR")} FBu
                </div>

                <div class="price-label">
                    par passager
                </div>

                <div class="available">
                    ● ${echapperHTML(trajet.places)} places disponibles
                </div>

                <button
                    type="button"
                    class="btn-primary choose-trip"
                    data-id="${echapperHTML(trajet.id)}"
                >
                    Choisir ce trajet
                </button>
            </div>
        `;

        tripsList.appendChild(card);
    });

    // Ajouter les événements après la création des cartes.
    const buttons = tripsList.querySelectorAll(".choose-trip");

    buttons.forEach(function (button) {
        button.addEventListener("click", function () {
            choisirTrajet(this.dataset.id);
        });
    });
}


/* ================= CHOISIR UN TRAJET ================= */

function choisirTrajet(id) {
    const trajet = trajets.find(function (element) {
        return String(element.id) === String(id);
    });

    if (!trajet) {
        alert("Trajet introuvable.");
        return;
    }

    if (Number(trajet.places) < passagers) {
        alert("Il ne reste pas assez de places pour ce trajet.");
        return;
    }

    /*
       Le serveur de places.php devra relire le trajet
       depuis MySQL et vérifier le prix et les disponibilités.
    */

    const parametres = new URLSearchParams();

    parametres.set("trajet", trajet.id);
    parametres.set("passagers", passagers);

    window.location.href = "places.php?" + parametres.toString();
}


/* ================= MODIFIER LA RECHERCHE ================= */

if (modifySearch) {
    modifySearch.addEventListener("click", function () {
        const parametres = new URLSearchParams();

        if (depart) {
            parametres.set("depart", depart);
        }

        if (destination) {
            parametres.set("destination", destination);
        }

        if (date) {
            parametres.set("date", date);
        }

        parametres.set("passagers", passagers);

        window.location.href =
            "recherche.php?" + parametres.toString();
    });
}


/* ================= NOUVELLE RECHERCHE ================= */

if (newSearchButton) {
    newSearchButton.addEventListener("click", function () {
        window.location.href = "recherche.php";
    });
}


/* ================= EVENEMENTS DES FILTRES ================= */

sortSelect.addEventListener("change", afficherTrajets);

filterMorning.addEventListener("change", afficherTrajets);
filterAfternoon.addEventListener("change", afficherTrajets);
filterEvening.addEventListener("change", afficherTrajets);


/* ================= MENU UTILISATEUR ================= */

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


/* ================= DECONNEXION ================= */

if (logoutButton) {
    logoutButton.addEventListener("click", function () {
        const confirmation = confirm(
            "Voulez-vous vraiment vous déconnecter ?"
        );

        if (confirmation) {
            window.location.href = "../logout.php";
        }
    });
}


/* ================= INITIALISATION ================= */

afficherResume();
afficherTrajets();
