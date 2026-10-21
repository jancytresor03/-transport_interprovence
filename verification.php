
<?php

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier la connexion et le rôle de l'utilisateur
function verifierAcces(
    string $roleAutorise,
    string $urlConnexion = "login.html"
): void {

    // L'utilisateur doit être connecté
    if (empty($_SESSION["user_id"])) {
        header("Location: " . $urlConnexion);
        exit;
    }

    // Le rôle doit correspondre à la page demandée
    $roleUtilisateur = strtolower($_SESSION["role"] ?? "");

    if ($roleUtilisateur !== strtolower($roleAutorise)) {
        http_response_code(403);
        exit("Accès refusé : vous n'avez pas l'autorisation d'accéder à cette page.");
    }
}
