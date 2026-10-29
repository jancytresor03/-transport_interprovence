
<?php

/* =========================================
   1. CHARGER PHPMailer ET LA CONFIGURATION
========================================= */

require_once __DIR__ . "/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$configMail = require __DIR__ . "/config-mail.php";

/* =========================================
   2. FONCTION D'ENVOI DE NOTIFICATION
========================================= */

function envoyerNotificationReservation(
    string $destinataire,
    string $nomClient,
    array $reservation
): bool {

    global $configMail;

    $mail = new PHPMailer(true);

    try {

        /* =========================================
           3. CONFIGURATION SMTP
        ========================================= */

        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = true;
        $mail->Username = $configMail["email"];
        $mail->Password = $configMail["mot_de_passe"];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->CharSet = "UTF-8";

        /* =========================================
           4. EXPEDITEUR ET DESTINATAIRE
        ========================================= */

        $mail->setFrom(
            $configMail["email"],
            "Burundi Transport"
        );

        $mail->addAddress($destinataire, $nomClient);

        $mail->isHTML(true);

        /* =========================================
           5. PREPARER LES DONNEES
        ========================================= */

        $reference = htmlspecialchars(
            (string) ($reservation["reference"] ?? ""),
            ENT_QUOTES,
            "UTF-8"
        );

        $depart = htmlspecialchars(
            (string) ($reservation["depart"] ?? ""),
            ENT_QUOTES,
            "UTF-8"
        );

        $destination = htmlspecialchars(
            (string) ($reservation["destination"] ?? ""),
            ENT_QUOTES,
            "UTF-8"
        );

        $dateDepart = htmlspecialchars(
            (string) ($reservation["date_depart"] ?? ""),
            ENT_QUOTES,
            "UTF-8"
        );

        $heureDepart = htmlspecialchars(
            (string) ($reservation["heure_depart"] ?? ""),
            ENT_QUOTES,
            "UTF-8"
        );

        $passagers = (int) (
            $reservation["nombre_passagers"] ?? 0
        );

        $montantTotal = number_format(
            (float) ($reservation["montant_total"] ?? 0),
            0,
            ",",
            " "
        );

        /* =========================================
           6. SUJET DE L'EMAIL
        ========================================= */

        $mail->Subject =
            "Réservation enregistrée - " . $reference;

        /* =========================================
           7. CONTENU HTML
        ========================================= */

        $mail->Body = "
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
        </head>

        <body style='margin:0;padding:20px;background:#f3f6f9;
                     font-family:Arial,sans-serif;color:#1e2a36;'>

            <div style='max-width:600px;margin:auto;background:#ffffff;
                        padding:25px;border-radius:10px;'>

                <h2 style='color:#2c6e9b;'>
                    Burundi Transport
                </h2>

                <p>Bonjour "
                    . htmlspecialchars(
                        $nomClient,
                        ENT_QUOTES,
                        "UTF-8"
                    ) .
                ",</p>

                <p>
                    Nous vous confirmons que votre demande de
                    réservation a bien été enregistrée.
                </p>

                <div style='background:#f3f6f9;padding:18px;
                            border-radius:8px;'>

                    <h3>Informations de réservation</h3>

                    <p>
                        <strong>Référence :</strong>
                        {$reference}
                    </p>

                    <p>
                        <strong>Départ :</strong>
                        {$depart}
                    </p>

                    <p>
                        <strong>Destination :</strong>
                        {$destination}
                    </p>

                    <p>
                        <strong>Date de départ :</strong>
                        {$dateDepart}
                    </p>

                    <p>
                        <strong>Heure de départ :</strong>
                        {$heureDepart}
                    </p>

                    <p>
                        <strong>Nombre de passagers :</strong>
                        {$passagers}
                    </p>

                    <p>
                        <strong>Montant total :</strong>
                        {$montantTotal}
                    </p>

                    <p>
                        <strong>Statut :</strong>
                        En attente de confirmation
                    </p>

                </div>

                <p>
                    Nous vous informerons par e-mail lorsque
                    votre réservation sera confirmée ou annulée.
                </p>

                <p>Merci d'avoir choisi Burundi Transport.</p>

                <hr style='border:none;border-top:1px solid #ddd;'>

                <p style='font-size:12px;color:#667085;'>
                    Ceci est un message automatique.
                    Merci de ne pas répondre directement à cet e-mail.
                </p>

            </div>

        </body>
        </html>
        ";

        /* =========================================
           8. VERSION TEXTE
        ========================================= */

        $mail->AltBody =
            "Bonjour " . $nomClient . ",\n\n"
            . "Votre demande de réservation a bien été enregistrée.\n\n"
            . "Référence : " . ($reservation["reference"] ?? "") . "\n"
            . "Départ : " . ($reservation["depart"] ?? "") . "\n"
            . "Destination : " . ($reservation["destination"] ?? "") . "\n"
            . "Date de départ : " . ($reservation["date_depart"] ?? "") . "\n"
            . "Heure de départ : " . ($reservation["heure_depart"] ?? "") . "\n"
            . "Passagers : " . ($reservation["nombre_passagers"] ?? "") . "\n"
            . "Montant total : " . ($reservation["montant_total"] ?? "") . "\n"
            . "Statut : En attente de confirmation\n\n"
            . "Merci d'avoir choisi Burundi Transport.";

        /* =========================================
           9. ENVOYER L'EMAIL
        ========================================= */

        $mail->send();

        return true;

    } catch (Exception $e) {

        error_log(
            "Erreur d'envoi de notification de réservation : "
            . $mail->ErrorInfo
        );

        return false;
    }
}


