
<?php

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Durée maximale d'inactivité pour les clients : 5 minutes
define("DELAI_INACTIVITE_CLIENT", 300);

// Vérifier la connexion et le rôle de l'utilisateur
function verifierAcces(
    string $roleAutorise,
    string $urlConnexion = "login.html"
): void {

    // 1. L'utilisateur doit être connecté
    if (empty($_SESSION["user_id"])) {
        header("Location: " . $urlConnexion);
        exit;
    }

    // 2. Récupérer le rôle de l'utilisateur
    $roleUtilisateur = strtolower(
        trim($_SESSION["role"] ?? "")
    );

    // 3. Vérifier le rôle autorisé
    if ($roleUtilisateur !== strtolower(trim($roleAutorise))) {
        http_response_code(403);
        exit(
            "Accès refusé : vous n'avez pas l'autorisation d'accéder à cette page."
        );
    }

    // 4. Appliquer la limite uniquement aux clients
    if ($roleUtilisateur === "client") {

        $maintenant = time();

        // Première visite protégée du client
        if (!isset($_SESSION["derniere_activite"])) {
            $_SESSION["derniere_activite"] = $maintenant;
        }

        $derniereActivite = (int) $_SESSION["derniere_activite"];

        // Vérifier si 5 minutes d'inactivité sont écoulées
        if (
            $maintenant - $derniereActivite
            >= DELAI_INACTIVITE_CLIENT
        ) {

            // Supprimer les données de session
            $_SESSION = [];

            // Supprimer le cookie de session si les cookies sont utilisés
            if (ini_get("session.use_cookies")) {
                $parametresCookie = session_get_cookie_params();

                setcookie(
                    session_name(),
                    "",
                    [
                        "expires" => time() - 42000,
                        "path" => $parametresCookie["path"],
                        "domain" => $parametresCookie["domain"],
                        "secure" => $parametresCookie["secure"],
                        "httponly" => $parametresCookie["httponly"],
                        "samesite" => $parametresCookie["samesite"] ?? "Lax"
                    ]
                );
            }

            // Détruire la session
            session_destroy();

            // Rediriger vers la connexion
            header("Location: " . $urlConnexion);
            exit;
        }

        // Actualiser l'heure de la dernière activité
        $_SESSION["derniere_activite"] = $maintenant;
    }

    // Les administrateurs ne sont pas soumis à cette limite.
}
