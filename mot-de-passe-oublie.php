
<?php

use PHPMailer\PHPMailer\PHPMailer;

header("Content-Type: application/json; charset=UTF-8");

/**
 * Retourner une réponse JSON puis arrêter le script.
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
 * Lire et valider les données JSON.
 */
$donnees = json_decode(file_get_contents("php://input"), true);

if (!is_array($donnees)) {
    repondre(false, "Les données envoyées sont invalides.", 400);
}

$email = strtolower(trim($donnees["email"] ?? ""));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    repondre(false, "Veuillez saisir une adresse e-mail valide.", 400);
}

$messageGeneral =
    "Si cette adresse correspond à un compte, un e-mail contenant " .
    "les instructions de réinitialisation sera envoyé.";

$userId = null;
$tokenHash = null;
$pdo = null;

try {
    // Connexion à la base de données.
    require_once __DIR__ . "/database.php";

    // Chargement de PHPMailer.
    require_once __DIR__ . "/vendor/autoload.php";

    // Chargement de la configuration Gmail existante.
    $configMail = require __DIR__ . "/config-mail.php";

    if (
        empty($configMail["email"]) ||
        empty($configMail["mot_de_passe"])
    ) {
        throw new RuntimeException(
            "La configuration Gmail est incomplète."
        );
    }

    /*
     * Rechercher le compte.
     * La table users contient les clients et les administrateurs.
     */
    $requete = $pdo->prepare(
        "SELECT user_id, email, nom, prenom
         FROM users
         WHERE email = :email
         LIMIT 1"
    );

    $requete->execute([
        "email" => $email
    ]);

    $utilisateur = $requete->fetch(PDO::FETCH_ASSOC);

    // Ne pas révéler si l'adresse existe.
    if (!$utilisateur) {
        repondre(true, $messageGeneral);
    }

    $userId = $utilisateur["user_id"];

    /*
     * Générer un jeton sécurisé.
     * Seul son hachage sera enregistré en base.
     */
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash("sha256", $token);

    // Supprimer les anciennes demandes non utilisées du compte.
    $suppression = $pdo->prepare(
        "DELETE FROM password_resets
         WHERE user_id = :user_id
           AND used_at IS NULL"
    );

    $suppression->execute([
        "user_id" => $userId
    ]);

    // Créer une demande valable une heure.
    $insertion = $pdo->prepare(
        "INSERT INTO password_resets
            (user_id, token_hash, expires_at)
         VALUES
            (:user_id, :token_hash,
             DATE_ADD(NOW(), INTERVAL 1 HOUR))"
    );

    $insertion->execute([
        "user_id" => $userId,
        "token_hash" => $tokenHash
    ]);

    /*
     * Construire le lien local.
     * Il faudra utiliser le domaine HTTPS du site en production.
     */
    $lien =
        "http://localhost:8080/transport_interprovence/" .
        "reinitialiser-mot-de-passe.html?token=" .
        urlencode($token);

    $lienSecurise = htmlspecialchars(
        $lien,
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );

    $prenomSecurise = htmlspecialchars(
        $utilisateur["prenom"],
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );

    // Configurer PHPMailer avec les paramètres Gmail existants.
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;

    $mail->Username = $configMail["email"];
    $mail->Password = $configMail["mot_de_passe"];

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = "UTF-8";

    $mail->setFrom(
        $configMail["email"],
        "Burundi Transport"
    );

    $nomComplet = trim(
        $utilisateur["prenom"] . " " . $utilisateur["nom"]
    );

    $mail->addAddress(
        $utilisateur["email"],
        $nomComplet
    );

    $mail->isHTML(true);
    $mail->Subject = "Réinitialisation de votre mot de passe";

    // Contenu HTML de l'e-mail.
    $mail->Body = <<<HTML
<div style="font-family:Arial,sans-serif;line-height:1.6;max-width:600px;margin:auto">
    <h2 style="color:#2c6e9b">Burundi Transport</h2>

    <p>Bonjour {$prenomSecurise},</p>

    <p>Vous avez demandé la réinitialisation du mot de passe
    de votre compte Burundi Transport.</p>

    <p>Cliquez sur le bouton ci-dessous pour choisir
    un nouveau mot de passe :</p>

    <p>
        <a href="{$lienSecurise}"
           style="display:inline-block;padding:12px 20px;background:#2c6e9b;color:#ffffff;text-decoration:none;border-radius:6px">
            Réinitialiser mon mot de passe
        </a>
    </p>

    <p>Ce lien expire dans une heure et ne peut être utilisé qu'une seule fois.</p>

    <p>Si vous n'avez pas effectué cette demande, ignorez cet e-mail.</p>

    <p>L'équipe Burundi Transport</p>
</div>
HTML;

    $mail->AltBody =
        "Bonjour " . $utilisateur["prenom"] . ",\n\n" .
        "Pour réinitialiser votre mot de passe, ouvrez ce lien :\n" .
        $lien . "\n\n" .
        "Ce lien expire dans une heure. Si vous n'avez pas demandé " .
        "cette réinitialisation, ignorez cet e-mail.";

    // Envoyer l'e-mail.
    $mail->send();

    repondre(true, $messageGeneral);

} catch (Throwable $e) {

    // Enregistrer le détail technique dans le journal PHP.
    error_log(
        "Mot de passe oublié : " . $e->getMessage()
    );

    /*
     * Si le traitement échoue, supprimer le jeton créé
     * afin qu'il ne reste pas une demande inutilisable.
     */
    if ($userId !== null && $tokenHash !== null && $pdo instanceof PDO) {
        try {
            $suppression = $pdo->prepare(
                "DELETE FROM password_resets
                 WHERE user_id = :user_id
                   AND token_hash = :token_hash
                   AND used_at IS NULL"
            );

            $suppression->execute([
                "user_id" => $userId,
                "token_hash" => $tokenHash
            ]);

        } catch (Throwable $erreurSuppression) {
            error_log(
                "Erreur de nettoyage du jeton : " .
                $erreurSuppression->getMessage()
            );
        }
    }

    repondre(
        false,
        "Une erreur technique est survenue. Vérifiez le journal PHP et réessayez.",
        500
    );
}
