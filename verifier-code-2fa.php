
<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/database.php";

/* =========================
   REPONSE JSON
========================= */

function repondre(
    bool $succes,
    string $message,
    int $codeHTTP = 200,
    ?string $role = null
): void {
    http_response_code($codeHTTP);

    echo json_encode([
        "succes" => $succes,
        "message" => $message,
        "role" => $role
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/* =========================
   VERIFIER LA METHODE
========================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    repondre(false, "Méthode non autorisée.", 405);
}

/* =========================
   VERIFIER LA SESSION TEMPORAIRE
========================= */

$userId = $_SESSION["pending_2fa_user_id"] ?? null;
$debutVerification = $_SESSION["pending_2fa_started"] ?? null;

if (
    !is_scalar($userId) ||
    trim((string) $userId) === "" ||
    !is_numeric($debutVerification) ||
    (int) $debutVerification > time() ||
    (time() - (int) $debutVerification) > 300
) {
    unset(
        $_SESSION["pending_2fa_user_id"],
        $_SESSION["pending_2fa_started"]
    );

    repondre(
        false,
        "Votre session de vérification a expiré. Reconnectez-vous.",
        401
    );
}

$userId = trim((string) $userId);

/* =========================
   VERIFIER LE FORMAT DU CODE
========================= */

$codeRecu = $_POST["code"] ?? "";

if (
    !is_string($codeRecu) ||
    !preg_match('/^[0-9]{6}$/', $codeRecu)
) {
    repondre(
        false,
        "Veuillez saisir un code valide de 6 chiffres.",
        400
    );
}

$code = $codeRecu;

try {

    /* =========================
       DEBUT DE LA TRANSACTION
    ========================= */

    $pdo->beginTransaction();

    /* =========================
       RECUPERER ET VERROUILLER LE CODE
    ========================= */

    $requete = $pdo->prepare("
        SELECT
            code_hash,
            expires_at,
            tentatives,
            (expires_at <= NOW()) AS code_expire
        FROM codes_2fa
        WHERE user_id = :user_id
        FOR UPDATE
    ");

    $requete->execute([
        "user_id" => $userId
    ]);

    $verification = $requete->fetch(PDO::FETCH_ASSOC);

    if (!$verification) {

        $pdo->commit();

        unset(
            $_SESSION["pending_2fa_user_id"],
            $_SESSION["pending_2fa_started"]
        );

        repondre(
            false,
            "Aucun code actif. Recommencez la connexion.",
            401
        );
    }

    /* =========================
       VERIFIER L'EXPIRATION DU CODE
    ========================= */

    if ((int) $verification["code_expire"] === 1) {

        $suppression = $pdo->prepare("
            DELETE FROM codes_2fa
            WHERE user_id = :user_id
        ");

        $suppression->execute([
            "user_id" => $userId
        ]);

        $pdo->commit();

        unset(
            $_SESSION["pending_2fa_user_id"],
            $_SESSION["pending_2fa_started"]
        );

        repondre(
            false,
            "Le code a expiré. Reconnectez-vous pour en recevoir un nouveau.",
            401
        );
    }

    /* =========================
       LIMITER LES TENTATIVES
    ========================= */

    $tentatives = (int) $verification["tentatives"];

    if ($tentatives >= 5) {

        $suppression = $pdo->prepare("
            DELETE FROM codes_2fa
            WHERE user_id = :user_id
        ");

        $suppression->execute([
            "user_id" => $userId
        ]);

        $pdo->commit();

        unset(
            $_SESSION["pending_2fa_user_id"],
            $_SESSION["pending_2fa_started"]
        );

        repondre(
            false,
            "Nombre maximal de tentatives atteint. Reconnectez-vous.",
            429
        );
    }

    /* =========================
       COMPARER LE CODE
    ========================= */

    $codeHash = hash("sha256", $code);

    if (!hash_equals($verification["code_hash"], $codeHash)) {

        $tentatives++;

        if ($tentatives >= 5) {

            $suppression = $pdo->prepare("
                DELETE FROM codes_2fa
                WHERE user_id = :user_id
            ");

            $suppression->execute([
                "user_id" => $userId
            ]);

            $pdo->commit();

            unset(
                $_SESSION["pending_2fa_user_id"],
                $_SESSION["pending_2fa_started"]
            );

            repondre(
                false,
                "Code incorrect. Nombre maximal de tentatives atteint. Reconnectez-vous.",
                429
            );
        }

        $miseAJourTentatives = $pdo->prepare("
            UPDATE codes_2fa
            SET tentatives = :tentatives
            WHERE user_id = :user_id
        ");

        $miseAJourTentatives->execute([
            "tentatives" => $tentatives,
            "user_id" => $userId
        ]);

        $pdo->commit();

        repondre(
            false,
            "Code incorrect. Il vous reste "
                . (5 - $tentatives)
                . " tentative(s).",
            400
        );
    }

    /* =========================
       REVERIFIER LE COMPTE
    ========================= */

    $requeteUtilisateur = $pdo->prepare("
        SELECT
            u.user_id,
            u.nom,
            u.prenom,
            u.email,
            u.role_id,
            u.statut,
            u.deux_facteurs,
            r.nom AS role_nom
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.role_id
        WHERE u.user_id = :user_id
        LIMIT 1
        FOR UPDATE
    ");

    $requeteUtilisateur->execute([
        "user_id" => $userId
    ]);

    $utilisateur = $requeteUtilisateur->fetch(PDO::FETCH_ASSOC);

    if (
        !$utilisateur ||
        $utilisateur["statut"] !== "actif"
    ) {

        $suppression = $pdo->prepare("
            DELETE FROM codes_2fa
            WHERE user_id = :user_id
        ");

        $suppression->execute([
            "user_id" => $userId
        ]);

        $pdo->commit();

        unset(
            $_SESSION["pending_2fa_user_id"],
            $_SESSION["pending_2fa_started"]
        );

        repondre(
            false,
            "Votre compte est inactif ou indisponible.",
            403
        );
    }

    /* =========================
       VERIFIER LE ROLE
    ========================= */

    $role = strtolower(
        trim($utilisateur["role_nom"] ?? "")
    );

    if (!in_array($role, ["admin", "client"], true)) {

        $suppression = $pdo->prepare("
            DELETE FROM codes_2fa
            WHERE user_id = :user_id
        ");

        $suppression->execute([
            "user_id" => $userId
        ]);

        $pdo->commit();

        unset(
            $_SESSION["pending_2fa_user_id"],
            $_SESSION["pending_2fa_started"]
        );

        repondre(
            false,
            "Le rôle de ce compte est invalide.",
            403
        );
    }

    /* =========================
       FORCER LA 2FA POUR LE CLIENT
    ========================= */

    if (
        $role === "client" &&
        (int) $utilisateur["deux_facteurs"] !== 1
    ) {

        $forcer2FA = $pdo->prepare("
            UPDATE users
            SET deux_facteurs = 1
            WHERE user_id = :user_id
        ");

        $forcer2FA->execute([
            "user_id" => $userId
        ]);
    }

    /*
       Pour l'administrateur, conserver le réglage
       deux_facteurs déjà enregistré.
    */

    /* =========================
       SUPPRIMER LE CODE UTILISE
    ========================= */

    $suppression = $pdo->prepare("
        DELETE FROM codes_2fa
        WHERE user_id = :user_id
    ");

    $suppression->execute([
        "user_id" => $userId
    ]);

    /* =========================
       METTRE A JOUR LA DERNIERE CONNEXION
    ========================= */

    $miseAJourConnexion = $pdo->prepare("
        UPDATE users
        SET derniere_connexion = NOW()
        WHERE user_id = :user_id
    ");

    $miseAJourConnexion->execute([
        "user_id" => $userId
    ]);

    /* =========================
       ENREGISTRER L'HISTORIQUE
    ========================= */

    $adresseIP = $_SERVER["REMOTE_ADDR"] ?? null;
    $navigateur = $_SERVER["HTTP_USER_AGENT"] ?? null;

    $historique = $pdo->prepare("
        INSERT INTO historique_connexions (
            user_id,
            email_saisi,
            statut,
            adresse_ip,
            navigateur,
            date_connexion
        ) VALUES (
            :user_id,
            :email,
            'reussie',
            :adresse_ip,
            :navigateur,
            NOW()
        )
    ");

    $historique->execute([
        "user_id" => $utilisateur["user_id"],
        "email" => $utilisateur["email"],
        "adresse_ip" => $adresseIP,
        "navigateur" => $navigateur
    ]);

    /* =========================
       VALIDER LES OPERATIONS SQL
    ========================= */

    $pdo->commit();

    /* =========================
       CREER LA SESSION AUTHENTIFIEE
    ========================= */

    session_regenerate_id(true);

    unset(
        $_SESSION["pending_2fa_user_id"],
        $_SESSION["pending_2fa_started"]
    );

    $_SESSION["user_id"] = $utilisateur["user_id"];
    $_SESSION["nom"] = $utilisateur["nom"];
    $_SESSION["prenom"] = $utilisateur["prenom"];
    $_SESSION["email"] = $utilisateur["email"];
    $_SESSION["role_id"] = $utilisateur["role_id"];
    $_SESSION["role"] = $role;

    /* =========================
       CONNEXION REUSSIE
    ========================= */

    repondre(
        true,
        "Vérification réussie. Bienvenue sur Burundi Transport !",
        200,
        $role
    );

} catch (Throwable $erreur) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        "Erreur de vérification 2FA : "
        . $erreur->getMessage()
    );

    repondre(
        false,
        "Une erreur technique est survenue. Réessayez plus tard.",
        500
    );
}

?>
