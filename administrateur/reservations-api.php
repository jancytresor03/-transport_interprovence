
<?php

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate");

require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");

require_once __DIR__ . "/../database.php";

/* =========================================
   RÉPONSE JSON
   Le format reservations est conservé
   pour rester compatible avec reservations.js.
========================================= */

function repondreReservations(
    bool $succes,
    string $message = "",
    $reservations = null,
    int $code = 200
): void {
    http_response_code($code);

    echo json_encode([
        "succes" => $succes,
        "message" => $message,
        "reservations" => $reservations
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/* =========================================
   CRÉER UNE NOTIFICATION POUR LE CLIENT
========================================= */

function ajouterNotificationReservation(
    PDO $pdo,
    string $clientId,
    string $titre,
    string $message
): void {

    $sql = "
        INSERT INTO notifications (
            notification_id,
            user_id,
            type_notification,
            titre,
            message,
            statut
        )
        VALUES (
            '',
            :user_id,
            'reservation',
            :titre,
            :message,
            'non_lue'
        )
    ";

    $requete = $pdo->prepare($sql);

    $requete->execute([
        ":user_id" => $clientId,
        ":titre" => $titre,
        ":message" => $message
    ]);
}

/* =========================================
   TRAITEMENT DE L'API
========================================= */

try {

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    $methode = $_SERVER["REQUEST_METHOD"];

    /* =====================================
       1. LISTER LES RÉSERVATIONS
    ===================================== */

    if ($methode === "GET") {

        $sql = "
            SELECT
                r.reservation_id AS id,
                r.reference,
                r.user_id,
                r.trajet_id,

                r.nom_passager AS nom,
                r.prenom_passager AS prenom,
                r.telephone_passager AS telephone,
                r.email_passager AS email,

                r.type_identite AS identite,
                r.numero_identite,
                r.nombre_passagers AS passagers,
                r.montant_total AS total,
                r.statut,

                r.remarque,
                r.date_creation AS date_reservation,

                t.depart,
                t.destination,
                t.date_depart AS date_voyage,
                t.heure_depart AS heure,
                t.prix AS prix_unitaire

            FROM reservations r

            INNER JOIN trajets t
                ON r.trajet_id = t.trajet_id

            ORDER BY r.date_creation DESC
        ";

        $requete = $pdo->query($sql);

        $reservations = $requete->fetchAll(
            PDO::FETCH_ASSOC
        );

        repondreReservations(
            true,
            "Réservations chargées.",
            $reservations
        );
    }

    /* =====================================
       2. AUTORISER UNIQUEMENT POST
    ===================================== */

    if ($methode !== "POST") {
        repondreReservations(
            false,
            "Méthode non autorisée.",
            null,
            405
        );
    }

    /* =====================================
       3. LIRE LES DONNÉES REÇUES
    ===================================== */

    $contenu = file_get_contents("php://input");
    $donnees = json_decode($contenu, true);

    if (!is_array($donnees)) {
        repondreReservations(
            false,
            "Les données envoyées sont invalides.",
            null,
            400
        );
    }

    $id = trim((string) ($donnees["id"] ?? ""));
    $statut = $donnees["statut"] ?? "";

    $statutsAutorises = [
        "confirmee",
        "annulee"
    ];

    if (
        $id === "" ||
        !is_string($statut) ||
        !in_array($statut, $statutsAutorises, true)
    ) {
        repondreReservations(
            false,
            "Identifiant ou statut invalide.",
            null,
            400
        );
    }

    /* =====================================
       4. IDENTIFIER L'ADMINISTRATEUR
    ===================================== */

    $adminId = (string) ($_SESSION["user_id"] ?? "");

    if ($adminId === "") {
        repondreReservations(
            false,
            "Session administrateur invalide.",
            null,
            401
        );
    }

    // Transmettre l'administrateur aux déclencheurs SQL.
    $requeteUser = $pdo->prepare(
        "SET @user_id_connecte = ?"
    );

    $requeteUser->execute([$adminId]);

    /* =====================================
       5. DÉMARRER LA TRANSACTION
    ===================================== */

    $pdo->beginTransaction();

    /* =====================================
       6. RÉCUPÉRER LA RÉSERVATION
    ===================================== */

    $verification = $pdo->prepare("
        SELECT
            reservation_id,
            reference,
            user_id,
            statut
        FROM reservations
        WHERE reservation_id = ?
        FOR UPDATE
    ");

    $verification->execute([$id]);

    $reservation = $verification->fetch(
        PDO::FETCH_ASSOC
    );

    if (!$reservation) {
        $pdo->rollBack();

        repondreReservations(
            false,
            "Réservation introuvable.",
            null,
            404
        );
    }

    /* =====================================
       7. VÉRIFIER LE STATUT ACTUEL
    ===================================== */

    if ($reservation["statut"] === "annulee") {
        $pdo->rollBack();

        repondreReservations(
            false,
            "Cette réservation est déjà annulée.",
            null,
            400
        );
    }

    if (
        $statut === "confirmee" &&
        $reservation["statut"] !== "en_attente"
    ) {
        $pdo->rollBack();

        repondreReservations(
            false,
            "Seules les réservations en attente peuvent être confirmées.",
            null,
            400
        );
    }

    /* =====================================
       8. IDENTIFIER LE CLIENT
    ===================================== */

    $clientId = (string) ($reservation["user_id"] ?? "");

    if ($clientId === "") {
        $pdo->rollBack();

        repondreReservations(
            false,
            "Impossible d'identifier le client.",
            null,
            500
        );
    }

    /* =====================================
       9. MODIFIER LE STATUT
    ===================================== */

    $miseAJour = $pdo->prepare("
        UPDATE reservations
        SET statut = ?
        WHERE reservation_id = ?
    ");

    $miseAJour->execute([
        $statut,
        $id
    ]);

    /* =====================================
       10. PRÉPARER LA NOTIFICATION
    ===================================== */

    if ($statut === "confirmee") {

        $titre = "Réservation confirmée";

        $message =
            "Votre réservation "
            . $reservation["reference"]
            . " a été confirmée. Vous pouvez consulter les détails de votre voyage.";

    } else {

        $titre = "Réservation annulée";

        $message =
            "Votre réservation "
            . $reservation["reference"]
            . " a été annulée. Pour toute question, contactez notre service client.";
    }

    /* =====================================
       11. ENREGISTRER LA NOTIFICATION
    ===================================== */

    ajouterNotificationReservation(
        $pdo,
        $clientId,
        $titre,
        $message
    );

    /* =====================================
       12. VALIDER LES CHANGEMENTS
    ===================================== */

    $pdo->commit();

    repondreReservations(
        true,
        $statut === "confirmee"
            ? "Réservation confirmée et client notifié."
            : "Réservation annulée et client notifié."
    );

} catch (Throwable $erreur) {

    if (
        isset($pdo) &&
        $pdo instanceof PDO &&
        $pdo->inTransaction()
    ) {
        $pdo->rollBack();
    }

    error_log(
        "Erreur reservations-api.php : "
        . $erreur->getMessage()
    );

    repondreReservations(
        false,
        "Une erreur est survenue lors du traitement de la réservation.",
        null,
        500
    );
}
