
<?php

header("Content-Type: application/json; charset=UTF-8");

/**
 * Envoyer une réponse JSON.
 */
function repondre($success, $message, $code = 200)
{
    http_response_code($code);

    echo json_encode([
        "success" => $success,
        "message" => $message
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/**
 * Accepter uniquement les requêtes POST.
 */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    repondre(false, "Méthode non autorisée.", 405);
}

/**
 * Lire les données JSON.
 */
$donnees = json_decode(file_get_contents("php://input"), true);

if (!is_array($donnees)) {
    repondre(false, "Les données envoyées sont invalides.", 400);
}

$token = trim($donnees["token"] ?? "");
$password = $donnees["password"] ?? "";
$confirmPassword = $donnees["confirmPassword"] ?? "";

/**
 * Vérifier le jeton : bin2hex(random_bytes(32))
 * produit normalement un jeton de 64 caractères hexadécimaux.
 */
if (!preg_match('/\A[a-f0-9]{64}\z/i', $token)) {
    repondre(false, "Le lien est invalide. Demandez un nouveau lien.", 400);
}

if (!is_string($password) || !is_string($confirmPassword)) {
    repondre(false, "Les mots de passe sont invalides.", 400);
}

if (strlen($password) < 8) {
    repondre(
        false,
        "Le mot de passe doit contenir au moins 8 caractères.",
        400
    );
}

/*
 * Limite raisonnable pour éviter des mots de passe excessivement longs.
 * strlen() compte ici les octets, ce qui convient aussi à bcrypt.
 */
if (strlen($password) > 72) {
    repondre(
        false,
        "Le mot de passe ne doit pas dépasser 72 octets.",
        400
    );
}

if ($password !== $confirmPassword) {
    repondre(
        false,
        "Les deux mots de passe ne correspondent pas.",
        400
    );
}

try {
    require_once __DIR__ . "/database.php";

    $tokenHash = hash("sha256", $token);

    // Préparer le nouveau mot de passe sous forme de hachage.
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    if ($passwordHash === false) {
        throw new RuntimeException(
            "Impossible de sécuriser le mot de passe."
        );
    }

    /*
     * Une transaction et FOR UPDATE empêchent deux requêtes
     * simultanées d'utiliser le même jeton.
     */
    $pdo->beginTransaction();

    $requete = $pdo->prepare(
        "SELECT id, user_id
         FROM password_resets
         WHERE token_hash = :token_hash
           AND used_at IS NULL
           AND expires_at > NOW()
         LIMIT 1
         FOR UPDATE"
    );

    $requete->execute([
        "token_hash" => $tokenHash
    ]);

    $demande = $requete->fetch(PDO::FETCH_ASSOC);

    if (!$demande) {
        $pdo->rollBack();

        repondre(
            false,
            "Ce lien est invalide, expiré ou déjà utilisé. Demandez un nouveau lien.",
            400
        );
    }

    // Modifier le mot de passe du compte correspondant.
    $miseAJour = $pdo->prepare(
        "UPDATE users
         SET password = :password
         WHERE user_id = :user_id"
    );

    $miseAJour->execute([
        "password" => $passwordHash,
        "user_id" => $demande["user_id"]
    ]);

    if ($miseAJour->rowCount() !== 1) {
        throw new RuntimeException(
            "Le compte associé au jeton est introuvable."
        );
    }

    // Marquer le jeton comme utilisé.
    $utilisation = $pdo->prepare(
        "UPDATE password_resets
         SET used_at = NOW()
         WHERE id = :id
           AND used_at IS NULL"
    );

    $utilisation->execute([
        "id" => $demande["id"]
    ]);

    if ($utilisation->rowCount() !== 1) {
        throw new RuntimeException(
            "Impossible de valider l'utilisation du jeton."
        );
    }

    $pdo->commit();

    repondre(
        true,
        "Votre mot de passe a été modifié avec succès. Vous pouvez maintenant vous connecter."
    );

} catch (Throwable $e) {

    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        "Réinitialisation du mot de passe : " . $e->getMessage()
    );

    repondre(
        false,
        "Une erreur technique est survenue. Réessayez plus tard.",
        500
    );
}
