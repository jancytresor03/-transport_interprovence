
<?php

require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");

require_once __DIR__ . "/../database.php";

header("Content-Type: application/json; charset=UTF-8");

// Autoriser uniquement les requêtes GET
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "succes" => false,
        "message" => "Méthode non autorisée."
    ]);

    exit;
}

try {

    // 1. Récupérer les filtres envoyés par JavaScript
    $recherche = trim($_GET["recherche"] ?? "");
    $type = trim($_GET["type"] ?? "");
    $statut = trim($_GET["statut"] ?? "");

    // 2. Construire la requête de recherche
    $conditions = [];
    $parametres = [];

    if ($recherche !== "") {
        $conditions[] = "(
            archive_id LIKE :recherche
            OR table_origine LIKE :recherche
            OR id_origine LIKE :recherche
            OR raison LIKE :recherche
        )";

        $parametres[":recherche"] = "%" . $recherche . "%";
    }

    // Types autorisés
    $typesAutorises = [
        "trajet",
        "reservation",
        "paiement",
        "document",
        "utilisateur",
        "autre"
    ];

    if ($type !== "" && in_array($type, $typesAutorises, true)) {
        $conditions[] = "type_donnee = :type";
        $parametres[":type"] = $type;
    }

    // Statuts autorisés
    $statutsAutorises = ["archive", "restauree"];

    if ($statut !== "" && in_array($statut, $statutsAutorises, true)) {
        $conditions[] = "statut = :statut";
        $parametres[":statut"] = $statut;
    }

    $where = "";

    if (!empty($conditions)) {
        $where = " WHERE " . implode(" AND ", $conditions);
    }

    // 3. Récupérer les archives filtrées
    $sql = "
        SELECT
            archive_id,
            user_id,
            type_donnee,
            table_origine,
            id_origine,
            donnees_archivees,
            raison,
            date_archivage,
            date_restauration,
            statut
        FROM archives
        $where
        ORDER BY date_archivage DESC
    ";

    $requete = $pdo->prepare($sql);
    $requete->execute($parametres);

    $archives = $requete->fetchAll(PDO::FETCH_ASSOC);

    // 4. Calculer les statistiques globales
    // Elles restent globales même lorsque les filtres sont utilisés.
    $sqlStats = "
        SELECT
            COUNT(*) AS total,
            COALESCE(SUM(statut = 'archive'), 0) AS actives,
            COALESCE(SUM(statut = 'restauree'), 0) AS restaurees
        FROM archives
    ";

    $requeteStats = $pdo->query($sqlStats);
    $stats = $requeteStats->fetch(PDO::FETCH_ASSOC);

    // 5. Préparer les données JSON
    echo json_encode([
        "succes" => true,
        "statistiques" => [
            "total" => (int) $stats["total"],
            "actives" => (int) $stats["actives"],
            "restaurees" => (int) $stats["restaurees"]
        ],
        "nombre_resultats" => count($archives),
        "archives" => $archives
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

} catch (PDOException $e) {

    error_log("Erreur archives-api.php : " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "succes" => false,
        "message" => "Une erreur est survenue lors de la récupération des archives."
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    error_log("Erreur archives-api.php : " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "succes" => false,
        "message" => "Une erreur interne est survenue."
    ], JSON_UNESCAPED_UNICODE);
}

