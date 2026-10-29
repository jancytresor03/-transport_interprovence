
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json; charset=UTF-8");

const DELAI_INACTIVITE_CLIENT = 300;

// Seules les requêtes POST sont acceptées.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "succes" => false,
        "message" => "Méthode non autorisée."
    ]);

    exit;
}

// Vérifier que l'utilisateur est connecté.
if (empty($_SESSION["user_id"])) {
    http_response_code(401);

    echo json_encode([
        "succes" => false,
        "expiree" => true,
        "message" => "Session absente ou expirée."
    ]);

    exit;
}

// Ce fichier est réservé aux clients.
$roleUtilisateur = strtolower(
    trim($_SESSION["role"] ?? "")
);

if ($roleUtilisateur !== "client") {
    http_response_code(403);

    echo json_encode([
        "succes" => false,
        "message" => "Accès réservé aux clients."
    ]);

    exit;
}

$maintenant = time();

$derniereActivite = isset($_SESSION["derniere_activite"])
    ? (int) $_SESSION["derniere_activite"]
    : 0;

// Vérifier l'expiration AVANT de renouveler l'activité.
if (
    $derniereActivite === 0 ||
    ($maintenant - $derniereActivite) >= DELAI_INACTIVITE_CLIENT
) {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $cookie = session_get_cookie_params();

        setcookie(session_name(), "", [
            "expires" => time() - 42000,
            "path" => $cookie["path"],
            "domain" => $cookie["domain"],
            "secure" => $cookie["secure"],
            "httponly" => $cookie["httponly"],
            "samesite" => $cookie["samesite"] ?? "Lax"
        ]);
    }

    session_destroy();

    http_response_code(401);

    echo json_encode([
        "succes" => false,
        "expiree" => true,
        "message" => "Session expirée après inactivité."
    ]);

    exit;
}

$action = $_POST["action"] ?? "";

// Enregistrer l'activité du client.
if ($action === "activite") {
    $_SESSION["derniere_activite"] = $maintenant;

    echo json_encode([
        "succes" => true,
        "message" => "Activité enregistrée."
    ]);

    exit;
}

// Déconnexion demandée par le compteur JavaScript.
if ($action === "deconnexion") {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $cookie = session_get_cookie_params();

        setcookie(session_name(), "", [
            "expires" => time() - 42000,
            "path" => $cookie["path"],
            "domain" => $cookie["domain"],
            "secure" => $cookie["secure"],
            "httponly" => $cookie["httponly"],
            "samesite" => $cookie["samesite"] ?? "Lax"
        ]);
    }

    session_destroy();

    echo json_encode([
        "succes" => true,
        "message" => "Déconnexion effectuée."
    ]);

    exit;
}

http_response_code(400);

echo json_encode([
    "succes" => false,
    "message" => "Action inconnue."
]);

