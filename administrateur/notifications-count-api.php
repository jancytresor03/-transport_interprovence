
<?php

require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");

require_once __DIR__ . "/../database.php";

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Méthode non autorisée."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    $sql = "SELECT COUNT(*)
            FROM notifications
            WHERE statut = 'non_lue'";

    $requete = $pdo->query($sql);
    $nombre = (int) $requete->fetchColumn();

    echo json_encode([
        "success" => true,
        "non_lues" => $nombre
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log("Erreur compteur notifications : " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Impossible de compter les notifications."
    ], JSON_UNESCAPED_UNICODE);
}
?>
