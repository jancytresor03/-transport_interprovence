
// ================= BOUTONS DE CONNEXION =================

const btnLogin = document.getElementById("btnLogin");

btnLogin.addEventListener("click", function () {

    window.location.href = "login.html";

});


// ================= BOUTON INSCRIPTION =================

const btnRegister = document.getElementById("btnRegister");

btnRegister.addEventListener("click", function () {

    window.location.href = "register.html";

});


// ================= BOUTON COMMENCER =================

const btnStart = document.getElementById("btnStart");

btnStart.addEventListener("click", function () {

    const searchSection = document.querySelector(".search-section");

    searchSection.scrollIntoView({
        behavior: "smooth"
    });

});


// ================= FORMULAIRE DE RECHERCHE =================

const searchForm = document.getElementById("searchForm");

searchForm.addEventListener("submit", function (event) {

    event.preventDefault();

    const depart = document.getElementById("depart").value;

    const destination = document.getElementById("destination").value;

    const date = document.getElementById("date").value;

    const passagers = document.getElementById("passagers").value;


    // Vérifier que le départ et la destination sont différents

    if (depart === destination) {

        alert("Le départ et la destination doivent être différents.");

        return;
    }


    // Vérifier les informations

    if (
        depart === "" ||
        destination === "" ||
        date === "" ||
        passagers === ""
    ) {

        alert("Veuillez remplir tous les champs.");

        return;
    }


    // Pour le moment, nous affichons simplement les informations

    alert(
        "Recherche effectuée !\n\n" +
        "Départ : " + depart + "\n" +
        "Destination : " + destination + "\n" +
        "Date : " + date + "\n" +
        "Passagers : " + passagers
    );


    // Plus tard :
    // window.location.href = "trajets.html";

});
