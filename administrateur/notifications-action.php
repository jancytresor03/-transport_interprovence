
<?php

/* =====================================================
   1. VÉRIFIER LES DROITS D'ACCÈS
===================================================== */

require_once __DIR__ . "/../verification.php";

verifierAcces("admin", "../login.html");


/* =====================================================
   2. CONNEXION À LA BASE DE DONNÉES
===================================================== */

require_once __DIR__ . "/../database.php";


/* =====================================================
   3. CONFIGURATION DE LA RÉPONSE JSON
===================================================== */

header("Content-Type: application/json; charset=UTF-8");


/* =====================================================
   4. AUTORISER UNIQUEMENT LA MÉTHODE POST
===================================================== */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Méthode non autorisée."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/* =====================================================
   5. RÉCUPÉRER LES DONNÉES ENVOYÉES PAR JAVASCRIPT
===================================================== */

$donnees = json_decode(file_get_contents("php://input"), true);

if (!is_array($donnees) || !isset($donnees["action"])) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Données invalides."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$action = $donnees["action"];


/* =====================================================
   6. EXÉCUTER L'ACTION DEMANDÉE
===================================================== */

try {

    /* ---------------------------------------------
       A. MARQUER UNE NOTIFICATION COMME LUE
    --------------------------------------------- */

    if ($action === "marquer_lue") {

        if (
            !isset($donnees["notification_id"]) ||
            !is_string($donnees["notification_id"]) ||
            trim($donnees["notification_id"]) === ""
        ) {
            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "Identifiant de notification invalide."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $notificationId = trim($donnees["notification_id"]);

        $sql = "UPDATE notifications
                SET statut = 'lue',
                    date_lecture = NOW()
                WHERE notification_id = :id
                  AND statut = 'non_lue'";

        $requete = $pdo->prepare($sql);

        $requete->execute([
            ":id" => $notificationId
        ]);

        if ($requete->rowCount() === 0) {

            // Vérifier si la notification existe déjà et est lue.
            $sqlVerification = "SELECT statut
                                FROM notifications
                                WHERE notification_id = :id";

            $verification = $pdo->prepare($sqlVerification);

            $verification->execute([
                ":id" => $notificationId
            ]);

            $notification = $verification->fetch(PDO::FETCH_ASSOC);

            if (!$notification) {
                http_response_code(404);

                echo json_encode([
                    "success" => false,
                    "message" => "Notification introuvable."
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }

            if ($notification["statut"] !== "lue") {
                http_response_code(409);

                echo json_encode([
                    "success" => false,
                    "message" => "La notification n'a pas pu être modifiée."
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }
        }

        echo json_encode([
            "success" => true,
            "message" => "Notification marquée comme lue."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    /* ---------------------------------------------
       B. MARQUER TOUTES LES NOTIFICATIONS COMME LUES
    --------------------------------------------- */

    elseif ($action === "tout_marquer_lue") {

        $sql = "UPDATE notifications
                SET statut = 'lue',
                    date_lecture = NOW()
                WHERE statut = 'non_lue'";

        $requete = $pdo->prepare($sql);
        $requete->execute();

        echo json_encode([
            "success" => true,
            "message" => "Toutes les notifications ont été marquées comme lues.",
            "notifications_modifiees" => $requete->rowCount()
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    /* ---------------------------------------------
       C. ACTION INCONNUE
    --------------------------------------------- */

    else {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Action inconnue."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

} catch (PDOException $e) {

    // Conserver les détails techniques dans les journaux du serveur.
    error_log("Erreur notifications-action.php : " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Une erreur est survenue lors de la modification des notifications."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}
?>
