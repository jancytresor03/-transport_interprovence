
/* ==========================================
   GESTION DES TRAJETS
   Communication avec la base de donnees
========================================== */

const API_TRAJETS = "trajets-api.php";

const formulaireTrajet = document.getElementById("tripForm");
const tripsTableBody = document.getElementById("tripsTableBody");

const searchInput = document.getElementById("searchInput");
const departureFilter = document.getElementById("departureFilter");
const statusFilter = document.getElementById("statusFilter");
const sortFilter = document.getElementById("sortFilter");
const resultCount = document.getElementById("resultCount");

const totalTrajets = document.getElementById("totalTrajets");
const trajetsActifs = document.getElementById("trajetsActifs");
const placesDisponibles = document.getElementById("placesDisponibles");
const nombreDestinations = document.getElementById("nombreDestinations");

const modal = document.getElementById("tripModal");
const modalTitle = document.getElementById("modalTitle");
const closeModalButton = document.getElementById("closeModal");
const cancelModalButton = document.getElementById("cancelButton");
const addTripButton = document.getElementById("addTripButton");
const emptyState = document.getElementById("emptyState");
const refreshButton = document.getElementById("refreshButton");
const formError = document.getElementById("formError");

let trajets = [];


/* ==========================================
   COMMUNIQUER AVEC PHP
========================================== */

async function appelerAPI(methode, donnees = null, actionGET = "") {
    let url = API_TRAJETS;

    if (methode === "GET") {
        url += "?action=" + encodeURIComponent(actionGET);
    }

    const options = {
        method: methode,
        headers: {}
    };

    if (donnees !== null) {
        options.headers["Content-Type"] = "application/json";
        options.body = JSON.stringify(donnees);
    }

    const reponse = await fetch(url, options);
	
    const resultat = await reponse.json();

    if (!reponse.ok || !resultat.succes) {
        throw new Error(resultat.message || "Une erreur est survenue.");
    }

    return resultat;
}


/* ==========================================
   CHARGER LES TRAJETS DEPUIS MYSQL
========================================== */

async function chargerTrajets() {
    try {
        const resultat = await appelerAPI("GET", null, "liste");

        trajets = resultat.donnees.map(function (ligne) {
            return {
                id: ligne.trajet_id,
                depart: ligne.depart,
                destination: ligne.destination,
                date: ligne.date_depart,
                heure: String(ligne.heure_depart).substring(0, 5),
                prix: Number(ligne.prix),
                places: Number(ligne.nombre_places),
                statut: ligne.statut
            };
        });

        actualiserStatistiques();
        filtrerTrajets();

    } catch (erreur) {
        console.error(erreur);
        alert("Erreur de chargement : " + erreur.message);
    }
}


/* ==========================================
   PROTEGER LE TEXTE A AFFICHER
========================================== */

function echapperHTML(valeur) {
    return String(valeur ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}


/* ==========================================
   AFFICHER LES TRAJETS
========================================== */

function afficherTrajets(liste) {
    tripsTableBody.innerHTML = "";

    if (liste.length === 0) {
        emptyState.style.display = "block";
        resultCount.textContent = "0 trajet";
        return;
    }

    emptyState.style.display = "none";

    resultCount.textContent =
        liste.length + (liste.length > 1 ? " trajets" : " trajet");

    liste.forEach(function (trajet) {
        const ligne = document.createElement("tr");

        const depart = echapperHTML(trajet.depart);
        const destination = echapperHTML(trajet.destination);
        const statut = echapperHTML(trajet.statut);

        ligne.innerHTML = `
            <td>
                <div class="route-cell">
                    <div class="route-icon">
                        <i class="fa-solid fa-route"></i>
                    </div>
                    <div class="route-name">
                        <strong>${depart} → ${destination}</strong>
                        <span>Burundi Transport</span>
                    </div>
                </div>
            </td>

            <td>${formaterDate(trajet.date)}</td>

            <td>
                <div class="time-cell">
                    <i class="fa-regular fa-clock"></i>
                    ${echapperHTML(trajet.heure)}
                </div>
            </td>

            <td>
                <span class="price">${formaterPrix(trajet.prix)}</span>
            </td>

            <td>
                <div class="seats">
                    <i class="fa-solid fa-chair"></i>
                    ${trajet.places}
                </div>
            </td>

            <td>
                <span class="status ${statut}">
                    ${texteStatut(trajet.statut)}
                </span>
            </td>

            <td>
                <div class="table-actions">
                    <button
                        class="table-action edit"
                        title="Modifier"
                        data-action="modifier"
                        data-id="${echapperHTML(trajet.id)}">
                        <i class="fa-solid fa-pen"></i>
                    </button>

                    <button
                        class="table-action delete"
                        title="Supprimer"
                        data-action="supprimer"
                        data-id="${echapperHTML(trajet.id)}">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </td>
        `;

        tripsTableBody.appendChild(ligne);
    });
}


function texteStatut(statut) {
    const textes = {
        actif: "Actif",
        inactif: "Inactif",
        termine: "Terminé",
        annule: "Annulé"
    };

    return textes[statut] || statut;
}


/* ==========================================
   RECHERCHE, FILTRES ET TRI
========================================== */

function filtrerTrajets() {
    const recherche = searchInput.value.toLowerCase().trim();
    const departChoisi = departureFilter.value;
    const statutChoisi = statusFilter.value;
    const tri = sortFilter.value;

    let resultat = trajets.filter(function (trajet) {
        const texte =
            (trajet.depart + " " + trajet.destination).toLowerCase();

        return texte.includes(recherche)
            && (departChoisi === "" || trajet.depart === departChoisi)
            && (statutChoisi === "" || trajet.statut === statutChoisi);
    });

    if (tri === "date") {
        resultat.sort(function (a, b) {
            return (a.date + a.heure).localeCompare(b.date + b.heure);
        });
    } else if (tri === "prix") {
        resultat.sort(function (a, b) {
            return a.prix - b.prix;
        });
    } else if (tri === "places") {
        resultat.sort(function (a, b) {
            return b.places - a.places;
        });
    } else if (tri === "depart") {
        resultat.sort(function (a, b) {
            return a.depart.localeCompare(b.depart);
        });
    }

    afficherTrajets(resultat);
}


/* ==========================================
   STATISTIQUES
========================================== */

function actualiserStatistiques() {
    totalTrajets.textContent = trajets.length;

    trajetsActifs.textContent = trajets.filter(function (trajet) {
        return trajet.statut === "actif";
    }).length;

    placesDisponibles.textContent = trajets.reduce(function (somme, trajet) {
        return somme + trajet.places;
    }, 0);

    nombreDestinations.textContent =
        new Set(trajets.map(function (trajet) {
            return trajet.destination;
        })).size;
}


/* ==========================================
   OUVRIR ET FERMER LE FORMULAIRE
========================================== */

function ouvrirModal() {
    formulaireTrajet.reset();

    document.getElementById("tripId").value = "";
    modalTitle.textContent = "Ajouter un trajet";
    formError.textContent = "";

    modal.classList.add("show");
    modal.style.display = "flex";
}

function fermerModal() {
    modal.classList.remove("show");
    modal.style.display = "none";

    formulaireTrajet.reset();
    formError.textContent = "";
}


/* ==========================================
   LIRE LES CHAMPS DU FORMULAIRE
========================================== */

function lireFormulaire() {
    return {
        trajet_id: document.getElementById("tripId").value,
        depart: document.getElementById("departure").value,
        destination: document.getElementById("destination").value,
        date_depart: document.getElementById("tripDate").value,
        heure_depart: document.getElementById("tripTime").value,
        prix: Number(document.getElementById("tripPrice").value),
        nombre_places: Number(document.getElementById("tripSeats").value),
        statut: document.getElementById("tripStatus").value
    };
}


/* ==========================================
   AJOUTER OU MODIFIER UN TRAJET
========================================== */

formulaireTrajet.addEventListener("submit", async function (event) {
    event.preventDefault();
    formError.textContent = "";

    const trajet = lireFormulaire();

    if (trajet.depart === trajet.destination) {
        formError.textContent =
            "Le départ et la destination doivent être différents.";
        return;
    }

    if (
        !trajet.depart ||
        !trajet.destination ||
        !trajet.date_depart ||
        !trajet.heure_depart ||
        trajet.prix < 0 ||
        trajet.nombre_places < 1
    ) {
        formError.textContent = "Vérifiez les informations saisies.";
        return;
    }

    const action = trajet.trajet_id ? "modifier" : "ajouter";

    try {
        const resultat = await appelerAPI("POST", {
            ...trajet,
            action: action
        });

        alert(resultat.message);
        fermerModal();
        await chargerTrajets();

    } catch (erreur) {
        formError.textContent = erreur.message;
    }
});


/* ==========================================
   MODIFIER OU SUPPRIMER
========================================== */

tripsTableBody.addEventListener("click", async function (event) {
    const bouton = event.target.closest("button[data-action]");

    if (!bouton) {
        return;
    }

    const id = bouton.dataset.id;
    const action = bouton.dataset.action;

    if (action === "modifier") {
        const trajet = trajets.find(function (element) {
            return String(element.id) === String(id);
        });

        if (!trajet) {
            alert("Trajet introuvable.");
            return;
        }

        document.getElementById("tripId").value = trajet.id;
        document.getElementById("departure").value = trajet.depart;
        document.getElementById("destination").value = trajet.destination;
        document.getElementById("tripDate").value = trajet.date;
        document.getElementById("tripTime").value = trajet.heure;
        document.getElementById("tripPrice").value = trajet.prix;
        document.getElementById("tripSeats").value = trajet.places;
        document.getElementById("tripStatus").value = trajet.statut;

        modalTitle.textContent = "Modifier le trajet";
        formError.textContent = "";

        modal.classList.add("show");
        modal.style.display = "flex";
    }

    if (action === "supprimer") {
        const trajet = trajets.find(function (element) {
            return String(element.id) === String(id);
        });

        if (!trajet) {
            return;
        }

        const confirmation = confirm(
            "Voulez-vous vraiment supprimer le trajet " +
            trajet.depart + " → " + trajet.destination + " ?"
        );

        if (!confirmation) {
            return;
        }

        try {
            const resultat = await appelerAPI("POST", {
                action: "supprimer",
                trajet_id: id
            });

            alert(resultat.message);
            await chargerTrajets();

        } catch (erreur) {
            alert("Erreur de suppression : " + erreur.message);
        }
    }
});


/* ==========================================
   EVENEMENTS
========================================== */

addTripButton.addEventListener("click", ouvrirModal);
closeModalButton.addEventListener("click", fermerModal);
cancelModalButton.addEventListener("click", fermerModal);

searchInput.addEventListener("input", filtrerTrajets);
departureFilter.addEventListener("change", filtrerTrajets);
statusFilter.addEventListener("change", filtrerTrajets);
sortFilter.addEventListener("change", filtrerTrajets);

refreshButton.addEventListener("click", chargerTrajets);

modal.addEventListener("click", function (event) {
    if (event.target === modal) {
        fermerModal();
    }
});


/* ==========================================
   FORMATER LE PRIX ET LA DATE
========================================== */

function formaterPrix(prix) {
    return Number(prix).toLocaleString("fr-FR") + " FBu";
}

function formaterDate(date) {
    if (!date) {
        return "";
    }

    const morceaux = String(date).split("-");

    if (morceaux.length !== 3) {
        return date;
    }

    return morceaux[2] + "/" + morceaux[1] + "/" + morceaux[0];
}


/* Démarrer la page */
chargerTrajets();
