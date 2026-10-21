
<?php

require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");

require_once __DIR__ . "/../database.php";

header("Content-Type: application/json; charset=UTF-8");

function repondre($succes, $message, $parametres = null, $code = 200)
{
    http_response_code($code);

    $reponse = [
        "succes" => $succes,
        "message" => $message
    ];

    if ($parametres !== null) {
        $reponse["parametres"] = $parametres;
    }

    echo json_encode($reponse, JSON_UNESCAPED_UNICODE);
    exit;
}

$defauts = [
    "nomEntreprise" => "Burundi Transport",
    "telephoneEntreprise" => "+257 79 00 00 00",
    "emailEntreprise" => "contact@burunditransport.bi",
    "adresseEntreprise" => "Bujumbura",
    "placesDefaut" => 28,
    "devise" => "BIF",
    "delaiReservation" => "30",
    "passagersMaximum" => 5,
    "notificationsReservation" => true,
    "notificationsEmail" => true,
    "alertesSysteme" => true,
    "theme" => "clair",
    "modeMaintenance" => false
];

$clesAutorisees = array_keys($defauts);

try {

    /*
    =========================================
    CHARGER LES PARAMETRES
    =========================================
    */

    if ($_SERVER["REQUEST_METHOD"] === "GET") {

        $stmt = $pdo->query(
            "SELECT cle, valeur FROM parametres"
        );

        $parametres = $defauts;

        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $cle = $ligne["cle"];

            if (!in_array($cle, $clesAutorisees, true)) {
                continue;
            }

            $valeur = json_decode(
                $ligne["valeur"],
                true
            );

            if (json_last_error() === JSON_ERROR_NONE) {
                $parametres[$cle] = $valeur;
            }
        }

        repondre(
            true,
            "Paramètres chargés avec succès.",
            $parametres
        );
    }

    /*
    =========================================
    VERIFIER LA METHODE
    =========================================
    */

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {

        repondre(
            false,
            "Méthode non autorisée.",
            null,
            405
        );
    }

    $donnees = json_decode(
        file_get_contents("php://input"),
        true
    );

    if (!is_array($donnees)) {

        repondre(
            false,
            "Données JSON invalides.",
            null,
            400
        );
    }

    $action = $donnees["action"] ?? "";

    /*
    =========================================
    REINITIALISER LES PARAMETRES
    =========================================
    */

    if ($action === "reset") {

        $pdo->beginTransaction();

        $pdo->exec("DELETE FROM parametres");

        $pdo->commit();

        repondre(
            true,
            "Les paramètres ont été réinitialisés.",
            $defauts
        );
    }

    /*
    =========================================
    ENREGISTRER LES PARAMETRES
    =========================================
    */

    if ($action !== "save") {

        repondre(
            false,
            "Action inconnue.",
            null,
            400
        );
    }

    $p = $donnees["parametres"] ?? null;

    if (!is_array($p)) {

        repondre(
            false,
            "Paramètres manquants.",
            null,
            400
        );
    }

    $p = array_merge($defauts, $p);

    $p["nomEntreprise"] = trim(
        (string) $p["nomEntreprise"]
    );

    $p["telephoneEntreprise"] = trim(
        (string) $p["telephoneEntreprise"]
    );

    $p["emailEntreprise"] = trim(
        (string) $p["emailEntreprise"]
    );

    $p["adresseEntreprise"] = trim(
        (string) $p["adresseEntreprise"]
    );

    if ($p["nomEntreprise"] === "") {
        repondre(false, "Le nom de l'entreprise est obligatoire.", null, 422);
    }

    if (!filter_var($p["emailEntreprise"], FILTER_VALIDATE_EMAIL)) {
        repondre(false, "Adresse email invalide.", null, 422);
    }

    $p["placesDefaut"] = filter_var(
        $p["placesDefaut"],
        FILTER_VALIDATE_INT
    );

    $p["passagersMaximum"] = filter_var(
        $p["passagersMaximum"],
        FILTER_VALIDATE_INT
    );

    if (
        $p["placesDefaut"] === false ||
        $p["placesDefaut"] < 1 ||
        $p["placesDefaut"] > 100
    ) {
        repondre(false, "Le nombre de places doit être compris entre 1 et 100.", null, 422);
    }

    if (
        $p["passagersMaximum"] === false ||
        $p["passagersMaximum"] < 1 ||
        $p["passagersMaximum"] > 20
    ) {
        repondre(false, "Le nombre maximum de passagers doit être compris entre 1 et 20.", null, 422);
    }

    $p["delaiReservation"] = (string) $p["delaiReservation"];

    if (!in_array($p["delaiReservation"], ["15", "30", "60", "120"], true)) {
        repondre(false, "Délai de réservation invalide.", null, 422);
    }

    if (!in_array($p["devise"], ["BIF", "USD"], true)) {
        repondre(false, "Devise invalide.", null, 422);
    }

    if (!in_array($p["theme"], ["clair", "sombre"], true)) {
        repondre(false, "Thème invalide.", null, 422);
    }

    foreach ([
        "notificationsReservation",
        "notificationsEmail",
        "alertesSysteme",
        "modeMaintenance"
    ] as $cle) {

        if (!is_bool($p[$cle])) {
            repondre(false, "Valeur incorrecte pour : " . $cle, null, 422);
        }
    }

    $pdo->beginTransaction();

    $sql = "
        INSERT INTO parametres (cle, valeur)
        VALUES (:cle, :valeur)
        ON DUPLICATE KEY UPDATE
            valeur = VALUES(valeur)
    ";

    $stmt = $pdo->prepare($sql);

    foreach ($clesAutorisees as $cle) {

        $valeur = json_encode(
            $p[$cle],
            JSON_UNESCAPED_UNICODE
        );

        $stmt->execute([
            ":cle" => $cle,
            ":valeur" => $valeur
        ]);
    }

    $pdo->commit();

    repondre(
        true,
        "Les paramètres ont été enregistrés avec succès.",
        $p
    );

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Erreur parametres-api.php : " . $e->getMessage());

    repondre(
        false,
        "Une erreur est survenue lors du traitement des paramètres.",
        null,
        500
    );
}
