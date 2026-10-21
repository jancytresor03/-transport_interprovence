<?php

require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");

require_once __DIR__ . "/../database.php";

header("Content-Type: application/json; charset=UTF-8");

function repondre($succes, $message, $code = 200) {
    http_response_code($code);

    echo json_encode([
        "succes" => $succes,
        "message" => $message
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    repondre(false, "Méthode non autorisée.", 405);
}

$entree = json_decode(file_get_contents("php://input"), true);

if (!is_array($entree)) {
    repondre(false, "Données JSON invalides.", 400);
}

$action = $entree["action"] ?? "";
$archiveId = trim($entree["archive_id"] ?? "");

if ($archiveId === "" || strlen($archiveId) > 20) {
    repondre(false, "Identifiant d'archive invalide.", 400);
}

if (!in_array($action, ["restaurer", "supprimer"], true)) {
    repondre(false, "Action inconnue.", 400);
}

/*
 * Colonnes autorisées pour la restauration.
 * Les noms de tables et de colonnes ne viennent jamais directement
 * de la requête du navigateur.
 */
$tablesAutorisees = [

    "trajets" => [
        "type" => "trajet",
        "id" => "trajet_id",
        "colonnes" => [
            "trajet_id",
            "depart",
            "destination",
            "date_depart",
            "heure_depart",
            "prix",
            "nombre_places",
            "statut",
            "date_creation",
            "date_modification"
        ],
        "obligatoires" => [
            "trajet_id",
            "depart",
            "destination",
            "date_depart",
            "heure_depart",
            "prix",
            "nombre_places",
            "statut"
        ]
    ],

    "reservations" => [
        "type" => "reservation",
        "id" => "reservation_id",
        "colonnes" => [
            "reservation_id",
            "reference",
            "user_id",
            "trajet_id",
            "nom_passager",
            "prenom_passager",
            "telephone_passager",
            "email_passager",
            "type_identite",
            "numero_identite",
            "nombre_passagers",
            "montant_total",
            "statut",
            "remarque",
            "date_expiration",
            "date_creation",
            "date_modification"
        ],
        "obligatoires" => [
            "reservation_id",
            "reference",
            "user_id",
            "trajet_id",
            "nom_passager",
            "prenom_passager",
            "telephone_passager",
            "email_passager",
            "type_identite",
            "numero_identite",
            "nombre_passagers",
            "montant_total",
            "statut"
        ]
    ],

    "users" => [
        "type" => "utilisateur",
        "id" => "user_id",
        "colonnes" => [
            "user_id",
            "nom",
            "prenom",
            "email",
            "telephone",
            "password",
            "role_id",
            "adresse",
            "statut",
            "deux_facteurs",
            "date_creation",
            "date_modification",
            "derniere_connexion"
        ],
        "obligatoires" => [
            "user_id",
            "nom",
            "prenom",
            "email",
            "telephone",
            "password",
            "statut"
        ]
    ]
];

try {

    $pdo->beginTransaction();

    // Verrouiller l'archive pendant le traitement
    $requete = $pdo->prepare("
        SELECT *
        FROM archives
        WHERE archive_id = :id
        FOR UPDATE
    ");

    $requete->execute([
        ":id" => $archiveId
    ]);

    $archive = $requete->fetch(PDO::FETCH_ASSOC);

    if (!$archive) {
        $pdo->rollBack();
        repondre(false, "Archive introuvable.", 404);
    }

    if ($action === "supprimer") {

        $requete = $pdo->prepare("
            DELETE FROM archives
            WHERE archive_id = :id
        ");

        $requete->execute([
            ":id" => $archiveId
        ]);

        $pdo->commit();

        repondre(
            true,
            "Archive supprimée définitivement."
        );
    }

    // La restauration ne concerne que les archives actives
    if ($archive["statut"] !== "archive") {
        $pdo->rollBack();

        repondre(
            false,
            "Cette archive a déjà été restaurée."
        );
    }

    $table = $archive["table_origine"];

    if (!isset($tablesAutorisees[$table])) {
        $pdo->rollBack();

        repondre(
            false,
            "La restauration de cette table n'est pas encore prise en charge."
        );
    }

    $configuration = $tablesAutorisees[$table];

    if ($archive["type_donnee"] !== $configuration["type"]) {
        $pdo->rollBack();

        repondre(
            false,
            "Le type de donnée ne correspond pas à la table d'origine."
        );
    }

    // Lire les données archivées
    $donnees = json_decode(
        $archive["donnees_archivees"],
        true
    );

    if (!is_array($donnees) || json_last_error() !== JSON_ERROR_NONE) {
        $pdo->rollBack();

        repondre(false, "Les données archivées sont invalides.");
    }

    $colonnes = array_intersect(
        $configuration["colonnes"],
        array_keys($donnees)
    );

    // Vérifier les champs obligatoires
    foreach ($configuration["obligatoires"] as $colonne) {
        if (
            !array_key_exists($colonne, $donnees) ||
            $donnees[$colonne] === null
        ) {
            $pdo->rollBack();

            repondre(
                false,
                "Restauration impossible : champ obligatoire manquant (" .
                $colonne . ")."
            );
        }
    }

    // Vérifier que l'identifiant d'origine correspond
    $colonneId = $configuration["id"];

    if (
        (string) $donnees[$colonneId] !==
        (string) $archive["id_origine"]
    ) {
        $pdo->rollBack();

        repondre(
            false,
            "L'identifiant d'origine ne correspond pas aux données archivées."
        );
    }

    // Vérifier si l'enregistrement existe déjà
    $sqlExistence = "
        SELECT COUNT(*)
        FROM `$table`
        WHERE `$colonneId` = :id
    ";

    $requete = $pdo->prepare($sqlExistence);

    $requete->execute([
        ":id" => $archive["id_origine"]
    ]);

    if ((int) $requete->fetchColumn() > 0) {
        $pdo->rollBack();

        repondre(
            false,
            "Impossible de restaurer : cet enregistrement existe déjà."
        );
    }

    // Construire une insertion limitée aux colonnes autorisées
    $colonnes = array_values($colonnes);

    $nomsColonnes = implode(
        ", ",
        array_map(
            fn($colonne) => "`$colonne`",
            $colonnes
        )
    );

    $marqueurs = implode(
        ", ",
        array_map(
            fn($colonne) => ":" . $colonne,
            $colonnes
        )
    );

    $sqlInsertion = "
        INSERT INTO `$table` ($nomsColonnes)
        VALUES ($marqueurs)
    ";

    $parametres = [];

    foreach ($colonnes as $colonne) {
        $parametres[":" . $colonne] = $donnees[$colonne];
    }

    $requete = $pdo->prepare($sqlInsertion);
    $requete->execute($parametres);

    // Marquer l'archive comme restaurée
    $requete = $pdo->prepare("
        UPDATE archives
        SET statut = 'restauree',
            date_restauration = NOW()
        WHERE archive_id = :id
    ");

    $requete->execute([
        ":id" => $archiveId
    ]);

    $pdo->commit();

    repondre(
        true,
        "Donnée restaurée avec succès dans la table d'origine."
    );

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Erreur archives-action.php : " . $e->getMessage());

    // Une contrainte de clé étrangère ou d'unicité peut empêcher
    // la restauration : aucune donnée ne doit être partiellement insérée.
    repondre(
        false,
        "Opération impossible. Vérifie les contraintes et les données archivées.",
        409
    );

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Erreur archives-action.php : " . $e->getMessage());

    repondre(
        false,
        "Une erreur interne est survenue.",
        500
    );
}
