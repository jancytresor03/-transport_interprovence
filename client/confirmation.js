// ================= RÉCUPÉRER LES PARAMÈTRES =================

const parametres =
    new URLSearchParams(window.location.search);


// Informations du trajet

const trajetId =
    parametres.get("trajet");

const depart =
    parametres.get("depart");

const destination =
    parametres.get("destination");

const date =
    parametres.get("date");

const heure =
    parametres.get("heure");

const passagers =
    parseInt(parametres.get("passagers")) || 1;

const places =
    parametres.get("places");

const prix =
    parseInt(parametres.get("prix")) || 0;


// Informations du passager

const nom =
    parametres.get("nom");

const prenom =
    parametres.get("prenom");

const telephone =
    parametres.get("telephone");

const email =
    parametres.get("email");

const identite =
    parametres.get("identite");

const numeroIdentite =
    parametres.get("numeroIdentite");


// ================= AFFICHER LE TRAJET =================

document.getElementById("depart").textContent =
    depart || "-";

document.getElementById("destination").textContent =
    destination || "-";

document.getElementById("date").textContent =
    date || "-";

document.getElementById("heure").textContent =
    heure || "-";

document.getElementById("passagers").textContent =
    passagers;

document.getElementById("places").textContent =
    places || "-";


// ================= AFFICHER LE PASSAGER =================

document.getElementById("nomComplet").textContent =
    `${nom || ""} ${prenom || ""}`.trim() || "-";

document.getElementById("telephone").textContent =
    telephone || "-";

document.getElementById("email").textContent =
    email || "Non renseigné";


// Afficher le type et le numéro de pièce

if (identite && numeroIdentite) {

    document.getElementById("identite").textContent =
        `${identite} - ${numeroIdentite}`;

} else {

    document.getElementById("identite").textContent =
        "-";
}


// ================= PRIX =================

const total =
    prix * passagers;

document.getElementById("prixUnitaire").textContent =
    prix.toLocaleString("fr-FR") + " BIF";

document.getElementById("nombrePlaces").textContent =
    passagers;

document.getElementById("total").textContent =
    total.toLocaleString("fr-FR") + " BIF";


// ================= NUMÉRO DE RÉSERVATION =================

// Pour le moment le numéro est généré par JavaScript.
// Plus tard PHP/MySQL générera le vrai numéro.

function genererReference() {

    const maintenant =
        new Date();

    const annee =
        maintenant.getFullYear();

    const mois =
        String(maintenant.getMonth() + 1)
        .padStart(2, "0");

    const jour =
        String(maintenant.getDate())
        .padStart(2, "0");

    const nombre =
        Math.floor(1000 + Math.random() * 9000);

    return `BT-${annee}${mois}${jour}-${nombre}`;
}


const reference =
    genererReference();


document.getElementById(
    "referenceReservation"
).textContent = reference;


// ================= MENU UTILISATEUR =================

const userButton =
    document.getElementById("userButton");

const userMenu =
    document.getElementById("userMenu");


userButton.addEventListener("click", function () {

    userMenu.classList.toggle("active");

});


// Fermer le menu si on clique ailleurs

document.addEventListener("click", function (event) {

    if (
        !userButton.contains(event.target) &&
        !userMenu.contains(event.target)
    ) {

        userMenu.classList.remove("active");

    }

});


// ================= BOUTON ACCUEIL =================

const homeButton =
    document.getElementById("homeButton");


homeButton.addEventListener("click", function () {

    window.location.href =
        "client-home.html";

});


// ================= MES RÉSERVATIONS =================

const reservationsButton =
    document.getElementById("reservationsButton");


reservationsButton.addEventListener(
    "click",
    function () {

        window.location.href =
            "mes-reservations.html";

    }
);


// ================= DÉCONNEXION =================

const logoutButton =
    document.getElementById("logoutButton");


logoutButton.addEventListener(
    "click",
    function () {

        const confirmer =
            confirm(
                "Voulez-vous vraiment vous déconnecter ?"
            );

        if (confirmer) {

            window.location.href =
                "../index.html";

        }

    }
);