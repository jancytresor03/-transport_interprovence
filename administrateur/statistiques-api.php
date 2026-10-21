
<?php

require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");

require_once __DIR__ . "/../database.php";

header("Content-Type: application/json; charset=UTF-8");

try {

    // 1. Statistiques des réservations
    $sqlReservations = "
        SELECT
            COUNT(*) AS total_reservations,

            SUM(
                CASE
                    WHEN statut = 'confirmee'
                    THEN 1 ELSE 0
                END
            ) AS reservations_confirmees,

            SUM(
                CASE
                    WHEN statut = 'en_attente'
                    THEN 1 ELSE 0
                END
            ) AS reservations_attente,

            SUM(
                CASE
                    WHEN statut = 'annulee'
                    THEN 1 ELSE 0
                END
            ) AS reservations_annulees,

            SUM(
                CASE
                    WHEN statut = 'expiree'
                    THEN 1 ELSE 0
                END
            ) AS reservations_expirees,

            SUM(
                CASE
                    WHEN statut = 'confirmee'
                    THEN montant_total ELSE 0
                END
            ) AS total_revenus

        FROM reservations
    ";

    $requete = $pdo->query($sqlReservations);
    $reservations = $requete->fetch(PDO::FETCH_ASSOC);


    // 2. Nombre total d'utilisateurs
    $sqlUtilisateurs = "
        SELECT COUNT(*) AS total_utilisateurs
        FROM users
    ";

    $requete = $pdo->query($sqlUtilisateurs);
    $utilisateurs = $requete->fetch(PDO::FETCH_ASSOC);


    // 3. Nombre total de trajets
    $sqlTrajets = "
        SELECT COUNT(*) AS total_trajets
        FROM trajets
    ";

    $requete = $pdo->query($sqlTrajets);
    $trajets = $requete->fetch(PDO::FETCH_ASSOC);


    // 4. Réservations par mois de l'année en cours
    $sqlParMois = "
        SELECT
            MONTH(date_creation) AS mois,
            COUNT(*) AS total
        FROM reservations
        WHERE YEAR(date_creation) = YEAR(CURDATE())
        GROUP BY MONTH(date_creation)
        ORDER BY mois
    ";

    $requete = $pdo->query($sqlParMois);
    $reservationsParMois = $requete->fetchAll(PDO::FETCH_ASSOC);


    // 5. Réservations par destination
    $sqlDestinations = "
        SELECT
            t.destination,
            COUNT(r.reservation_id) AS total
        FROM trajets t
        LEFT JOIN reservations r
            ON t.trajet_id = r.trajet_id
        GROUP BY t.destination
        ORDER BY total DESC
    ";

    $requete = $pdo->query($sqlDestinations);
    $destinations = $requete->fetchAll(PDO::FETCH_ASSOC);


    // 6. Revenus confirmés par mois
    $sqlRevenus = "
        SELECT
            MONTH(date_creation) AS mois,
            SUM(montant_total) AS total
        FROM reservations
        WHERE statut = 'confirmee'
          AND YEAR(date_creation) = YEAR(CURDATE())
        GROUP BY MONTH(date_creation)
        ORDER BY mois
    ";

    $requete = $pdo->query($sqlRevenus);
    $revenusParMois = $requete->fetchAll(PDO::FETCH_ASSOC);


    // 7. Préparer les données pour JavaScript
    echo json_encode([
        "statistiques" => [
            "totalReservations" =>
                (int) $reservations["total_reservations"],

            "reservationsConfirmees" =>
                (int) $reservations["reservations_confirmees"],

            "reservationsAttente" =>
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
        ],

        "reservationsParMois" => $reservationsParMois,

        "destinations" => $destinations,

        "revenusParMois" => $revenusParMois

    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    error_log("Erreur statistiques : " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "erreur" => "Impossible de charger les statistiques."
    ], JSON_UNESCAPED_UNICODE);
}
?>
