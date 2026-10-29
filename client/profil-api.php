
<?php

require_once __DIR__ . "/../verification.php";
verifierAcces("client", "../login.html");

require_once __DIR__ . "/../database.php";

header("Content-Type: application/json; charset=UTF-8");


// ================= RÉPONSE JSON =================

function repondre($succes, $message, $donnees = [])
{
    echo json_encode([
        "success" => $succes,
        "message" => $message,
        "data" => $donnees
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


// ================= VÉRIFIER LA SESSION =================

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    repondre(false, "Vous devez vous reconnecter.");
}

$userId = $_SESSION["user_id"];


// ================= VÉRIFIER LA MÉTHODE =================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Allow: POST");
    http_response_code(405);
    repondre(false, "Méthode non autorisée.");
}


// ================= LIRE LES DONNÉES =================

$contenu = json_decode(file_get_contents("php://input"), true);

if (!is_array($contenu)) {
    http_response_code(400);
    repondre(false, "Données invalides.");
}

// Si aucune action n'est précisée, on met à jour le profil.
$action = $contenu["action"] ?? "profil";


// ================= TRAITER LA DEMANDE =================

try {

    // Vérifier que le compte existe et est actif.
    $verification = $pdo->prepare("
        SELECT user_id, password
        FROM users
        WHERE user_id = :user_id
          AND statut = 'actif'
        LIMIT 1
    ");

    $verification->execute([
        "user_id" => $userId
    ]);

    $utilisateur = $verification->fetch(PDO::FETCH_ASSOC);

    if (!$utilisateur) {
        http_response_code(403);
        repondre(false, "Compte introuvable ou inactif.");
    }


    // ==================================================
    // ACTION 1 : MODIFICATION DU MOT DE PASSE
    // ==================================================

    if ($action === "mot_de_passe") {

        $ancienMotDePasse = $contenu["ancienMotDePasse"] ?? "";
        $nouveauMotDePasse = $contenu["nouveauMotDePasse"] ?? "";
        $confirmation = $contenu["confirmationMotDePasse"] ?? "";

        if (
            !is_string($ancienMotDePasse) ||
            !is_string($nouveauMotDePasse) ||
            !is_string($confirmation)
        ) {
            http_response_code(422);
            repondre(false, "Les données du mot de passe sont invalides.");
        }

        if (
            $ancienMotDePasse === "" ||
            $nouveauMotDePasse === "" ||
            $confirmation === ""
        ) {
            http_response_code(422);
            repondre(false, "Veuillez remplir tous les champs du mot de passe.");
        }

        // Vérifier l'ancien mot de passe.
        if (!password_verify(
            $ancienMotDePasse,
            $utilisateur["password"]
        )) {
            http_response_code(422);
            repondre(false, "L'ancien mot de passe est incorrect.");
        }

        // Exiger au moins 8 caractères.
        if (strlen($nouveauMotDePasse) < 8) {
            http_response_code(422);
            repondre(
                false,
                "Le nouveau mot de passe doit contenir au moins 8 caractères."
            );
        }

        // Limiter la taille pour éviter les entrées excessives.
        if (strlen($nouveauMotDePasse) > 72) {
            http_response_code(422);
            repondre(
                false,
                "Le nouveau mot de passe est trop long."
            );
        }

        if ($nouveauMotDePasse !== $confirmation) {
            http_response_code(422);
            repondre(
                false,
                "La confirmation du nouveau mot de passe ne correspond pas."
            );
        }

        if ($nouveauMotDePasse === $ancienMotDePasse) {
            http_response_code(422);
            repondre(
                false,
                "Le nouveau mot de passe doit être différent de l'ancien."
            );
        }

        // Hacher le nouveau mot de passe avant l'enregistrement.
        $nouveauHash = password_hash(
            $nouveauMotDePasse,
            PASSWORD_DEFAULT
        );

        if ($nouveauHash === false) {
            throw new RuntimeException(
                "Impossible de sécuriser le mot de passe."
            );
        }

        $miseAJour = $pdo->prepare("
            UPDATE users
            SET password = :password
            WHERE user_id = :user_id
              AND statut = 'actif'
        ");

        $miseAJour->execute([
            "password" => $nouveauHash,
            "user_id" => $userId
        ]);

        repondre(
            true,
            "Votre mot de passe a été modifié avec succès."
        );
    }


    // ==================================================
    // ACTION 2 : MODIFICATION DU PROFIL
    // ==================================================

    if ($action === "profil") {

        $nom = trim($contenu["nom"] ?? "");
        $prenom = trim($contenu["prenom"] ?? "");
        $telephone = trim($contenu["telephone"] ?? "");
        $email = trim($contenu["email"] ?? "");
        $adresse = trim($contenu["adresse"] ?? "");

        if (
            $nom === "" ||
            $prenom === "" ||
            $telephone === "" ||
            $email === ""
        ) {
            http_response_code(422);
            repondre(
                false,
                "Veuillez remplir tous les champs obligatoires."
            );
        }

        if (
            mb_strlen($nom) > 100 ||
            mb_strlen($prenom) > 100 ||
            mb_strlen($telephone) > 30 ||
            mb_strlen($email) > 150 ||
            mb_strlen($adresse) > 255
        ) {
            http_response_code(422);
            repondre(
                false,
                "Un ou plusieurs champs sont trop longs."
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            repondre(false, "L'adresse email est invalide.");
        }

        $sql = "
            UPDATE users
            SET nom = :nom,
                prenom = :prenom,
                telephone = :telephone,
                email = :email,
                adresse = :adresse
            WHERE user_id = :user_id
              AND statut = 'actif'
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            "nom" => $nom,
            "prenom" => $prenom,
            "telephone" => $telephone,
            "email" => $email,
            "adresse" => $adresse,
            "user_id" => $userId
        ]);

        repondre(true, "Votre profil a été mis à jour.", [
            "nom" => $nom,
            "prenom" => $prenom,
            "email" => $email,
            "telephone" => $telephone,
            "adresse" => $adresse
        ]);
    }


    // ==================================================
    // ACTION INCONNUE
    // ==================================================

    http_response_code(400);
    repondre(false, "Action inconnue.");

} catch (PDOException $e) {

    if ($e->getCode() === "23000") {
        http_response_code(409);

        repondre(
            false,
            "Cette adresse email ou ce numéro de téléphone est déjà utilisé."
        );
    }

    error_log("Erreur profil client : " . $e->getMessage());

    http_response_code(500);
    repondre(
        false,
        "Une erreur est survenue lors de l'opération."
    );

} catch (Throwable $e) {

    error_log("Erreur sécurité profil : " . $e->getMessage());

    http_response_code(500);
    repondre(
        false,
        "Une erreur interne est survenue."
    );
}
