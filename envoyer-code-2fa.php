
<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . "/vendor/autoload.php";

function envoyerCode2FA(string $destinataire, string $code): bool
{
    $config = require __DIR__ . "/config-mail.php";

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = true;
        $mail->Username = $config["email"];
        $mail->Password = $config["mot_de_passe"];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->CharSet = "UTF-8";
        $mail->setFrom($config["email"], "Burundi Transport");
        $mail->addAddress($destinataire);

        $mail->isHTML(true);
        $mail->Subject = "Votre code de vérification - Burundi Transport";
        $mail->Body = "
            <h2>Burundi Transport</h2>
            <p>Voici votre code de vérification :</p>
            <h1 style='letter-spacing: 5px;'>"
                . htmlspecialchars($code, ENT_QUOTES, "UTF-8") .
            "</h1>
            <p>Ce code expire dans 5 minutes.</p>
            <p>Si vous n'êtes pas à l'origine de cette demande, ignorez cet e-mail.</p>
        ";

        $mail->AltBody =
            "Votre code de vérification Burundi Transport est : "
            . $code
            . ". Il expire dans 5 minutes.";

        $mail->send();

        return true;

    } catch (\Throwable $e) {
        error_log("Échec d'envoi du code 2FA : " . $e->getMessage());
        return false;
    }
}
