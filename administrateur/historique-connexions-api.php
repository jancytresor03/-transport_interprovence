
<?php

require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");

require_once __DIR__ . "/../database.php";

header("Content-Type: application/json; charset=UTF-8");

/* =========================
   REPONSE JSON
========================= */

function repondreJSON(
    bool $succes,
    string $message,
    array $donnees = [],
    int $codeHTTP = 200
): void {
    http_response_code($codeHTTP);

    echo json_encode(
        array_merge([
            "succes" => $succes,
            "message" => $message
        ], $donnees),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

/* =========================
   VERIFIER LA METHODE
========================= */

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    repondreJSON(
        false,
        "Méthode non autorisée.",
        [],
        405
    );
}

try {

    /* =========================
       RECUPERER LES PARAMETRES
    ========================= */

    $recherche = trim((string) ($_GET["recherche"] ?? ""));
    $statut = trim((string) ($_GET["statut"] ?? "tous"));

    $page = filter_var(
        $_GET["page"] ?? 1,
        FILTER_VALIDATE_INT
    );

    $limite = filter_var(
        $_GET["limite"] ?? 10,
        FILTER_VALIDATE_INT
    );

    $page = ($page !== false && $page > 0) ? $page : 1;

    // Limiter le nombre de lignes par page
    $limite = ($limite !== false && $limite > 0)
        ? min($limite, 50)
        : 10;

    $offset = ($page - 1) * $limite;

    /* =========================
       VERIFIER LE STATUT
    ========================= */

    $statutsAutorises = [
        "tous",
        "reussie",
        "echouee"
    ];

    if (!in_array($statut, $statutsAutorises, true)) {
        repondreJSON(
            false,
            "Le filtre de statut est invalide.",
            [],
            400
        );
    }

    /* =========================
       CONSTRUIRE LES FILTRES
    ========================= */

    $conditions = [];
    $parametres = [];

    if ($recherche !== "") {
        $conditions[] = "(
            h.email_saisi LIKE :recherche_email
            OR h.adresse_ip LIKE :recherche_ip
            OR u.nom LIKE :recherche_nom
            OR u.prenom LIKE :recherche_prenom
        )";

        $terme = "%" . $recherche . "%";

        $parametres["recherche_email"] = $terme;
        $parametres["recherche_ip"] = $terme;
        $parametres["recherche_nom"] = $terme;
        $parametres["recherche_prenom"] = $terme;
    }

    if ($statut !== "tous") {
        $conditions[] = "h.statut = :statut";
        $parametres["statut"] = $statut;
    }

    $where = "";

    if (!empty($conditions)) {
        $where = "WHERE " . implode(" AND ", $conditions);
    }

    /* =========================
       CALCULER LES STATISTIQUES
    ========================= */

    $requeteStats = $pdo->query("
        SELECT
            COUNT(*) AS total,
            COALESCE(
                SUM(statut = 'reussie'),
                0
            ) AS reussies,
            COALESCE(
                SUM(statut = 'echouee'),
                0
            ) AS echouees
        FROM historique_connexions
    ");

    $statistiques = $requeteStats->fetch(PDO::FETCH_ASSOC);

    /* =========================
       COMPTER LES RESULTATS FILTRES
    ========================= */

    $sqlCompte = "
        SELECT COUNT(*)
        FROM historique_connexions h
        LEFT JOIN users u ON h.user_id = u.user_id
        $where
    ";

    $requeteCompte = $pdo->prepare($sqlCompte);
    $requeteCompte->execute($parametres);

    $totalFiltres = (int) $requeteCompte->fetchColumn();

    $totalPages = max(
        1,
        (int) ceil($totalFiltres / $limite)
    );

    /* =========================
       RECUPERER LES CONNEXIONS
    ========================= */

    $sqlHistorique = "
        SELECT
            h.id,
            h.user_id,
            h.email_saisi,
            h.statut,
            h.adresse_ip,
            h.navigateur,
            h.date_connexion,
            u.nom,
            u.prenom
        FROM historique_connexions h
        LEFT JOIN users u ON h.user_id = u.user_id
        $where
        ORDER BY h.date_connexion DESC, h.id DESC
        LIMIT $limite OFFSET $offset
    ";

    $requeteHistorique = $pdo->prepare($sqlHistorique);
    $requeteHistorique->execute($parametres);

    $connexions = $requeteHistorique->fetchAll(PDO::FETCH_ASSOC);

    /* =========================
       FORMATER LES RESULTATS
    ========================= */

    foreach ($connexions as &$connexion) {
        $connexion["id"] = (int) $connexion["id"];

        $connexion["nom_complet"] = trim(
            ($connexion["prenom"] ?? "") . " "
            . ($connexion["nom"] ?? "")
        );

        if ($connexion["nom_complet"] === "") {
            $connexion["nom_complet"] = "Utilisateur inconnu";
        }
    }

    unset($connexion);

    /* =========================
       REPONSE FINALE
    ========================= */

    repondreJSON(
        true,
        "Historique des connexions récupéré avec succès.",
        [
            "statistiques" => [
                "total" => (int) $statistiques["total"],
                "reussies" => (int) $statistiques["reussies"],
                "echouees" => (int) $statistiques["echouees"]
            ],
            "pagination" => [
                "page" => $page,
                "limite" => $limite,
                "total_resultats" => $totalFiltres,
                "total_pages" => $totalPages
            ],
            "connexions" => $connexions
        ]
    );

} catch (Throwable $erreur) {

    error_log(
        "Erreur historique des connexions : "
        . $erreur->getMessage()
    );

    repondreJSON(
        false,
        "Impossible de récupérer l'historique des connexions.",
        [],
        500
    );
}
?>
