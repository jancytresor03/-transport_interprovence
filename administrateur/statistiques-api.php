
<?php

require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");

require_once __DIR__ . "/../database.php";

header("Content-Type: application/json; charset=UTF-8");


/* =========================================================
   1. FONCTIONS UTILITAIRES
========================================================= */

function repondreJSON($donnees, $code = 200)
{
    http_response_code($code);

    echo json_encode(
        $donnees,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

function validerDate($date)
{
    if (!is_string($date) || $date === "") {
        return false;
    }

    $objetDate = DateTime::createFromFormat("!Y-m-d", $date);

    return $objetDate !== false
        && $objetDate->format("Y-m-d") === $date;
}

/*
 * Construit le filtre de date pour une requête SQL.
 * Les paramètres sont ajoutés uniquement si une date existe.
 */
function construireFiltreDate($debut, $fin, &$parametres, $prefixe = "")
{
    $conditions = [];

    if ($debut !== null && $debut !== "") {
        $nom = ":debut" . $prefixe;

        $conditions[] = "date_creation >= " . $nom;
        $parametres[$nom] = $debut;
    }

    if ($fin !== null && $fin !== "") {
        $nom = ":fin" . $prefixe;

        $conditions[] =
            "date_creation < DATE_ADD(" . $nom . ", INTERVAL 1 DAY)";

        $parametres[$nom] = $fin;
    }

    return count($conditions) > 0
        ? implode(" AND ", $conditions)
        : "1 = 1";
}


/* =========================================================
   2. LECTURE ET VALIDATION DES PARAMÈTRES
========================================================= */

try {

    $debut = $_GET["debut"] ?? null;
    $fin = $_GET["fin"] ?? null;

    if ($debut === "") $debut = null;
    if ($fin === "") $fin = null;

    if (
        ($debut !== null && !validerDate($debut)) ||
        ($fin !== null && !validerDate($fin))
    ) {
        repondreJSON([
            "success" => false,
            "erreur" => "Une date sélectionnée est invalide."
        ], 400);
    }

    if ($debut !== null && $fin !== null && $debut > $fin) {
        repondreJSON([
            "success" => false,
            "erreur" => "La date de début doit précéder la date de fin."
        ], 400);
    }

    $typeRapport = $_GET["type"] ?? null;

    $typesAutorises = [
        "trajets",
        "reservations",
        "revenus",
        "utilisateurs",
        "complet"
    ];

    if (
        $typeRapport !== null &&
        !in_array($typeRapport, $typesAutorises, true)
    ) {
        repondreJSON([
            "success" => false,
            "erreur" => "Le type de rapport sélectionné est invalide."
        ], 400);
    }
	
	
	
	
    /* =====================================================
       2B. ANALYSE COMPARATIVE
    ===================================================== */

    $action = $_GET["action"] ?? "";

    if ($action === "comparaison") {

        $debut1 = $_GET["debut1"] ?? "";
        $fin1 = $_GET["fin1"] ?? "";
        $debut2 = $_GET["debut2"] ?? "";
        $fin2 = $_GET["fin2"] ?? "";

        // Les quatre dates sont obligatoires.
        if (
            !validerDate($debut1) ||
            !validerDate($fin1) ||
            !validerDate($debut2) ||
            !validerDate($fin2)
        ) {
            repondreJSON([
                "success" => false,
                "erreur" => "Sélectionne les quatre dates de comparaison."
            ], 400);
        }

        if ($debut1 > $fin1 || $debut2 > $fin2) {
            repondreJSON([
                "success" => false,
                "erreur" => "Chaque date de début doit précéder sa date de fin."
            ], 400);
        }

        $sqlComparaison = "
            SELECT
                COUNT(*) AS total_reservations,
                COALESCE(
                    SUM(
                        CASE
                            WHEN statut = 'confirmee'
                            THEN montant_total
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_revenus
            FROM reservations
            WHERE date_creation >= :debut
              AND date_creation < DATE_ADD(:fin, INTERVAL 1 DAY)
        ";

        $requeteComparaison = $pdo->prepare($sqlComparaison);

        // Première période
        $requeteComparaison->execute([
            ":debut" => $debut1,
            ":fin" => $fin1
        ]);

        $periode1 = $requeteComparaison->fetch(PDO::FETCH_ASSOC);

        // Deuxième période
        $requeteComparaison->execute([
            ":debut" => $debut2,
            ":fin" => $fin2
        ]);

        $periode2 = $requeteComparaison->fetch(PDO::FETCH_ASSOC);

        $periode1 = [
            "reservations" => (int) $periode1["total_reservations"],
            "revenus" => (float) $periode1["total_revenus"]
        ];

        $periode2 = [
            "reservations" => (int) $periode2["total_reservations"],
            "revenus" => (float) $periode2["total_revenus"]
        ];

        $calculerEvolution = function ($ancienneValeur, $nouvelleValeur) {
            if ($ancienneValeur == 0) {
                return $nouvelleValeur == 0 ? 0 : null;
            }

            return round(
                (($nouvelleValeur - $ancienneValeur) / $ancienneValeur) * 100,
                2
            );
        };

        repondreJSON([
            "success" => true,
            "comparaison" => [
                "periode1" => $periode1,
                "periode2" => $periode2,
                "difference" => [
                    "reservations" =>
                        $periode2["reservations"] - $periode1["reservations"],
                    "revenus" =>
                        $periode2["revenus"] - $periode1["revenus"]
                ],
                "evolution" => [
                    "reservations" => $calculerEvolution(
                        $periode1["reservations"],
                        $periode2["reservations"]
                    ),
                    "revenus" => $calculerEvolution(
                        $periode1["revenus"],
                        $periode2["revenus"]
                    )
                ]
            ]
        ]);
    }


    /* =====================================================
       2C. PRÉVISIONS STATISTIQUES
    ===================================================== */

    if ($action === "previsions") {

        $nombreMois = filter_var(
            $_GET["mois"] ?? 3,
            FILTER_VALIDATE_INT
        );

        if (
            $nombreMois === false ||
            !in_array($nombreMois, [3, 6, 12], true)
        ) {
            repondreJSON([
                "success" => false,
                "erreur" => "Choisis une prévision de 3, 6 ou 12 mois."
            ], 400);
        }

        /*
         * On récupère les 12 derniers mois calendaires.
         * Les revenus concernent uniquement les réservations confirmées.
         */
        $debutHistorique = new DateTime("first day of this month");
        $debutHistorique->modify("-11 months");

        $debutHistoriqueSQL = $debutHistorique->format("Y-m-d");
        $finHistoriqueSQL = (new DateTime("first day of next month"))
            ->format("Y-m-d");

        $sqlHistorique = "
            SELECT
                DATE_FORMAT(date_creation, '%Y-%m') AS mois,
                COUNT(*) AS reservations,
                COALESCE(
                    SUM(
                        CASE
                            WHEN statut = 'confirmee'
                            THEN montant_total
                            ELSE 0
                        END
                    ),
                    0
                ) AS revenus
            FROM reservations
            WHERE date_creation >= :debut
              AND date_creation < :fin
            GROUP BY DATE_FORMAT(date_creation, '%Y-%m')
            ORDER BY mois
        ";

        $requeteHistorique = $pdo->prepare($sqlHistorique);

        $requeteHistorique->execute([
            ":debut" => $debutHistoriqueSQL,
            ":fin" => $finHistoriqueSQL
        ]);

        $resultatsHistorique = $requeteHistorique->fetchAll(PDO::FETCH_ASSOC);

        // Préparer les 12 mois, y compris ceux sans réservation.
        $historiqueParMois = [];

        foreach ($resultatsHistorique as $ligne) {
            $historiqueParMois[$ligne["mois"]] = [
                "reservations" => (int) $ligne["reservations"],
                "revenus" => (float) $ligne["revenus"]
            ];
        }

        $historique = [];
        $curseur = clone $debutHistorique;

        for ($i = 0; $i < 12; $i++) {
            $cleMois = $curseur->format("Y-m");

            $historique[] = [
                "mois" => $cleMois,
                "reservations" =>
                    $historiqueParMois[$cleMois]["reservations"] ?? 0,
                "revenus" =>
                    $historiqueParMois[$cleMois]["revenus"] ?? 0
            ];

            $curseur->modify("+1 month");
        }

        // Moyenne des trois derniers mois calendaires.
        $troisDerniersMois = array_slice($historique, -3);

        $moyenneReservations = array_sum(
            array_column($troisDerniersMois, "reservations")
        ) / 3;

        $moyenneRevenus = array_sum(
            array_column($troisDerniersMois, "revenus")
        ) / 3;

        $previsions = [];
        $prochainMois = new DateTime("first day of next month");

        for ($i = 0; $i < $nombreMois; $i++) {
            $previsions[] = [
                "mois" => $prochainMois->format("Y-m"),
                "reservations" => round($moyenneReservations, 2),
                "revenus" => round($moyenneRevenus, 2)
            ];

            $prochainMois->modify("+1 month");
        }

        repondreJSON([
            "success" => true,
            "methode" => "Moyenne des trois derniers mois calendaires",
            "nombreMois" => $nombreMois,
            "historique" => $historique,
            "previsions" => $previsions
        ]);
    }

	
	


    /* =====================================================
       3. STATISTIQUES GÉNÉRALES
    ===================================================== */

    $parametresReservations = [];

    $conditionReservations = construireFiltreDate(
        $debut,
        $fin,
        $parametresReservations
    );

    $sqlReservations = "
        SELECT
            COUNT(*) AS total_reservations,

            COALESCE(SUM(
                CASE WHEN statut = 'confirmee'
                THEN 1 ELSE 0 END
            ), 0) AS reservations_confirmees,

            COALESCE(SUM(
                CASE WHEN statut = 'en_attente'
                THEN 1 ELSE 0 END
            ), 0) AS reservations_attente,

            COALESCE(SUM(
                CASE WHEN statut = 'annulee'
                THEN 1 ELSE 0 END
            ), 0) AS reservations_annulees,

            COALESCE(SUM(
                CASE WHEN statut = 'expiree'
                THEN 1 ELSE 0 END
            ), 0) AS reservations_expirees,

            COALESCE(SUM(
                CASE WHEN statut = 'confirmee'
                THEN montant_total ELSE 0 END
            ), 0) AS total_revenus

        FROM reservations
        WHERE $conditionReservations
    ";

    $requete = $pdo->prepare($sqlReservations);
    $requete->execute($parametresReservations);

    $reservations = $requete->fetch(PDO::FETCH_ASSOC);


    /* =====================================================
       4. UTILISATEURS ET TRAJETS
    ===================================================== */

    $requete = $pdo->query("
        SELECT COUNT(*) AS total_utilisateurs
        FROM users
    ");

    $utilisateurs = $requete->fetch(PDO::FETCH_ASSOC);

    $requete = $pdo->query("
        SELECT COUNT(*) AS total_trajets
        FROM trajets
    ");

    $trajets = $requete->fetch(PDO::FETCH_ASSOC);


    /* =====================================================
       5. RÉSERVATIONS PAR MOIS
    ===================================================== */

    $sqlParMois = "
        SELECT
            DATE_FORMAT(date_creation, '%Y-%m') AS mois,
            COUNT(*) AS total
        FROM reservations
        WHERE $conditionReservations
        GROUP BY DATE_FORMAT(date_creation, '%Y-%m')
        ORDER BY mois
    ";

    $requete = $pdo->prepare($sqlParMois);
    $requete->execute($parametresReservations);

    $reservationsParMois = $requete->fetchAll(PDO::FETCH_ASSOC);


    /* =====================================================
       6. DESTINATIONS
    ===================================================== */

    $parametresDestinations = [];

    $conditionDestinations = construireFiltreDate(
        $debut,
        $fin,
        $parametresDestinations,
        "dest"
    );

    $sqlDestinations = "
        SELECT
            t.destination,
            COUNT(r.reservation_id) AS total
        FROM trajets t

        LEFT JOIN reservations r
            ON t.trajet_id = r.trajet_id
            AND $conditionDestinations

        GROUP BY t.destination
        ORDER BY total DESC
    ";

    $requete = $pdo->prepare($sqlDestinations);
    $requete->execute($parametresDestinations);

    $destinations = $requete->fetchAll(PDO::FETCH_ASSOC);


    /* =====================================================
       7. REVENUS PAR MOIS
    ===================================================== */

    $sqlRevenus = "
        SELECT
            DATE_FORMAT(date_creation, '%Y-%m') AS mois,
            COALESCE(SUM(montant_total), 0) AS total
        FROM reservations
        WHERE statut = 'confirmee'
          AND $conditionReservations
        GROUP BY DATE_FORMAT(date_creation, '%Y-%m')
        ORDER BY mois
    ";

    $requete = $pdo->prepare($sqlRevenus);
    $requete->execute($parametresReservations);

    $revenusParMois = $requete->fetchAll(PDO::FETCH_ASSOC);


    /* =====================================================
       8. CONSTRUIRE LE RÉSUMÉ GÉNÉRAL
    ===================================================== */

    $statistiques = [
        "totalReservations" =>
            (int) $reservations["total_reservations"],

        "reservationsConfirmees" =>
            (int) $reservations["reservations_confirmees"],

        "reservationsEnAttente" =>
            (int) $reservations["reservations_attente"],

        "reservationsAnnulees" =>
            (int) $reservations["reservations_annulees"],

        "reservationsExpirees" =>
            (int) $reservations["reservations_expirees"],

        "totalRevenus" =>
            (float) $reservations["total_revenus"],

        "totalUtilisateurs" =>
            (int) $utilisateurs["total_utilisateurs"],

        "totalTrajets" =>
            (int) $trajets["total_trajets"]
    ];


    /* =====================================================
       9. SI AUCUN RAPPORT PERSONNALISÉ N'EST DEMANDÉ
       RETOURNER LES DONNÉES POUR LES GRAPHIQUES
    ===================================================== */

    if ($typeRapport === null) {

        repondreJSON([
            "success" => true,
            "statistiques" => $statistiques,
            "reservationsParMois" => $reservationsParMois,
            "destinations" => $destinations,
            "revenusParMois" => $revenusParMois
        ]);
    }


    /* =====================================================
       10. RAPPORT PERSONNALISÉ : TRAJETS
    ===================================================== */

    if ($typeRapport === "trajets") {

        /*
         * Les champs ci-dessous sont à confirmer avec la
         * structure réelle de ta table trajets.
         */
        $sql = "
            SELECT
                trajet_id,
                depart,
                destination,
                date_depart,
                heure_depart,
                prix
            FROM trajets
            ORDER BY trajet_id DESC
        ";

        $requete = $pdo->query($sql);
        $lignes = $requete->fetchAll(PDO::FETCH_NUM);

        repondreJSON([
            "success" => true,
            "rapport" => [
                "titre" => "Rapport des trajets",
                "colonnes" => [
                    "ID",
                    "Départ",
                    "Destination",
                    "Date de départ",
                    "Heure de départ",
                    "Prix (BIF)"
                ],
                "lignes" => $lignes
            ]
        ]);
    }


    /* =====================================================
       11. RAPPORT PERSONNALISÉ : RÉSERVATIONS
    ===================================================== */

    if ($typeRapport === "reservations") {

        $parametres = [];

        $condition = construireFiltreDate(
            $debut,
            $fin,
            $parametres,
            "res"
        );

        $sql = "
            SELECT
                r.reservation_id,
                r.reference,
                t.depart,
                t.destination,
                r.passagers,
                r.montant_total,
                r.statut,
                r.date_creation

            FROM reservations r

            LEFT JOIN trajets t
                ON r.trajet_id = t.trajet_id

            WHERE $condition

            ORDER BY r.date_creation DESC
        ";

        $requete = $pdo->prepare($sql);
        $requete->execute($parametres);

        $lignes = $requete->fetchAll(PDO::FETCH_NUM);

        repondreJSON([
            "success" => true,
            "rapport" => [
                "titre" => "Rapport des réservations",
                "colonnes" => [
                    "ID",
                    "Référence",
                    "Départ",
                    "Destination",
                    "Passagers",
                    "Montant (BIF)",
                    "Statut",
                    "Date de création"
                ],
                "lignes" => $lignes
            ]
        ]);
    }


    /* =====================================================
       12. RAPPORT PERSONNALISÉ : REVENUS
    ===================================================== */

    if ($typeRapport === "revenus") {

        $parametres = [];

        $condition = construireFiltreDate(
            $debut,
            $fin,
            $parametres,
            "rev"
        );

        $sql = "
            SELECT
                r.reference,
                t.depart,
                t.destination,
                r.montant_total,
                r.date_creation

            FROM reservations r

            LEFT JOIN trajets t
                ON r.trajet_id = t.trajet_id

            WHERE r.statut = 'confirmee'
              AND $condition

            ORDER BY r.date_creation DESC
        ";

        $requete = $pdo->prepare($sql);
        $requete->execute($parametres);

        $lignes = $requete->fetchAll(PDO::FETCH_NUM);

        repondreJSON([
            "success" => true,
            "rapport" => [
                "titre" => "Rapport des revenus confirmés",
                "colonnes" => [
                    "Référence",
                    "Départ",
                    "Destination",
                    "Montant (BIF)",
                    "Date"
                ],
                "lignes" => $lignes
            ]
        ]);
    }


    /* =====================================================
       13. RAPPORT PERSONNALISÉ : UTILISATEURS
    ===================================================== */

    if ($typeRapport === "utilisateurs") {

        /*
         * Ne jamais retourner les mots de passe ou leurs hash.
         * Les champs utilisés ici doivent exister dans users.
         */
        $sql = "
            SELECT
                user_id,
                nom,
                prenom,
                email
            FROM users
            ORDER BY user_id DESC
        ";

        $requete = $pdo->query($sql);
        $lignes = $requete->fetchAll(PDO::FETCH_NUM);

        repondreJSON([
            "success" => true,
            "rapport" => [
                "titre" => "Rapport des utilisateurs",
                "colonnes" => [
                    "ID",
                    "Nom",
                    "Prénom",
                    "Email"
                ],
                "lignes" => $lignes
            ]
        ]);
    }


    /* =====================================================
       14. RAPPORT COMPLET
    ===================================================== */

    if ($typeRapport === "complet") {

        $lignes = [
            ["Trajets enregistrés", $statistiques["totalTrajets"]],
            ["Réservations", $statistiques["totalReservations"]],
            [
                "Réservations confirmées",
                $statistiques["reservationsConfirmees"]
            ],
            [
                "Réservations en attente",
                $statistiques["reservationsEnAttente"]
            ],
            [
                "Réservations annulées",
                $statistiques["reservationsAnnulees"]
            ],
            [
                "Réservations expirées",
                $statistiques["reservationsExpirees"]
            ],
            [
                "Revenus confirmés (BIF)",
                $statistiques["totalRevenus"]
            ],
            ["Utilisateurs", $statistiques["totalUtilisateurs"]]
        ];

        repondreJSON([
            "success" => true,
            "rapport" => [
                "titre" => "Rapport complet de Burundi Transport",
                "colonnes" => [
                    "Indicateur",
                    "Valeur"
                ],
                "lignes" => $lignes
            ]
        ]);
    }


} catch (PDOException $e) {

    error_log("Erreur statistiques : " . $e->getMessage());

    repondreJSON([
        "success" => false,
        "erreur" => "Une erreur SQL est survenue. Vérifie les noms des colonnes et la structure de la base de données."
    ], 500);

} catch (Throwable $e) {

    error_log("Erreur rapport statistiques : " . $e->getMessage());

    repondreJSON([
        "success" => false,
        "erreur" => "Impossible de générer le rapport demandé."
    ], 500);
}
