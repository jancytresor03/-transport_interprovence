
<?php

require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");

require_once __DIR__ . "/../database.php";

header("Content-Type: application/json; charset=UTF-8");

try {

    /* ==========================================
       1. STATISTIQUES GÉNÉRALES
    ========================================== */

    $sqlStatistiques = "
        SELECT
            (SELECT COUNT(*)
             FROM users) AS total_utilisateurs,

            (SELECT COUNT(*)
             FROM reservations
             WHERE YEAR(date_creation) = YEAR(CURDATE())
               AND MONTH(date_creation) = MONTH(CURDATE())
            ) AS reservations_mois,

            (SELECT COUNT(*)
             FROM trajets
             WHERE statut = 'actif'
               AND date_depart >= CURDATE()
            ) AS trajets_actifs,

            (SELECT COALESCE(SUM(montant_total), 0)
             FROM reservations
             WHERE statut = 'confirmee'
               AND YEAR(date_creation) = YEAR(CURDATE())
               AND MONTH(date_creation) = MONTH(CURDATE())
            ) AS revenus_mois,

            (SELECT COUNT(*)
             FROM reservations
             WHERE statut = 'en_attente'
            ) AS reservations_en_attente,

            (SELECT COUNT(*)
             FROM reservations
             WHERE statut = 'confirmee'
            ) AS reservations_confirmees
    ";

    $stmtStatistiques = $pdo->query($sqlStatistiques);

    $statistiques = $stmtStatistiques->fetch(PDO::FETCH_ASSOC);


    /* ==========================================
       2. RÉSERVATIONS RÉCENTES
    ========================================== */

    $sqlReservations = "
        SELECT
            r.reference,
            r.nom_passager,
            r.prenom_passager,
            r.montant_total,
            r.statut,
            r.date_creation,
            t.depart,
            t.destination

        FROM reservations r

        INNER JOIN trajets t
            ON r.trajet_id = t.trajet_id

        ORDER BY r.date_creation DESC

        LIMIT 5
    ";

    $stmtReservations = $pdo->query($sqlReservations);

    $reservations = $stmtReservations->fetchAll(PDO::FETCH_ASSOC);


    /* ==========================================
       3. PROCHAINS TRAJETS
    ========================================== */

    $sqlTrajets = "
        SELECT
            t.trajet_id,
            t.depart,
            t.destination,
            t.date_depart,
            t.heure_depart,
            t.nombre_places,

            GREATEST(
                t.nombre_places - COALESCE(SUM(
                    CASE
                        WHEN r.statut IN ('en_attente', 'confirmee')
                        THEN r.nombre_passagers
                        ELSE 0
                    END
                ), 0),
                0
            ) AS places_disponibles

        FROM trajets t

        LEFT JOIN reservations r
            ON t.trajet_id = r.trajet_id

        WHERE t.statut = 'actif'
          AND t.date_depart >= CURDATE()

        GROUP BY
            t.trajet_id,
            t.depart,
            t.destination,
            t.date_depart,
            t.heure_depart,
            t.nombre_places

        ORDER BY t.date_depart ASC, t.heure_depart ASC

        LIMIT 5
    ";

    $stmtTrajets = $pdo->query($sqlTrajets);

    $trajets = $stmtTrajets->fetchAll(PDO::FETCH_ASSOC);


    /* ==========================================
       4. ENVOYER LES DONNÉES AU JAVASCRIPT
    ========================================== */

    echo json_encode([
        "succes" => true,
        "statistiques" => $statistiques,
        "reservations" => $reservations,
        "trajets" => $trajets
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

} catch (Throwable $e) {

    error_log("Erreur Dashboard : " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "succes" => false,
        "message" => "Impossible de charger les données du Dashboard."
    ], JSON_UNESCAPED_UNICODE);

}


