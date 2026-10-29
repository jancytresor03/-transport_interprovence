
/* =========================================================
   BURUNDI TRANSPORT
   STATISTIQUES, GRAPHIQUES ET RAPPORTS PERSONNALISÉS
========================================================= */


/* =========================================================
   1. VARIABLES
========================================================= */

let statistiques = {};
let reservationsParMois = [];
let destinations = [];
let revenusParMois = [];

let reservationsChart = null;
let statutChart = null;
let destinationChart = null;
let revenusChart = null;

let rapportActuel = null;
let chargementEnCours = false;

const moisFrancais = [
    "Janvier", "Février", "Mars", "Avril",
    "Mai", "Juin", "Juillet", "Août",
    "Septembre", "Octobre", "Novembre", "Décembre"
];

const formatNombre = new Intl.NumberFormat("fr-FR");

const formatBIF = new Intl.NumberFormat("fr-FR", {
    style: "decimal",
    maximumFractionDigits: 0
});


/* =========================================================
   2. FONCTIONS UTILITAIRES
========================================================= */

function obtenirElement(id) {
    return document.getElementById(id);
}

function afficherMessage(message, erreur = false) {
    const zone =
        obtenirElement("reportMessage") ||
        obtenirElement("messageRapport");

    if (zone) {
        zone.textContent = message;
        zone.style.display = "block";
        zone.style.color = erreur ? "#b42318" : "#217346";
    } else {
        alert(message);
    }
}

function formaterNombre(valeur) {
    return formatNombre.format(Number(valeur) || 0);
}

function formaterBIF(valeur) {
    return formatBIF.format(Number(valeur) || 0) + " BIF";
}

function obtenirValeur(objet, cles, valeurParDefaut = 0) {
    for (const cle of cles) {
        if (
            objet &&
            objet[cle] !== undefined &&
            objet[cle] !== null
        ) {
            return objet[cle];
        }
    }

    return valeurParDefaut;
}


/* =========================================================
   3. CHARGEMENT DES STATISTIQUES GÉNÉRALES
========================================================= */

async function chargerDonnees(debut = "", fin = "") {
    if (chargementEnCours) {
        return false;
    }

    chargementEnCours = true;

    try {
        const parametres = new URLSearchParams();

        if (debut && fin) {
            parametres.set("debut", debut);
            parametres.set("fin", fin);
        }

        const url = "statistiques-api.php" +
            (parametres.toString()
                ? "?" + parametres.toString()
                : "");

        const reponse = await fetch(url, {
            method: "GET",
            headers: {
                "Accept": "application/json"
            },
            cache: "no-store",
            credentials: "same-origin"
        });

        const donnees = await reponse.json();

        if (
            !reponse.ok ||
            donnees.success === false ||
            donnees.erreur
        ) {
            throw new Error(
                donnees.message ||
                donnees.erreur ||
                "Impossible de charger les statistiques."
            );
        }

        statistiques = donnees.statistiques || {};
        reservationsParMois = donnees.reservationsParMois || [];
        destinations = donnees.destinations || [];
        revenusParMois = donnees.revenusParMois || [];

        afficherStatistiquesGenerales();

        return true;

    } catch (erreur) {
        console.error("Erreur statistiques :", erreur);

        afficherMessage(
            "Impossible de charger les statistiques. Vérifie statistiques-api.php.",
            true
        );

        return false;

    } finally {
        chargementEnCours = false;
    }
}


/* =========================================================
   4. AFFICHER LES CARTES STATISTIQUES
========================================================= */

function afficherStatistiquesGenerales() {
    const totalReservations = obtenirValeur(statistiques, [
        "totalReservations",
        "total_reservations",
        "total"
    ]);

    const confirmees = obtenirValeur(statistiques, [
        "reservationsConfirmees",
        "confirmees",
        "confirmee"
    ]);

    const revenus = obtenirValeur(statistiques, [
        "revenus",
        "revenusConfirmes",
        "revenuTotal",
        "totalRevenus",
        "montantTotal"
    ]);

    const utilisateurs = obtenirValeur(statistiques, [
        "totalUtilisateurs",
        "utilisateurs",
        "total_users"
    ]);

    const trajets = obtenirValeur(statistiques, [
        "totalTrajets",
        "trajets",
        "total_trajets"
    ]);

    const elements = [
        ["totalReservations", formaterNombre(totalReservations)],
        ["reservationsConfirmees", formaterNombre(confirmees)],
        ["totalRevenus", formaterBIF(revenus)],
        ["totalUtilisateurs", formaterNombre(utilisateurs)],
        ["tableTrajets", formaterNombre(trajets)],
        ["tableReservations", formaterNombre(totalReservations)],
        ["tableUtilisateurs", formaterNombre(utilisateurs)],
        ["tableRevenus", formaterBIF(revenus)]
    ];

    elements.forEach(([id, valeur]) => {
        const element = obtenirElement(id);

        if (element) {
            element.textContent = valeur;
        }
    });
}


/* =========================================================
   5. PRÉPARER LES DONNÉES DES GRAPHIQUES
========================================================= */

function preparerMois(lignes) {
    const lignesTriees = [...(lignes || [])].sort((a, b) => {
        return String(a.mois || "").localeCompare(String(b.mois || ""));
    });

    return {
        labels: lignesTriees.map(ligne => {
            const valeur = String(ligne.mois || "");

            if (valeur.includes("-")) {
                const morceaux = valeur.split("-");
                const annee = morceaux[0];
                const numeroMois = Number(morceaux[1]);

                if (numeroMois >= 1 && numeroMois <= 12) {
                    return moisFrancais[numeroMois - 1] + " " + annee;
                }
            }

            const numeroMois = Number(valeur);

            if (numeroMois >= 1 && numeroMois <= 12) {
                return moisFrancais[numeroMois - 1];
            }

            return valeur || "Non défini";
        }),

        valeurs: lignesTriees.map(ligne => {
            return Number(
                obtenirValeur(
                    ligne,
                    ["total", "nombre", "revenus", "montant"],
                    0
                )
            ) || 0;
        })
    };
}


/* =========================================================
   6. DÉTRUIRE LES ANCIENS GRAPHIQUES
========================================================= */

function detruireGraphiques() {
    [
        "reservationsChart",
        "statutChart",
        "destinationChart",
        "revenusChart"
    ].forEach(nom => {
        if (window[nom] && typeof window[nom].destroy === "function") {
            window[nom].destroy();
            window[nom] = null;
        }
    });

    if (reservationsChart) reservationsChart.destroy();
    if (statutChart) statutChart.destroy();
    if (destinationChart) destinationChart.destroy();
    if (revenusChart) revenusChart.destroy();

    reservationsChart = null;
    statutChart = null;
    destinationChart = null;
    revenusChart = null;
}


/* =========================================================
   7. GRAPHIQUE DES RÉSERVATIONS
========================================================= */

function creerGraphiqueReservations() {
    const canvas = obtenirElement("reservationsChart");

    if (!canvas || typeof Chart === "undefined") return;

    const donnees = preparerMois(reservationsParMois);

    reservationsChart = new Chart(canvas, {
        type: "line",
        data: {
            labels: donnees.labels,
            datasets: [{
                label: "Réservations",
                data: donnees.valeurs,
                borderColor: "#2c6e9b",
                backgroundColor: "rgba(44,110,155,0.15)",
                borderWidth: 3,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });
}


/* =========================================================
   8. GRAPHIQUE DES STATUTS
========================================================= */

function creerGraphiqueStatuts() {
    const canvas = obtenirElement("statutChart");

    if (!canvas || typeof Chart === "undefined") return;

    const confirmees = Number(obtenirValeur(statistiques, [
        "reservationsConfirmees", "confirmees", "confirmee"
    ])) || 0;

    const attente = Number(obtenirValeur(statistiques, [
        "reservationsEnAttente", "en_attente", "enAttente"
    ])) || 0;

    const annulees = Number(obtenirValeur(statistiques, [
        "reservationsAnnulees", "annulees", "annulee"
    ])) || 0;

    const expirees = Number(obtenirValeur(statistiques, [
        "reservationsExpirees", "expirees", "expiree"
    ])) || 0;

    statutChart = new Chart(canvas, {
        type: "doughnut",
        data: {
            labels: [
                "Confirmées",
                "En attente",
                "Annulées",
                "Expirées"
            ],
            datasets: [{
                data: [confirmees, attente, annulees, expirees],
                backgroundColor: [
                    "#2e8b57",
                    "#e8b04b",
                    "#d9534f",
                    "#7c6db0"
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: "bottom" }
            }
        }
    });
}


/* =========================================================
   9. GRAPHIQUE DES DESTINATIONS
========================================================= */

function creerGraphiqueDestinations() {
    const canvas = obtenirElement("destinationChart");

    if (!canvas || typeof Chart === "undefined") return;

    destinationChart = new Chart(canvas, {
        type: "bar",
        data: {
            labels: destinations.map(ligne =>
                ligne.destination || ligne.nom || "Non définie"
            ),
            datasets: [{
                label: "Réservations",
                data: destinations.map(ligne =>
                    Number(obtenirValeur(
                        ligne,
                        ["total", "nombre", "reservations"],
                        0
                    )) || 0
                ),
                backgroundColor: "#4a8db8",
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });
}


/* =========================================================
   10. GRAPHIQUE DES REVENUS
========================================================= */

function creerGraphiqueRevenus() {
    const canvas = obtenirElement("revenusChart");

    if (!canvas || typeof Chart === "undefined") return;

    const donnees = preparerMois(revenusParMois);

    revenusChart = new Chart(canvas, {
        type: "bar",
        data: {
            labels: donnees.labels,
            datasets: [{
                label: "Revenus en BIF",
                data: donnees.valeurs,
                backgroundColor: "#2e8b57",
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: valeur => formaterNombre(valeur)
                    }
                }
            }
        }
    });
}


/* =========================================================
   11. CRÉER LES GRAPHIQUES
========================================================= */

function creerGraphiques() {
    detruireGraphiques();

    creerGraphiqueReservations();
    creerGraphiqueStatuts();
    creerGraphiqueDestinations();
    creerGraphiqueRevenus();
}


/* =========================================================
   12. ACTUALISER LA PAGE
========================================================= */

async function actualiserPage() {
    const succes = await chargerDonnees();

    if (succes) {
        creerGraphiques();
    }
}


/* =========================================================
   13. LIRE LE TYPE ET LES DATES DU RAPPORT
========================================================= */

function obtenirTypeRapport() {
    const select = obtenirElement("reportType");
    return select ? select.value : "complet";
}

function obtenirDatesRapport() {
    const debut = obtenirElement("reportStartDate")?.value || "";
    const fin = obtenirElement("reportEndDate")?.value || "";

    if (debut && fin && debut > fin) {
        throw new Error(
            "La date de début doit être antérieure à la date de fin."
        );
    }

    return { debut, fin };
}


/* =========================================================
   14. GÉNÉRER LE RAPPORT PERSONNALISÉ
========================================================= */

async function genererRapportPersonnalise() {
    const bouton = obtenirElement("applyReportButton");
    const zone = obtenirElement("reportResults");

    try {
        const type = obtenirTypeRapport();
        const { debut, fin } = obtenirDatesRapport();

        if (bouton) {
            bouton.disabled = true;
            bouton.textContent = "Chargement...";
        }

        afficherMessage("Chargement du rapport...");

        const parametres = new URLSearchParams();
        parametres.set("type", type);

        if (debut) parametres.set("debut", debut);
        if (fin) parametres.set("fin", fin);

        const reponse = await fetch(
            "statistiques-api.php?" + parametres.toString(),
            {
                headers: { "Accept": "application/json" },
                cache: "no-store",
                credentials: "same-origin"
            }
        );

        const donnees = await reponse.json();

        if (
            !reponse.ok ||
            donnees.success === false ||
            donnees.erreur
        ) {
            throw new Error(
                donnees.message ||
                donnees.erreur ||
                "Impossible de générer le rapport."
            );
        }

        const rapport = donnees.rapport;

        if (
            !rapport ||
            !Array.isArray(rapport.colonnes) ||
            !Array.isArray(rapport.lignes)
        ) {
            throw new Error(
                "L'API PHP ne renvoie pas encore le format de rapport attendu."
            );
        }

        const titre = obtenirElement("reportResultsTitle");
        const description = obtenirElement("reportResultsDescription");
        const entete = obtenirElement("reportResultsHead");
        const corps = obtenirElement("reportResultsBody");

        if (!zone || !entete || !corps) {
            throw new Error(
                "Ajoute la zone reportResults dans statistiques.php."
            );
        }

        if (titre) {
            titre.textContent = rapport.titre || "Résultat du rapport";
        }

        if (description) {
            description.textContent =
                debut || fin
                    ? `Période : ${debut || "sans début"} au ${fin || "sans fin"}`
                    : "Toutes les données disponibles";
        }

        entete.replaceChildren();
        corps.replaceChildren();

        const trEntete = document.createElement("tr");

        rapport.colonnes.forEach(colonne => {
            const th = document.createElement("th");
            th.textContent = String(colonne);
            trEntete.appendChild(th);
        });

        entete.appendChild(trEntete);

        rapport.lignes.forEach(ligne => {
            const tr = document.createElement("tr");

            ligne.forEach(valeur => {
                const td = document.createElement("td");
                td.textContent =
                    valeur === null || valeur === undefined
                        ? ""
                        : String(valeur);

                tr.appendChild(td);
            });

            corps.appendChild(tr);
        });

        rapportActuel = rapport;
        zone.hidden = false;

        afficherMessage(
            `Rapport généré : ${rapport.lignes.length} ligne(s).`
        );

        zone.scrollIntoView({
            behavior: "smooth",
            block: "start"
        });

    } catch (erreur) {
        console.error("Erreur du rapport :", erreur);
        afficherMessage(erreur.message, true);

    } finally {
        if (bouton) {
            bouton.disabled = false;
            bouton.innerHTML =
                '<i class="fa-solid fa-check"></i> Générer le rapport';
        }
    }
}


/* =========================================================
   15. EXPORT CSV POUR EXCEL
========================================================= */

function echapperCSV(valeur) {
    let texte = String(valeur ?? "");

    if (/^[=+\-@\t\r]/.test(texte)) {
        texte = "'" + texte;
    }

    return '"' + texte.replace(/"/g, '""') + '"';
}

function telechargerCSV(lignes, nomFichier) {
    const contenu = "\uFEFF" + lignes
        .map(ligne => ligne.map(echapperCSV).join(";"))
        .join("\r\n");

    const fichier = new Blob([contenu], {
        type: "text/csv;charset=utf-8;"
    });

    const url = URL.createObjectURL(fichier);
    const lien = document.createElement("a");

    lien.href = url;
    lien.download = nomFichier;

    document.body.appendChild(lien);
    lien.click();
    lien.remove();

    URL.revokeObjectURL(url);
}

function exporterCSV() {
    const lignes = [
        ["Rapport Burundi Transport"],
        ["Date d'export", new Date().toLocaleString("fr-FR")],
        [],
        ["Indicateur", "Valeur"],
        [
            "Total des réservations",
            obtenirValeur(statistiques, [
                "totalReservations", "total_reservations", "total"
            ])
        ],
        [
            "Réservations confirmées",
            obtenirValeur(statistiques, [
                "reservationsConfirmees", "confirmees", "confirmee"
            ])
        ],
        [
            "Revenus confirmés (BIF)",
            obtenirValeur(statistiques, [
                "revenus", "revenusConfirmes", "revenuTotal",
                "totalRevenus", "montantTotal"
            ])
        ],
        [
            "Utilisateurs",
            obtenirValeur(statistiques, [
                "totalUtilisateurs", "utilisateurs", "total_users"
            ])
        ],
        [
            "Trajets",
            obtenirValeur(statistiques, [
                "totalTrajets", "trajets", "total_trajets"
            ])
        ]
    ];

    lignes.push([], ["Réservations par mois"], ["Mois", "Nombre"]);

    const reservations = preparerMois(reservationsParMois);

    reservations.labels.forEach((mois, index) => {
        lignes.push([mois, reservations.valeurs[index]]);
    });

    lignes.push([], ["Revenus par mois"], ["Mois", "Revenus (BIF)"]);

    const revenus = preparerMois(revenusParMois);

    revenus.labels.forEach((mois, index) => {
        lignes.push([mois, revenus.valeurs[index]]);
    });

    lignes.push([], ["Destinations"], ["Destination", "Réservations"]);

    destinations.forEach(destination => {
        lignes.push([
            destination.destination || destination.nom || "Non définie",
            obtenirValeur(destination, [
                "total", "nombre", "reservations"
            ], 0)
        ]);
    });

    telechargerCSV(lignes, "rapport_burundi_transport.csv");

    afficherMessage("Export CSV terminé.");
}

function exporterRapportPersonnalise() {
    if (
        !rapportActuel ||
        !Array.isArray(rapportActuel.colonnes) ||
        !Array.isArray(rapportActuel.lignes)
    ) {
        exporterCSV();
        return;
    }

    const lignes = [
        [rapportActuel.titre || "Rapport personnalisé"],
        ["Date d'export", new Date().toLocaleString("fr-FR")],
        [],
        rapportActuel.colonnes,
        ...rapportActuel.lignes
    ];

    telechargerCSV(lignes, "rapport_personnalise.csv");

    afficherMessage("Rapport personnalisé exporté.");
}


/* =========================================================
   16. IMPRESSION / PDF
========================================================= */

function imprimerRapport() {
    window.print();
}


/* =========================================================
   17. AFFICHER LES CONTRÔLES
========================================================= */

function afficherControlesRapport() {
    const controles = obtenirElement("reportControls");

    if (!controles) return;

    controles.hidden = false;
    controles.style.display = "block";

    controles.scrollIntoView({
        behavior: "smooth",
        block: "center"
    });
}


/* =========================================================
   18. COMPARAISON ET PRÉVISIONS
========================================================= */


async function afficherComparaison() {
    const panneau = document.getElementById("comparisonPanel");
    const message = document.getElementById("comparisonMessage");
    const resultats = document.getElementById("comparisonResults");
    const tableau = document.getElementById("comparisonResultsBody");

    const debut1 = document.getElementById("comparisonStart1");
    const fin1 = document.getElementById("comparisonEnd1");
    const debut2 = document.getElementById("comparisonStart2");
    const fin2 = document.getElementById("comparisonEnd2");

    if (!panneau || !message || !resultats || !tableau ||
        !debut1 || !fin1 || !debut2 || !fin2) {
        console.error("Éléments HTML de comparaison introuvables.");
        return;
    }

    panneau.hidden = false;
    resultats.hidden = true;
    message.textContent = "Sélectionne les dates des deux périodes.";

    // Afficher le panneau et demander les dates
    if (!debut1.value || !fin1.value || !debut2.value || !fin2.value) {
        panneau.scrollIntoView({ behavior: "smooth", block: "start" });
        return;
    }

    if (debut1.value > fin1.value || debut2.value > fin2.value) {
        message.textContent =
            "Chaque date de début doit précéder sa date de fin.";
        return;
    }

    message.textContent = "Comparaison en cours...";

    try {
        const parametres = new URLSearchParams({
            action: "comparaison",
            debut1: debut1.value,
            fin1: fin1.value,
            debut2: debut2.value,
            fin2: fin2.value
        });

        const reponse = await fetch(
            "statistiques-api.php?" + parametres.toString()
        );

        const donnees = await reponse.json();

        if (!reponse.ok || !donnees.success) {
            throw new Error(
                donnees.erreur || "Impossible de comparer les périodes."
            );
        }

        const comparaison = donnees.comparaison;

        function formaterNombre(valeur) {
            return Number(valeur).toLocaleString("fr-FR", {
                maximumFractionDigits: 2
            });
        }

        function formaterPourcentage(valeur) {
            if (valeur === null || valeur === undefined) {
                return "Non calculable";
            }

            return formaterNombre(valeur) + " %";
        }

        const lignes = [
            {
                nom: "Réservations",
                p1: comparaison.periode1.reservations,
                p2: comparaison.periode2.reservations,
                difference: comparaison.difference.reservations,
                evolution: comparaison.evolution.reservations
            },
            {
                nom: "Revenus confirmés (BIF)",
                p1: comparaison.periode1.revenus,
                p2: comparaison.periode2.revenus,
                difference: comparaison.difference.revenus,
                evolution: comparaison.evolution.revenus
            }
        ];

        tableau.innerHTML = "";

        lignes.forEach((ligne) => {
            const tr = document.createElement("tr");

            [
                ligne.nom,
                formaterNombre(ligne.p1),
                formaterNombre(ligne.p2),
                formaterNombre(ligne.difference),
                formaterPourcentage(ligne.evolution)
            ].forEach((valeur) => {
                const td = document.createElement("td");
                td.textContent = valeur;
                tr.appendChild(td);
            });

            tableau.appendChild(tr);
        });

        const canvas = document.getElementById("comparisonChart");

        if (canvas) {
            if (window.comparisonChart instanceof Chart) {
                window.comparisonChart.destroy();
            }

            window.comparisonChart = new Chart(canvas, {
                type: "bar",
                data: {
                    labels: ["Réservations", "Revenus confirmés (BIF)"],
                    datasets: [
                        {
                            label: "Première période",
                            data: [
                                comparaison.periode1.reservations,
                                comparaison.periode1.revenus
                            ],
                            backgroundColor: "#2c6e9b"
                        },
                        {
                            label: "Deuxième période",
                            data: [
                                comparaison.periode2.reservations,
                                comparaison.periode2.revenus
                            ],
                            backgroundColor: "#e8b04b"
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        resultats.hidden = false;
        message.textContent = "Comparaison terminée.";

        panneau.scrollIntoView({ behavior: "smooth", block: "start" });

    } catch (erreur) {
        console.error("Erreur de comparaison :", erreur);
        message.textContent = erreur.message;
    }
}



async function afficherPrevisions() {
    const panneau = document.getElementById("forecastPanel");
    const resultat = document.getElementById("forecastResults");
    const message = document.getElementById("forecastMessage");
    const tableau = document.getElementById("forecastResultsBody");
    const selectMois = document.getElementById("forecastMonths");

    if (!panneau || !resultat || !message || !tableau || !selectMois) {
        console.error("Certains éléments HTML des prévisions sont introuvables.");
        return;
    }

    // Afficher le panneau des prévisions
    panneau.hidden = false;
    resultat.hidden = true;
    message.textContent = "Calcul des prévisions en cours...";
    tableau.innerHTML = "";

    try {
        const mois = selectMois.value;

        const reponse = await fetch(
            `statistiques-api.php?action=previsions&mois=${encodeURIComponent(mois)}`
        );

        const donnees = await reponse.json();

        if (!reponse.ok || !donnees.success) {
            throw new Error(
                donnees.erreur || "Impossible de calculer les prévisions."
            );
        }

        const previsions = donnees.previsions;

        if (!Array.isArray(previsions) || previsions.length === 0) {
            throw new Error("Aucune prévision n'a été retournée.");
        }

        // Remplir le tableau
        tableau.innerHTML = "";

        previsions.forEach((ligne) => {
            const tr = document.createElement("tr");

            const moisCellule = document.createElement("td");
            moisCellule.textContent = ligne.mois;

            const reservationsCellule = document.createElement("td");
            reservationsCellule.textContent =
                Number(ligne.reservations).toLocaleString("fr-FR");

            const revenusCellule = document.createElement("td");
            revenusCellule.textContent =
                Number(ligne.revenus).toLocaleString("fr-FR") + " BIF";

            tr.appendChild(moisCellule);
            tr.appendChild(reservationsCellule);
            tr.appendChild(revenusCellule);

            tableau.appendChild(tr);
        });

        // Préparer les données des graphiques
        const etiquettes = previsions.map((ligne) => ligne.mois);
        const reservations = previsions.map((ligne) =>
            Number(ligne.reservations)
        );
        const revenus = previsions.map((ligne) => Number(ligne.revenus));

        // Détruire les anciens graphiques avant de les recréer
        if (window.forecastReservationsChart instanceof Chart) {
            window.forecastReservationsChart.destroy();
        }

        if (window.forecastRevenueChart instanceof Chart) {
            window.forecastRevenueChart.destroy();
        }

        const canvasReservations =
            document.getElementById("forecastReservationsChart");
        const canvasRevenus =
            document.getElementById("forecastRevenueChart");

        if (!canvasReservations || !canvasRevenus) {
            throw new Error("Les éléments des graphiques sont introuvables.");
        }

        window.forecastReservationsChart = new Chart(
            canvasReservations,
            {
                type: "line",
                data: {
                    labels: etiquettes,
                    datasets: [{
                        label: "Réservations estimées",
                        data: reservations,
                        borderColor: "#2c6e9b",
                        backgroundColor: "rgba(44, 110, 155, 0.15)",
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            }
        );

        window.forecastRevenueChart = new Chart(
            canvasRevenus,
            {
                type: "line",
                data: {
                    labels: etiquettes,
                    datasets: [{
                        label: "Revenus estimés (BIF)",
                        data: revenus,
                        borderColor: "#e8b04b",
                        backgroundColor: "rgba(232, 176, 75, 0.15)",
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            }
        );

        resultat.hidden = false;
        message.textContent =
            "Prévisions calculées. Méthode utilisée : " + donnees.methode + ".";

    } catch (erreur) {
        console.error("Erreur des prévisions :", erreur);
        message.textContent = erreur.message;
    }
}



/* =========================================================
   19. INITIALISATION DES ÉVÉNEMENTS
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    // Actualiser les statistiques générales
    const boutonActualiser = obtenirElement("refreshButton");

    if (boutonActualiser) {
        boutonActualiser.addEventListener("click", actualiserPage);
    }

    // Imprimer / PDF
    const boutonPDF = obtenirElement("printReportButton");

    if (boutonPDF) {
        boutonPDF.addEventListener("click", imprimerRapport);
    }

    // Exporter Excel/CSV : exporter le rapport sélectionné si disponible
    const boutonExcel = obtenirElement("exportExcelButton");

    if (boutonExcel) {
        boutonExcel.addEventListener("click", exporterRapportPersonnalise);
    }

    // Rapports périodiques
    const boutonPeriodique = obtenirElement("periodicReportButton");

    if (boutonPeriodique) {
        boutonPeriodique.addEventListener("click", function () {
            afficherControlesRapport();

            const titre = obtenirElement("reportControlsTitle");

            if (titre) {
                titre.textContent = "Rapports périodiques";
            }
        });
    }

    // Rapport personnalisé
    const boutonPersonnalise = obtenirElement("customReportButton");

    if (boutonPersonnalise) {
        boutonPersonnalise.addEventListener("click", function () {
            afficherControlesRapport();

            const titre = obtenirElement("reportControlsTitle");

            if (titre) {
                titre.textContent = "Rapport personnalisé";
            }
        });
    }

    // Générer le rapport
    const boutonRapport = obtenirElement("applyReportButton");

    if (boutonRapport) {
        boutonRapport.addEventListener(
            "click",
            genererRapportPersonnalise
        );
    }

    // Comparaison
    const boutonComparaison = obtenirElement("comparisonButton");

    if (boutonComparaison) {
        boutonComparaison.addEventListener("click", afficherComparaison);
    }

    // Prévisions
	

    const boutonPrevisions = obtenirElement("forecastButton");

    if (boutonPrevisions) {
        boutonPrevisions.addEventListener("click", afficherPrevisions);
    }

    // Menu mobile
    const boutonMenu = obtenirElement("menuButton");
    const sidebar = document.querySelector(".sidebar");

    if (boutonMenu && sidebar) {
        boutonMenu.addEventListener("click", function () {
            sidebar.classList.toggle("active");
        });
    }

    // Chargement initial
    actualiserPage();
});
