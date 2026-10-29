
<?php

require_once __DIR__ . "/vendor/autoload.php";

/* =========================================
   ENVOYER UN E-MAIL DE STATUT
========================================= */

function envoyerEmailStatutReservation(
    string $destinataire,
    string $nomClient,
    string $reference,
    string $statut
): bool {

    // Vérifier le statut reçu.
    if (!in_array($statut, ["confirmee", "annulee"], true)) {

        error_log(
            "Statut invalide pour l'e-mail : " . $statut
        );

        return false;
    }

    $configMail = require __DIR__ . "/config-mail.php";

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {

        /* =====================================
           1. CONFIGURATION SMTP
        ===================================== */

        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = true;
        $mail->Username = $configMail["email"];
        $mail->Password = $configMail["mot_de_passe"];

        $mail->SMTPSecure =
            \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = 587;
        $mail->CharSet = "UTF-8";

        $mail->setFrom(
            $configMail["email"],
            "Burundi Transport"
        );

        $mail->addAddress(
            $destinataire,
            $nomClient
        );

        $mail->isHTML(true);

        /* =====================================
           2. PROTÉGER LES DONNÉES HTML
        ===================================== */

        $referenceHTML = htmlspecialchars(
            $reference,
            ENT_QUOTES,
            "UTF-8"
        );

        $nomHTML = htmlspecialchars(
            $nomClient,
            ENT_QUOTES,
            "UTF-8"
        );

        /* =====================================
           3. PRÉPARER LE MESSAGE
        ===================================== */

        if ($statut === "confirmee") {

            $mail->Subject =
                "Votre réservation est confirmée - " . $reference;

            $messageHTML = "
                <h2 style='color:#198754;'>
                    Réservation confirmée
                </h2>

                <p>Bonjour {$nomHTML},</p>

                <p>
                    Nous avons le plaisir de vous informer que
                    votre réservation a été confirmée par
                    notre équipe.
                </p>

                <p>
                    <strong>Référence :</strong>
                    {$referenceHTML}
                </p>

                <p>
                    Merci d'avoir choisi Burundi Transport.
                    Nous vous souhaitons un bon voyage !
                </p>
            ";

            $messageTexte =
                "Bonjour {$nomClient},\n\n"
                . "Votre réservation est confirmée.\n"
                . "Référence : {$reference}\n\n"
                . "Merci d'avoir choisi Burundi Transport.";

        } elseif ($statut === "annulee") {

            $mail->Subject =
                "Votre réservation a été annulée - " . $reference;

            $messageHTML = "
                <h2 style='color:#dc3545;'>
                    Réservation annulée
                </h2>

                <p>Bonjour {$nomHTML},</p>

                <p>
                    Nous vous informons que votre réservation
                    a été annulée par notre équipe.
                </p>

                <p>
                    <strong>Référence :</strong>
                    {$referenceHTML}
                </p>

                <p>
                    Pour toute question, veuillez contacter
                    notre service client.
                </p>
            ";

            $messageTexte =
                "Bonjour {$nomClient},\n\n"
                . "Votre réservation a été annulée.\n"
                . "Référence : {$reference}\n\n"
                . "Pour toute question, contactez notre service client.";
        }

        /* =====================================
           4. CONSTRUIRE L'E-MAIL
        ===================================== */

        $mail->Body = "
            <div style='font-family:Arial,sans-serif;
                        max-width:600px;
                        margin:auto;
                        padding:20px;
                        color:#1e2a36;'>

                <h1 style='color:#2c6e9b;'>
                    Burundi Transport
                </h1>

                {$messageHTML}

                <hr>

                <p style='color:#5f7080;font-size:12px;'>
                    Message automatique de Burundi Transport.
                </p>

            </div>
        ";

        $mail->AltBody = $messageTexte;

        /* =====================================
           5. ENVOYER L'E-MAIL
        ===================================== */

        $mail->send();

        error_log(
            "E-mail de réservation envoyé. Statut : "
            . $statut
            . " ; référence : "
            . $reference
        );

        return true;

    } catch (\Throwable $erreur) {

        error_log(
            "Erreur PHPMailer : "
            . $erreur->getMessage()
        );

        error_log(
            "Détail SMTP : "
            . ($mail->ErrorInfo ?: "Aucun détail disponible")
        );

        return false;
    }
}


