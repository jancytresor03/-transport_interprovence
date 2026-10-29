
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/database.php";
require_once __DIR__ . "/envoyer-code-2fa.php";

/*
|--------------------------------------------------------------------------
| PARAMÈTRES DU VERROUILLAGE
|--------------------------------------------------------------------------
*/

const MAX_TENTATIVES_CONNEXION = 5;
const DUREE_VERROUILLAGE_MINUTES = 2;

/*
|--------------------------------------------------------------------------
| HISTORIQUE DES CONNEXIONS
|--------------------------------------------------------------------------
*/

function enregistrerHistoriqueConnexion(
    $pdo,
    $email,
    $statut,
    $userId = null
) {
    try {
        $sql = "
            INSERT INTO historique_connexions (
                user_id,
                email_saisi,
                statut,
                adresse_ip,
                navigateur,
                date_connexion
            )
            VALUES (
                :user_id,
                :email,
                :statut,
                :adresse_ip,
                :navigateur,
                NOW()
            )
        ";

        $requete = $pdo->prepare($sql);

        $requete->execute([
            "user_id" => $userId,
            "email" => substr((string) $email, 0, 255),
            "statut" => $statut,
            "adresse_ip" => $_SERVER["REMOTE_ADDR"] ?? null,
            "navigateur" => substr(
                $_SERVER["HTTP_USER_AGENT"] ?? "",
                0,
                1000
            )
        ]);
    } catch (Throwable $erreur) {
        // L'historique ne doit pas bloquer la connexion.
        error_log(
            "Erreur historique de connexion : " .
            $erreur->getMessage()
        );
    }
}

/*
|--------------------------------------------------------------------------
| RÉPONSE JSON
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| VÉRIFIER LA MÉTHODE HTTP
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    repondre(false, "Méthode non autorisée.", 405);
}

/*
|--------------------------------------------------------------------------
| RÉCUPÉRER LES DONNÉES DU FORMULAIRE
|--------------------------------------------------------------------------
*/

$email = strtolower(trim($_POST["email"] ?? ""));
$motDePasse = $_POST["password"] ?? "";

if (
    $email === "" ||
    $motDePasse === "" ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    enregistrerHistoriqueConnexion(
        $pdo,
        $email,
        "echouee"
    );

    repondre(
        false,
        "Veuillez saisir une adresse e-mail valide et votre mot de passe.",
        400
    );
}

try {

    /*
    |--------------------------------------------------------------------------
    | RECHERCHER LE COMPTE
    |--------------------------------------------------------------------------
    */

    $sql = "
        SELECT
            u.user_id,
            u.nom,
            u.prenom,
            u.email,
            u.password,
            u.role_id,
            u.statut,
            u.deux_facteurs,
            u.tentatives_echouees,
            u.verrouille_jusqua,
            CASE
                WHEN u.verrouille_jusqua IS NOT NULL
                     AND u.verrouille_jusqua > NOW()
                THEN 1
                ELSE 0
            END AS est_verrouille,
            r.nom AS role_nom
        FROM users u
        LEFT JOIN roles r
            ON u.role_id = r.role_id
        WHERE u.email = :email
        LIMIT 1
    ";

    $requete = $pdo->prepare($sql);

    $requete->execute([
        "email" => $email
    ]);

    $utilisateur = $requete->fetch(PDO::FETCH_ASSOC);

    /*
    |--------------------------------------------------------------------------
    | VÉRIFIER LE VERROUILLAGE
    |--------------------------------------------------------------------------
    */

    if ($utilisateur) {

        // Le compte est toujours dans sa période de verrouillage.
        if ((int) $utilisateur["est_verrouille"] === 1) {

            enregistrerHistoriqueConnexion(
                $pdo,
                $email,
                "echouee",
                $utilisateur["user_id"]
            );

            repondre(
                false,
                "Votre compte est temporairement verrouillé. Réessayez dans 15 minutes au maximum.",
                423
            );
        }

        /*
         * Si le verrouillage précédent est terminé,
         * remettre le compteur à zéro.
         */
        if (!empty($utilisateur["verrouille_jusqua"])) {

            $resetExpiration = $pdo->prepare("
                UPDATE users
                SET tentatives_echouees = 0,
                    verrouille_jusqua = NULL
                WHERE user_id = :user_id
            ");

            $resetExpiration->execute([
                "user_id" => $utilisateur["user_id"]
            ]);

            $utilisateur["tentatives_echouees"] = 0;
            $utilisateur["verrouille_jusqua"] = null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VÉRIFIER L'EMAIL ET LE MOT DE PASSE
    |--------------------------------------------------------------------------
    */

    if (
        !$utilisateur ||
        !password_verify(
            $motDePasse,
            $utilisateur["password"]
        )
    ) {

        if ($utilisateur) {

            $tentatives =
                (int) $utilisateur["tentatives_echouees"] + 1;

            if ($tentatives >= MAX_TENTATIVES_CONNEXION) {

                /*
                 * Verrouiller le compte pendant 15 minutes.
                 * Le statut général du compte n'est pas modifié.
                 */
                $verrouillage = $pdo->prepare("
                    UPDATE users
                    SET tentatives_echouees = :maximum,
                        verrouille_jusqua = DATE_ADD(
                            NOW(),
                            INTERVAL 15 MINUTE
                        )
                    WHERE user_id = :user_id
                ");

                $verrouillage->execute([
                    "maximum" => MAX_TENTATIVES_CONNEXION,
                    "user_id" => $utilisateur["user_id"]
                ]);

                enregistrerHistoriqueConnexion(
                    $pdo,
                    $email,
                    "echouee",
                    $utilisateur["user_id"]
                );

                repondre(
                    false,
                    "Trop de tentatives incorrectes. Votre compte est verrouillé pendant 15 minutes.",
                    423
                );
            }

            /*
             * Enregistrer la tentative incorrecte.
             */
            $miseAJourTentatives = $pdo->prepare("
                UPDATE users
                SET tentatives_echouees = :tentatives
                WHERE user_id = :user_id
            ");

            $miseAJourTentatives->execute([
                "tentatives" => $tentatives,
                "user_id" => $utilisateur["user_id"]
            ]);
        }

        enregistrerHistoriqueConnexion(
            $pdo,
            $email,
            "echouee",
            $utilisateur["user_id"] ?? null
        );

        repondre(
            false,
            "Email ou mot de passe incorrect.",
            401
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MOT DE PASSE CORRECT : RÉINITIALISER LES TENTATIVES
    |--------------------------------------------------------------------------
    */

    $resetTentatives = $pdo->prepare("
        UPDATE users
        SET tentatives_echouees = 0,
            verrouille_jusqua = NULL
        WHERE user_id = :user_id
    ");

    $resetTentatives->execute([
        "user_id" => $utilisateur["user_id"]
    ]);

    /*
    |--------------------------------------------------------------------------
    | VÉRIFIER LE STATUT DU COMPTE
    |--------------------------------------------------------------------------
    */

    if ($utilisateur["statut"] !== "actif") {

        enregistrerHistoriqueConnexion(
            $pdo,
            $email,
            "echouee",
            $utilisateur["user_id"]
        );

        repondre(
            false,
            "Votre compte est bloqué ou inactif. Contactez l'administrateur.",
            403
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VÉRIFIER LE RÔLE
    |--------------------------------------------------------------------------
    */

    $role = strtolower(
        trim($utilisateur["role_nom"] ?? "")
    );

    if (!in_array($role, ["admin", "client"], true)) {

        enregistrerHistoriqueConnexion(
            $pdo,
            $email,
            "echouee",
            $utilisateur["user_id"]
        );

        repondre(
            false,
            "Le rôle de ce compte est invalide.",
            403
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RÈGLES DE DOUBLE AUTHENTIFICATION
    |--------------------------------------------------------------------------
    |
    | Client : 2FA obligatoire.
    | Administrateur : 2FA selon son réglage.
    |--------------------------------------------------------------------------
    */

    if ($role === "client") {

        if ((int) $utilisateur["deux_facteurs"] !== 1) {

            $forcer2FA = $pdo->prepare("
                UPDATE users
                SET deux_facteurs = 1
                WHERE user_id = :user_id
            ");

            $forcer2FA->execute([
                "user_id" => $utilisateur["user_id"]
            ]);

            $utilisateur["deux_facteurs"] = 1;
        }

        $verificationRequise = true;

    } else {

        $verificationRequise =
            ((int) $utilisateur["deux_facteurs"] === 1);
    }

    /*
    |--------------------------------------------------------------------------
    | SUPPRIMER UNE ANCIENNE VÉRIFICATION EN ATTENTE
    |--------------------------------------------------------------------------
    */

    unset(
        $_SESSION["pending_2fa_user_id"],
        $_SESSION["pending_2fa_started"]
    );

    /*
    |--------------------------------------------------------------------------
    | DOUBLE AUTHENTIFICATION REQUISE
    |--------------------------------------------------------------------------
    */

    if ($verificationRequise) {

        $code = (string) random_int(100000, 999999);
        $codeHash = hash("sha256", $code);

        $sqlCode = "
            INSERT INTO codes_2fa (
                user_id,
                code_hash,
                expires_at,
                tentatives,
                date_creation
            )
            VALUES (
                :user_id,
                :code_hash,
                DATE_ADD(NOW(), INTERVAL 5 MINUTE),
                0,
                NOW()
            )
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

        /*
         * Envoyer le code par e-mail.
         */
        if (
            !envoyerCode2FA(
                $utilisateur["email"],
                $code
            )
        ) {

            $suppression = $pdo->prepare("
                DELETE FROM codes_2fa
                WHERE user_id = :user_id
            ");

            $suppression->execute([
                "user_id" => $utilisateur["user_id"]
            ]);

            enregistrerHistoriqueConnexion(
                $pdo,
                $email,
                "echouee",
                $utilisateur["user_id"]
            );

            repondre(
                false,
                "Impossible d'envoyer le code. Réessayez plus tard.",
                500
            );
        }

        /*
         * La connexion n'est pas encore terminée.
         * L'utilisateur doit vérifier le code 2FA.
         */
        session_regenerate_id(true);

        $_SESSION["pending_2fa_user_id"] =
            $utilisateur["user_id"];

        $_SESSION["pending_2fa_started"] = time();

        repondre(
            true,
            "Un code de vérification a été envoyé par e-mail.",
            200,
            $role,
            true
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CONNEXION SANS 2FA
    |--------------------------------------------------------------------------
    */

    session_regenerate_id(true);

    $_SESSION["user_id"] = $utilisateur["user_id"];
    $_SESSION["nom"] = $utilisateur["nom"];
    $_SESSION["prenom"] = $utilisateur["prenom"];
    $_SESSION["email"] = $utilisateur["email"];
    $_SESSION["role_id"] = $utilisateur["role_id"];
    $_SESSION["role"] = $role;

    /*
     * Mettre à jour la dernière connexion.
     */
    $miseAJour = $pdo->prepare("
        UPDATE users
        SET derniere_connexion = NOW()
        WHERE user_id = :user_id
    ");

    $miseAJour->execute([
        "user_id" => $utilisateur["user_id"]
    ]);

    /*
     * Enregistrer la connexion réussie.
     */
    enregistrerHistoriqueConnexion(
        $pdo,
        $email,
        "reussie",
        $utilisateur["user_id"]
    );

    repondre(
        true,
        "Connexion réussie.",
        200,
        $role,
        false
    );

} catch (Throwable $erreur) {

    error_log(
        "Erreur de connexion : " . $erreur->getMessage()
    );

    repondre(
        false,
        "Une erreur technique est survenue. Réessayez plus tard.",
        500
    );
}
