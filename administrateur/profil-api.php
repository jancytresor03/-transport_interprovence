
<?php

require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");

require_once __DIR__ . "/../database.php";

header("Content-Type: application/json; charset=UTF-8");

/*
==================================================
1. FONCTION POUR RENVOYER UNE RÉPONSE JSON
==================================================
*/

function repondre(int $code, array $donnees): void
{
    http_response_code($code);

    echo json_encode(
        $donnees,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

try {
    // Identifiant de l'administrateur connecté.
    $userId = $_SESSION["user_id"] ?? null;

    if (!$userId) {
        repondre(401, [
            "succes" => false,
            "message" => "Vous devez vous connecter."
        ]);
    }

    $action = $_GET["action"] ?? "lire";

    /*
    ==================================================
    2. LIRE LE PROFIL
    ==================================================
    */

    if ($action === "lire") {
        $sql = "
            SELECT
                user_id,
                nom,
                prenom,
                email,
                telephone,
                adresse,
                statut,
                deux_facteurs,
                date_creation,
                derniere_connexion
            FROM users
            WHERE user_id = :user_id
            LIMIT 1
        ";

        $requete = $pdo->prepare($sql);

        $requete->execute([
            "user_id" => $userId
        ]);

        $profil = $requete->fetch(PDO::FETCH_ASSOC);

        if (!$profil) {
            repondre(404, [
                "succes" => false,
                "message" => "Profil introuvable."
            ]);
        }

        repondre(200, [
            "succes" => true,
            "profil" => $profil
        ]);
    }

    /*
    ==================================================
    3. MODIFIER LES INFORMATIONS DU PROFIL
    ==================================================
    */

    if ($action === "modifier") {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            repondre(405, [
                "succes" => false,
                "message" => "Méthode non autorisée."
            ]);
        }

        $donnees = json_decode(
            file_get_contents("php://input"),
            true
        );

        if (!is_array($donnees)) {
            repondre(400, [
                "succes" => false,
                "message" => "Données invalides."
            ]);
        }

        $nom = trim($donnees["nom"] ?? "");
        $prenom = trim($donnees["prenom"] ?? "");
        $email = trim($donnees["email"] ?? "");
        $telephone = trim($donnees["telephone"] ?? "");
        $adresse = trim($donnees["adresse"] ?? "");

        if (
            $nom === "" ||
            $prenom === "" ||
            $email === "" ||
            $telephone === ""
        ) {
            repondre(400, [
                "succes" => false,
                "message" => "Le nom, le prénom, l'email et le téléphone sont obligatoires."
            ]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            repondre(400, [
                "succes" => false,
                "message" => "Adresse email invalide."
            ]);
        }

        // Vérifier que l'email n'est pas déjà utilisé.
        $requeteEmail = $pdo->prepare("
            SELECT user_id
            FROM users
            WHERE email = :email
              AND user_id != :user_id
            LIMIT 1
        ");

        $requeteEmail->execute([
            "email" => $email,
            "user_id" => $userId
        ]);

        if ($requeteEmail->fetch()) {
            repondre(409, [
                "succes" => false,
                "message" => "Cette adresse email est déjà utilisée."
            ]);
        }

        // Vérifier que le téléphone n'est pas déjà utilisé.
        $requeteTelephone = $pdo->prepare("
            SELECT user_id
            FROM users
            WHERE telephone = :telephone
              AND user_id != :user_id
            LIMIT 1
        ");

        $requeteTelephone->execute([
            "telephone" => $telephone,
            "user_id" => $userId
        ]);

        if ($requeteTelephone->fetch()) {
            repondre(409, [
                "succes" => false,
                "message" => "Ce numéro de téléphone est déjà utilisé."
            ]);
        }

        // Mettre à jour le profil.
        $requete = $pdo->prepare("
            UPDATE users
            SET
                nom = :nom,
                prenom = :prenom,
                email = :email,
                telephone = :telephone,
                adresse = :adresse
            WHERE user_id = :user_id
        ");

        $requete->execute([
            "nom" => $nom,
            "prenom" => $prenom,
            "email" => $email,
            "telephone" => $telephone,
            "adresse" => $adresse !== "" ? $adresse : null,
            "user_id" => $userId
        ]);

        repondre(200, [
            "succes" => true,
            "message" => "Profil mis à jour avec succès."
        ]);
    }

    /*
    ==================================================
    4. CHANGER LE MOT DE PASSE
    ==================================================
    */

    if ($action === "changer_mot_de_passe") {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            repondre(405, [
                "succes" => false,
                "message" => "Méthode non autorisée."
            ]);
        }

        $donnees = json_decode(
            file_get_contents("php://input"),
            true
        );

        if (!is_array($donnees)) {
            repondre(400, [
                "succes" => false,
                "message" => "Données invalides."
            ]);
        }

        $ancienMotDePasse =
            $donnees["ancienMotDePasse"] ?? null;

        $nouveauMotDePasse =
            $donnees["nouveauMotDePasse"] ?? null;

        $confirmation =
            $donnees["confirmation"] ?? null;

        if (
            !is_string($ancienMotDePasse) ||
            !is_string($nouveauMotDePasse) ||
            !is_string($confirmation) ||
            $ancienMotDePasse === "" ||
            $nouveauMotDePasse === "" ||
            $confirmation === ""
        ) {
            repondre(400, [
                "succes" => false,
                "message" => "Veuillez remplir tous les champs du mot de passe."
            ]);
        }

        if (strlen($nouveauMotDePasse) < 8) {
            repondre(400, [
                "succes" => false,
                "message" => "Le nouveau mot de passe doit contenir au moins 8 caractères."
            ]);
        }

        // Limite pratique pour les mots de passe bcrypt.
        if (strlen($nouveauMotDePasse) > 72) {
            repondre(400, [
                "succes" => false,
                "message" => "Le nouveau mot de passe est trop long."
            ]);
        }

        if ($nouveauMotDePasse !== $confirmation) {
            repondre(400, [
                "succes" => false,
                "message" => "Les nouveaux mots de passe ne correspondent pas."
            ]);
        }

        // Récupérer le mot de passe chiffré en base.
        $requete = $pdo->prepare("
            SELECT password
            FROM users
            WHERE user_id = :user_id
            LIMIT 1
        ");

        $requete->execute([
            "user_id" => $userId
        ]);

        $utilisateur = $requete->fetch(PDO::FETCH_ASSOC);

        if (!$utilisateur) {
            repondre(404, [
                "succes" => false,
                "message" => "Compte administrateur introuvable."
            ]);
        }

        if (!password_verify(
            $ancienMotDePasse,
            $utilisateur["password"]
        )) {
            repondre(400, [
                "succes" => false,
                "message" => "L'ancien mot de passe est incorrect."
            ]);
        }

        if (password_verify(
            $nouveauMotDePasse,
            $utilisateur["password"]
        )) {
            repondre(400, [
                "succes" => false,
                "message" => "Choisissez un mot de passe différent de l'ancien."
            ]);
        }

        $nouveauHash = password_hash(
            $nouveauMotDePasse,
            PASSWORD_DEFAULT
        );

        if ($nouveauHash === false) {
            throw new RuntimeException(
                "Impossible de sécuriser le nouveau mot de passe."
            );
        }

        $requete = $pdo->prepare("
            UPDATE users
            SET password = :password
            WHERE user_id = :user_id
        ");

        $requete->execute([
            "password" => $nouveauHash,
            "user_id" => $userId
        ]);

        repondre(200, [
            "succes" => true,
            "message" => "Votre mot de passe a été modifié avec succès."
        ]);
    }

    /*
    ==================================================
    5. ACTIVER OU DÉSACTIVER LA DOUBLE AUTHENTIFICATION
    ==================================================
    */

    if ($action === "changer_2fa") {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            repondre(405, [
                "succes" => false,
                "message" => "Méthode non autorisée."
            ]);
        }

        $donnees = json_decode(
            file_get_contents("php://input"),
            true
        );

        if (
            !is_array($donnees) ||
            !array_key_exists("activer", $donnees) ||
            !is_bool($donnees["activer"])
        ) {
            repondre(400, [
                "succes" => false,
                "message" => "État de la double authentification invalide."
            ]);
        }

        $activer = $donnees["activer"] ? 1 : 0;

        // Vérifier que le compte connecté existe toujours.
        $requete = $pdo->prepare("
            SELECT user_id
            FROM users
            WHERE user_id = :user_id
            LIMIT 1
        ");

        $requete->execute([
            "user_id" => $userId
        ]);

        if (!$requete->fetch(PDO::FETCH_ASSOC)) {
            repondre(404, [
                "succes" => false,
                "message" => "Compte administrateur introuvable."
            ]);
        }

        // Enregistrer le nouvel état dans MySQL.
        $pdo->beginTransaction();

        try {
            $miseAJour = $pdo->prepare("
                UPDATE users
                SET deux_facteurs = :deux_facteurs
                WHERE user_id = :user_id
            ");

            $miseAJour->execute([
                "deux_facteurs" => $activer,
                "user_id" => $userId
            ]);

            // En cas de désactivation, supprimer le code restant.
            if ($activer === 0) {
                $suppression = $pdo->prepare("
                    DELETE FROM codes_2fa
                    WHERE user_id = :user_id
                ");

                $suppression->execute([
                    "user_id" => $userId
                ]);
            }

            $pdo->commit();

        } catch (Throwable $erreur) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $erreur;
        }

        repondre(200, [
            "succes" => true,
            "deux_facteurs" => $activer,
            "message" => $activer === 1
                ? "La double authentification est activée. Un code sera envoyé par e-mail lors de votre prochaine connexion."
                : "La double authentification est désactivée."
        ]);
    }

    /*
    ==================================================
    6. ACTION INCONNUE
    ==================================================
    */

    repondre(400, [
        "succes" => false,
        "message" => "Action inconnue."
    ]);

} catch (PDOException $e) {
    error_log(
        "Erreur profil administrateur : " . $e->getMessage()
    );

    if ($e->getCode() === "23000") {
        repondre(409, [
            "succes" => false,
            "message" => "Cette information existe déjà."
        ]);
    }

    repondre(500, [
        "succes" => false,
        "message" => "Une erreur de base de données est survenue."
    ]);

} catch (Throwable $e) {
    error_log(
        "Erreur profil administrateur : " . $e->getMessage()
    );

    repondre(500, [
        "succes" => false,
        "message" => "Une erreur interne est survenue."
    ]);
}
