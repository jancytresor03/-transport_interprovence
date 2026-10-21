/* =========================================
   PAGE AIDE
========================================= */


/* =========================
   ELEMENTS
========================= */

const menuButton =
    document.getElementById("menuButton");

const sidebar =
    document.getElementById("sidebar");

const logoutButton =
    document.getElementById("logoutButton");

const searchInput =
    document.getElementById("searchInput");

const categoryButtons =
    document.querySelectorAll(".category-button");

const faqItems =
    document.querySelectorAll(".faq-item");

const noResult =
    document.getElementById("noResult");

const notificationBadge =
    document.getElementById("notificationBadge");


/* =========================
   CATEGORIE ACTUELLE
========================= */

let categorieActuelle = "tous";


/* =========================
   MENU MOBILE
========================= */

menuButton.addEventListener("click", function () {

    sidebar.classList.toggle("open");

});


/* =========================
   QUESTIONS FAQ
========================= */

const questions =
    document.querySelectorAll(".faq-question");


questions.forEach(function (question) {

    question.addEventListener("click", function () {

        const faqItem =
            question.parentElement;


        /*
         * Si la question est déjà ouverte,
         * on la ferme.
         */

        if (faqItem.classList.contains("open")) {

            faqItem.classList.remove("open");

            return;
        }


        /*
         * Fermer les autres questions.
         */

        faqItems.forEach(function (item) {

            item.classList.remove("open");

        });


        /*
         * Ouvrir la question sélectionnée.
         */

        faqItem.classList.add("open");

    });

});


/* =========================
   FILTRER LES FAQ
========================= */

function filtrerQuestions() {

    const recherche =
        searchInput.value
            .toLowerCase()
            .trim();


    let nombreResultats = 0;


    faqItems.forEach(function (item) {

        const texte =
            item.textContent.toLowerCase();


        const categorie =
            item.dataset.category;


        const correspondRecherche =
            texte.includes(recherche);


        const correspondCategorie =
            categorieActuelle === "tous" ||
            categorie === categorieActuelle;


        if (
            correspondRecherche &&
            correspondCategorie
        ) {

            item.style.display = "block";

            nombreResultats++;

        } else {

            item.style.display = "none";

            item.classList.remove("open");

        }

    });


    /*
     * Afficher le message lorsqu'il
     * n'y a aucun résultat.
     */

    if (nombreResultats === 0) {

        noResult.style.display = "block";

    } else {

        noResult.style.display = "none";

    }

}


/* =========================
   RECHERCHE
========================= */

searchInput.addEventListener(
    "input",
    filtrerQuestions
);


/* =========================
   CATEGORIES
========================= */

categoryButtons.forEach(function (button) {

    button.addEventListener("click", function () {

        /*
         * Retirer active de tous les boutons.
         */

        categoryButtons.forEach(function (btn) {

            btn.classList.remove("active");

        });


        /*
         * Ajouter active au bouton sélectionné.
         */

        button.classList.add("active");


        /*
         * Récupérer la catégorie.
         */

        categorieActuelle =
            button.dataset.category;


        /*
         * Filtrer.
         */

        filtrerQuestions();

    });

});


/* =========================
   NOTIFICATIONS
========================= */

function afficherNombreNotifications() {

    const notifications =
        JSON.parse(
            localStorage.getItem(
                "burundiTransportNotifications"
            )
        ) || [];


    const nonLues =
        notifications.filter(function (notification) {

            return notification.lue === false;

        });


    notificationBadge.textContent =
        nonLues.length;

}


/* =========================
   DECONNEXION
========================= */

logoutButton.addEventListener("click", function () {

    const confirmation =
        confirm(
            "Voulez-vous vraiment vous déconnecter ?"
        );


    if (!confirmation) {

        return;

    }


    window.location.href =
        "../index.html";

});


/* =========================
   INITIALISATION
========================= */

afficherNombreNotifications();

filtrerQuestions();