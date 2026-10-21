
<?php
session_start();

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/database.php";
require_once __DIR__ . "/envoyer-code-2fa.php";

function repondre(
    $succes,
    $message,
    $code = 200,
    $role = null,
    $verificationRequise = false
) {
    http_response_code($code);

    echo json_encode([
        "succes" => $succes,
        "message" => $message,
        "role" => $role,
        "verification_requise" => $verificationRequise
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Vérifier la méthode d'envoi
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    repondre(false, "Méthode non autorisée.", 405);
}

// Récupérer les données du formulaire
$email = strtolower(trim($_POST["email"] ?? ""));
$motDePasse = $_POST["password"] ?? "";

// Vérifier les champs
if ($email === "" || $motDePasse === "") {
    repondre(false, "Veuillez remplir tous les champs.", 400);
}

try {
    // Rechercher l'utilisateur et son rôle
    $sql = "SELECT
                u.user_id,
                u.nom,
                u.prenom,
                u.email,
                u.password,
                u.role_id,
                u.statut,
                u.deux_facteurs,
                r.nom AS role_nom
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.role_id
            WHERE u.email = :email
            LIMIT 1";

    $requete = $pdo->prepare($sql);
    $requete->execute(["email" => $email]);

    $utilisateur = $requete->fetch(PDO::FETCH_ASSOC);

    // Vérifier l'adresse et le mot de passe
    if (
        !$utilisateur ||
        !password_verify($motDePasse, $utilisateur["password"])
    ) {
        repondre(false, "Email ou mot de passe incorrect.", 401);
    }

    // Vérifier si le compte est actif
    if ($utilisateur["statut"] !== "actif") {
        repondre(
            false,
            "Votre compte est bloqué ou inactif. Contactez l'administrateur.",
            403
        );
    }

    // Vérifier le rôle
    $role = strtolower(trim($utilisateur["role_nom"] ?? ""));

    if (!in_array($role, ["admin", "client"], true)) {
        repondre(false, "Le rôle de ce compte est invalide.", 403);
    }

    // Retirer une éventuelle ancienne vérification en attente
    unset(
        $_SESSION["pending_2fa_user_id"],
        $_SESSION["pending_2fa_started"]
    );

    /*
     * ÉTAPE 1 : DOUBLE AUTHENTIFICATION
     */
    if ((int) $utilisateur["deux_facteurs"] === 1) {

        // Générer un code à 6 chiffres
        $code = (string) random_int(100000, 999999);

        // Enregistrer uniquement le hachage du code
        $codeHash = hash("sha256", $code);

        // Enregistrer le code et son expiration (5 minutes)
        $sqlCode = "
            INSERT INTO codes_2fa
                (user_id, code_hash, expires_at, tentatives, date_creation)
            VALUES
                (:user_id, :code_hash, DATE_ADD(NOW(), INTERVAL 5 MINUTE), 0, NOW())
            ON DUPLICATE KEY UPDATE
                code_hash = VALUES(code_hash),
                expires_at = VALUES(expires_at),
                tentatives = 0,
                date_creation = NOW()
        ";

        $requeteCode = $pdo->prepare($sqlCode);

        $requeteCode->execute([
            "user_id" => $utilisateur["user_id"],
            "code_hash" => $codeHash
        ]);

        // Envoyer le code à l'adresse de l'utilisateur
        if (!envoyerCode2FA($utilisateur["email"], $code)) {

            // Supprimer le code si l'envoi échoue
            $suppression = $pdo->prepare(
                "DELETE FROM codes_2fa WHERE user_id = :user_id"
            );

            $suppression->execute([
                "user_id" => $utilisateur["user_id"]
            ]);

            repondre(
                false,
                "Impossible d'envoyer le code. Réessayez plus tard.",
                500
            );
        }

        // Créer une session temporaire, non authentifiée
        session_regenerate_id(true);

        $_SESSION["pending_2fa_user_id"] =
            $utilisateur["user_id"];

        $_SESSION["pending_2fa_started"] = time();

        // Aucune session utilisateur authentifiée n'est créée ici
        repondre(
            true,
            "Un code de vérification a été envoyé par e-mail.",
            200,
            null,
            true
        );
    }

    /*
     * ÉTAPE 2 : CONNEXION NORMALE
     * Pour les comptes sans double authentification
     */

    session_regenerate_id(true);

    $_SESSION["user_id"] = $utilisateur["user_id"];
    $_SESSION["nom"] = $utilisateur["nom"];
    $_SESSION["prenom"] = $utilisateur["prenom"];
    $_SESSION["email"] = $utilisateur["email"];
    $_SESSION["role_id"] = $utilisateur["role_id"];
    $_SESSION["role"] = $role;

    // Mettre à jour la date de dernière connexion
    $miseAJour = $pdo->prepare(
        "UPDATE users
         SET derniere_connexion = NOW()
         WHERE user_id = :user_id"
    );

    $miseAJour->execute([
        "user_id" => $utilisateur["user_id"]
    ]);

    repondre(true, "Connexion réussie.", 200, $role);

} catch (Throwable $erreur) {
    error_log("Erreur de connexion : " . $erreur->getMessage());

    repondre(
        false,
        "Une erreur technique est survenue. Réessayez plus tard.",
        500
    );
}
?>
