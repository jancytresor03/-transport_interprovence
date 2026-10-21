
<?php

// 1. Vérifier que l'administrateur est connecté
require_once __DIR__ . "/../verification.php";

verifierAcces("admin", "../login.html");

// 2. Connexion à la base de données
require_once __DIR__ . "/../database.php";

// 3. Indiquer que la réponse sera au format JSON
header("Content-Type: application/json; charset=UTF-8");

// 4. Autoriser uniquement la méthode GET
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Méthode non autorisée."
    ]);

    exit;
}

try {

    // 5. Lire les notifications directement depuis MySQL
    $sql = "SELECT
                notification_id,
                user_id,
                type_notification,
                titre,
                message,
                statut,
                date_lecture,
                date_creation
            FROM notifications
            ORDER BY date_creation DESC";

    $requete = $pdo->prepare($sql);
    $requete->execute();

    $notifications = $requete->fetchAll(PDO::FETCH_ASSOC);

    // 6. Envoyer les données au JavaScript
    echo json_encode([
        "success" => true,
        "total" => count($notifications),
        "notifications" => $notifications
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    // Enregistrer l'erreur dans le journal PHP
    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Erreur lors du chargement des notifications."
    ], JSON_UNESCAPED_UNICODE);
}
