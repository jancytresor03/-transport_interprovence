
<?php

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate");

require_once __DIR__ . "/../verification.php";

/* ==============================
   RÉPONSE JSON
============================== */

function repondreJSON(
    bool $succes,
    string $message = "",
    $donnees = null,
    int $code = 200
): void {
    http_response_code($code);

    echo json_encode([
        "succes" => $succes,
        "message" => $message,
        "donnees" => $donnees
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/* ==============================
   VÉRIFIER L'ADMINISTRATEUR
============================== */

if (
    empty($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    repondreJSON(
        false,
        "Accès refusé. Connectez-vous comme administrateur.",
        null,
        403
    );
}

require_once __DIR__ . "/../database.php";

/* ==============================
   CRÉER UNE NOTIFICATION
============================== */

function ajouterNotification(
    PDO $pdo,
    string $userId,
    string $titre,
    string $message
): void {

    $sql = "
        INSERT INTO notifications (
            notification_id,
            user_id,
            type_notification,
            titre,
            message,
            statut
        )
        VALUES (
            '',
            :user_id,
            'trajet',
            :titre,
            :message,
            'non_lue'
        )
    ";

    $requete = $pdo->prepare($sql);

    $requete->execute([
        ":user_id" => $userId,
        ":titre" => $titre,
        ":message" => $message
    ]);
}

/* ==============================
   RÉCUPÉRER UN TRAJET
============================== */

function obtenirTrajet(PDO $pdo, string $id): ?array
{
    $requete = $pdo->prepare("
        SELECT
            trajet_id,
            depart,
            destination,
            date_depart,
            heure_depart
        FROM trajets
        WHERE trajet_id = ?
    ");

    $requete->execute([$id]);

    $trajet = $requete->fetch(PDO::FETCH_ASSOC);

    return $trajet ?: null;
}

/* ==============================
   TRAITEMENT DE L'API
============================== */

try {

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    $methode = $_SERVER["REQUEST_METHOD"];
    $action = $_GET["action"] ?? "";

    /* --------------------------------
       1. AFFICHER LES TRAJETS
    -------------------------------- */

    if ($methode === "GET" && $action === "liste") {

        $requete = $pdo->query("
            SELECT
                trajet_id,
                depart,
                destination,
                date_depart,
                heure_depart,
                prix,
                nombre_places,
                statut
            FROM trajets
            ORDER BY date_depart ASC, heure_depart ASC
        ");

        repondreJSON(
            true,
            "Trajets chargés.",
            $requete->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /* Les autres opérations utilisent POST */

    if ($methode !== "POST") {
        repondreJSON(
            false,
            "Méthode non autorisée.",
            null,
            405
        );
    }

    /* --------------------------------
       2. LIRE LES DONNÉES
    -------------------------------- */

    $contenu = file_get_contents("php://input");
    $donnees = json_decode($contenu, true);

    if (!is_array($donnees)) {
        repondreJSON(
            false,
            "Les données reçues sont invalides.",
            null,
            400
        );
    }

    $action = $donnees["action"] ?? "";
    $adminId = (string) $_SESSION["user_id"];

    /* Transmettre l'administrateur aux déclencheurs SQL */

    $pdo->prepare("SET @user_id_connecte = ?")
        ->execute([$adminId]);

    /* ========================================
       3. SUPPRIMER UN TRAJET
    ======================================== */

    if ($action === "supprimer") {

        $id = trim((string) ($donnees["trajet_id"] ?? ""));

        if ($id === "") {
            repondreJSON(
                false,
                "Identifiant du trajet manquant.",
                null,
                400
            );
        }

        $pdo->beginTransaction();

        $ancienTrajet = obtenirTrajet($pdo, $id);

        if (!$ancienTrajet) {
            $pdo->rollBack();

            repondreJSON(
                false,
                "Trajet introuvable.",
                null,
                404
            );
        }

        $requete = $pdo->prepare(
            "DELETE FROM trajets WHERE trajet_id = ?"
        );

        $requete->execute([$id]);

        ajouterNotification(
            $pdo,
            $adminId,
            "Trajet supprimé",
            "Le trajet de "
                . $ancienTrajet["depart"]
                . " vers "
                . $ancienTrajet["destination"]
                . " a été supprimé."
        );

        $pdo->commit();

        repondreJSON(
            true,
            "Trajet supprimé avec succès."
        );
    }

    /* ========================================
       4. VALIDATION DES DONNÉES
    ======================================== */

    if (!in_array($action, ["ajouter", "modifier"], true)) {
        repondreJSON(
            false,
            "Action inconnue.",
            null,
            400
        );
    }

    $depart = trim((string) ($donnees["depart"] ?? ""));
    $destination = trim((string) ($donnees["destination"] ?? ""));
    $date = trim((string) ($donnees["date_depart"] ?? ""));
    $heure = trim((string) ($donnees["heure_depart"] ?? ""));

    $prix = $donnees["prix"] ?? null;
    $places = $donnees["nombre_places"] ?? null;
    $statut = $donnees["statut"] ?? "actif";

    if (
        $depart === "" ||
        $destination === "" ||
        $date === "" ||
        $heure === "" ||
        !is_numeric($prix) ||
        !is_numeric($places)
    ) {
        repondreJSON(
            false,
            "Veuillez remplir correctement tous les champs.",
            null,
            400
        );
    }

    if (strcasecmp($depart, $destination) === 0) {
        repondreJSON(
            false,
            "Le départ et la destination doivent être différents.",
            null,
            400
        );
    }

    if ((float) $prix < 0 || (int) $places < 1) {
        repondreJSON(
            false,
            "Le prix doit être positif ou nul et le nombre de places supérieur à zéro.",
            null,
            400
        );
    }

    if (!in_array(
        $statut,
        ["actif", "inactif", "termine", "annule"],
        true
    )) {
        repondreJSON(
            false,
            "Statut invalide.",
            null,
            400
        );
    }

    /* ========================================
       5. AJOUTER UN TRAJET
    ======================================== */

    if ($action === "ajouter") {

        $pdo->beginTransaction();

        $requete = $pdo->prepare("
            INSERT INTO trajets (
                depart,
                destination,
                date_depart,
                heure_depart,
                prix,
                nombre_places,
                statut
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $requete->execute([
            $depart,
            $destination,
            $date,
            $heure,
            $prix,
            (int) $places,
            $statut
        ]);

        ajouterNotification(
            $pdo,
            $adminId,
            "Nouveau trajet ajouté",
            "Le trajet de "
                . $depart
                . " vers "
                . $destination
                . " a été ajouté pour le "
                . $date
                . " à "
                . $heure
                . "."
        );

        $pdo->commit();

        repondreJSON(
            true,
            "Trajet ajouté avec succès."
        );
    }

    /* ========================================
       6. MODIFIER UN TRAJET
    ======================================== */

    if ($action === "modifier") {

        $id = trim((string) ($donnees["trajet_id"] ?? ""));

        if ($id === "") {
            repondreJSON(
                false,
                "Identifiant du trajet manquant.",
                null,
                400
            );
        }

        $pdo->beginTransaction();

        $ancienTrajet = obtenirTrajet($pdo, $id);

        if (!$ancienTrajet) {
            $pdo->rollBack();

            repondreJSON(
                false,
                "Trajet introuvable.",
                null,
                404
            );
        }

        $requete = $pdo->prepare("
            UPDATE trajets
            SET
                depart = ?,
                destination = ?,
                date_depart = ?,
                heure_depart = ?,
                prix = ?,
                nombre_places = ?,
                statut = ?
            WHERE trajet_id = ?
        ");

        $requete->execute([
            $depart,
            $destination,
            $date,
            $heure,
            $prix,
            (int) $places,
            $statut,
            $id
        ]);

        ajouterNotification(
            $pdo,
            $adminId,
            "Trajet modifié",
            "Le trajet "
                . $ancienTrajet["depart"]
                . " vers "
                . $ancienTrajet["destination"]
                . " a été mis à jour. Nouveau trajet : "
                . $depart
                . " vers "
                . $destination
                . ", le "
                . $date
                . " à "
                . $heure
                . "."
        );

        $pdo->commit();

        repondreJSON(
            true,
            "Trajet modifié avec succès."
        );
    }

} catch (PDOException $erreur) {

    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        "Erreur API trajets : " . $erreur->getMessage()
    );

    repondreJSON(
        false,
        "Erreur de base de données. Vérifiez les contraintes, les identifiants et les déclencheurs SQL.",
        null,
        500
    );

} catch (Throwable $erreur) {

    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        "Erreur inattendue API trajets : " . $erreur->getMessage()
    );

    repondreJSON(
        false,
        "Une erreur inattendue est survenue.",
        null,
        500
    );
}
