
<?php

/* =========================================
   1. SECURITE ET CONNEXION
========================================= */

require_once __DIR__ . "/../verification.php";
verifierAcces("client", "../login.html");

require_once __DIR__ . "/../database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: trajets.php");
    exit;
}

/* =========================================
   2. AFFICHER UNE ERREUR
========================================= */

function afficherErreur($message, $trajetId = "", $passagers = 1)
{
    http_response_code(400);

    $urlRetour = "trajets.php";

    if ($trajetId !== "") {
        $urlRetour = "places.php?" . http_build_query([
            "trajet" => $trajetId,
            "passagers" => max(1, (int) $passagers)
        ]);
    }

    $messageHTML = htmlspecialchars(
        $message,
        ENT_QUOTES,
        "UTF-8"
    );

    $urlHTML = htmlspecialchars(
        $urlRetour,
        ENT_QUOTES,
        "UTF-8"
    );

    echo '<!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Erreur de réservation - Burundi Transport</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f3f6f9;
                margin: 0;
                padding: 40px 16px;
                color: #1e2a36;
            }
            .carte {
                max-width: 560px;
                margin: 50px auto;
                padding: 30px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 4px 18px rgba(0,0,0,.08);
                text-align: center;
            }
            h1 { color: #b42318; }
            a {
                display: inline-block;
                margin-top: 15px;
                padding: 12px 18px;
                background: #2c6e9b;
                color: white;
                text-decoration: none;
                border-radius: 6px;
            }
        </style>
    </head>
    <body>
        <div class="carte">
            <h1>Réservation impossible</h1>
            <p>' . $messageHTML . '</p>
            <a href="' . $urlHTML . '">Retour</a>
        </div>
    </body>
    </html>';

    exit;
}

/* =========================================
   3. RECUPERER LES DONNEES
========================================= */

$userId = $_SESSION["user_id"] ?? "";

$trajetId = trim($_POST["trajet"] ?? "");

$passagers = filter_var(
    $_POST["passagers"] ?? null,
    FILTER_VALIDATE_INT
);

$placesTexte = trim($_POST["places"] ?? "");

$nom = trim($_POST["nom"] ?? "");
$prenom = trim($_POST["prenom"] ?? "");
$telephone = trim($_POST["telephone"] ?? "");
$email = trim($_POST["email"] ?? "");

$identite = strtoupper(trim($_POST["identite"] ?? ""));
$numeroIdentite = trim($_POST["numeroIdentite"] ?? "");
$remarque = trim($_POST["remarque"] ?? "");

/* =========================================
   4. VALIDATION
========================================= */

if ($userId === "") {
    header("Location: ../login.html");
    exit;
}

if (
    $trajetId === "" ||
    $passagers === false ||
    $passagers < 1 ||
    $passagers > 5
) {
    afficherErreur("Les informations du trajet sont invalides.");
}

if (
    $nom === "" ||
    $prenom === "" ||
    $telephone === "" ||
    $email === "" ||
    $identite === "" ||
    $numeroIdentite === ""
) {
    afficherErreur(
        "Veuillez remplir tous les champs obligatoires.",
        $trajetId,
        $passagers
    );
}

if (!in_array($identite, ["CNI", "PASSEPORT", "AUTRE"], true)) {
    afficherErreur(
        "Le type de pièce d'identité est invalide.",
        $trajetId,
        $passagers
    );
}

if (mb_strlen($nom) > 100 || mb_strlen($prenom) > 100) {
    afficherErreur(
        "Le nom ou le prénom est trop long.",
        $trajetId,
        $passagers
    );
}

if (mb_strlen($telephone) > 30) {
    afficherErreur(
        "Le numéro de téléphone est trop long.",
        $trajetId,
        $passagers
    );
}

if (
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    mb_strlen($email) > 150
) {
    afficherErreur(
        "L'adresse e-mail est invalide.",
        $trajetId,
        $passagers
    );
}

if (
    mb_strlen($numeroIdentite) > 100 ||
    mb_strlen($remarque) > 1000
) {
    afficherErreur(
        "Le numéro d'identité ou la remarque est trop long.",
        $trajetId,
        $passagers
    );
}

if (($_POST["conditions"] ?? "") !== "1") {
    afficherErreur(
        "Vous devez accepter les conditions de réservation.",
        $trajetId,
        $passagers
    );
}

/* =========================================
   5. VERIFIER LES SIEGES
========================================= */

if ($placesTexte === "") {
    afficherErreur(
        "Aucun siège n'a été sélectionné.",
        $trajetId,
        $passagers
    );
}

$numerosTexte = explode(",", $placesTexte);

foreach ($numerosTexte as $numero) {
    if ($numero === "" || !ctype_digit($numero)) {
        afficherErreur(
            "Les numéros de sièges sont invalides.",
            $trajetId,
            $passagers
        );
    }
}

$sieges = array_map("intval", $numerosTexte);

if (
    count($sieges) !== $passagers ||
    count($sieges) !== count(array_unique($sieges))
) {
    afficherErreur(
        "Le nombre de sièges ne correspond pas au nombre de passagers.",
        $trajetId,
        $passagers
    );
}

foreach ($sieges as $numero) {
    if ($numero < 1 || $numero > 35) {
        afficherErreur(
            "Un numéro de siège est invalide.",
            $trajetId,
            $passagers
        );
    }
}

sort($sieges);

/* =========================================
   6. TRANSACTION DE RESERVATION
========================================= */

try {
    $pdo->beginTransaction();

    /* Vérifier le trajet et le verrouiller */

    $sqlTrajet = "
        SELECT trajet_id, date_depart, heure_depart, prix, statut
        FROM trajets
        WHERE trajet_id = ?
        FOR UPDATE
    ";

    $stmt = $pdo->prepare($sqlTrajet);
    $stmt->execute([$trajetId]);

    $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trajet) {
        throw new Exception("Le trajet demandé n'existe pas.");
    }

    if ($trajet["statut"] !== "actif") {
        throw new Exception("Ce trajet n'est plus disponible.");
    }

    $departDateHeure = strtotime(
        $trajet["date_depart"] . " " . $trajet["heure_depart"]
    );

    if ($departDateHeure === false || $departDateHeure <= time()) {
        throw new Exception("La date ou l'heure de départ est passée.");
    }

    /* Calculer le montant */

    $prixUnitaire = (float) $trajet["prix"];

    if ($prixUnitaire <= 0) {
        throw new Exception("Le prix du trajet est invalide.");
    }

    $montantTotal = round($prixUnitaire * $passagers, 2);

    /* Vérifier le client */

    $stmt = $pdo->prepare("
        SELECT user_id
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmt->execute([$userId]);

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception("Votre compte utilisateur est introuvable.");
    }

    /* Verrouiller les sièges sélectionnés */

    $placeholders = implode(",", array_fill(0, count($sieges), "?"));

    $sqlSieges = "
        SELECT numero_siege, statut, reservation_id
        FROM sieges_trajets
        WHERE trajet_id = ?
          AND numero_siege IN ($placeholders)
        ORDER BY numero_siege
        FOR UPDATE
    ";

    $stmt = $pdo->prepare($sqlSieges);
    $stmt->execute(array_merge([$trajetId], $sieges));

    $siegesBDD = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($siegesBDD) !== $passagers) {
        throw new Exception(
            "Certains sièges n'existent pas pour ce trajet."
        );
    }

    foreach ($siegesBDD as $siege) {
        if (
            $siege["statut"] !== "disponible" ||
            $siege["reservation_id"] !== null
        ) {
            throw new Exception(
                "Un siège sélectionné n'est plus disponible. "
                . "Veuillez choisir d'autres sièges."
            );
        }
    }

    /* =========================================
       7. INSERER LA RESERVATION
       Le trigger génère les identifiants.
    ========================================= */

    // Réinitialiser les variables de cette session MySQL.
    $pdo->exec("SET @generated_reservation_id = NULL");
    $pdo->exec("SET @generated_reference = NULL");

    $sqlReservation = "
        INSERT INTO reservations (
            reservation_id,
            reference,
            user_id,
            trajet_id,
            nom_passager,
            prenom_passager,
            telephone_passager,
            email_passager,
            type_identite,
            numero_identite,
            nombre_passagers,
            montant_total,
            statut,
            remarque,
            date_expiration
        ) VALUES (
            '', '', ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, 'en_attente', ?, ?
        )
    ";

    $stmt = $pdo->prepare($sqlReservation);

    $dateExpiration = date(
        "Y-m-d H:i:s",
        time() + 30 * 60
    );

    $stmt->execute([
        $userId,
        $trajetId,
        $nom,
        $prenom,
        $telephone,
        $email,
        $identite,
        $numeroIdentite,
        $passagers,
        $montantTotal,
        $remarque !== "" ? $remarque : null,
        $dateExpiration
    ]);

    /* Récupérer les valeurs exactes générées par le trigger */

    $stmtIds = $pdo->query("
        SELECT
            @generated_reservation_id AS reservation_id,
            @generated_reference AS reference
    ");

    $identifiants = $stmtIds->fetch(PDO::FETCH_ASSOC);

    $reservationId = $identifiants["reservation_id"] ?? null;
    $reference = $identifiants["reference"] ?? null;

    if (
        !$reservationId ||
        !$reference
    ) {
        throw new Exception(
            "Le trigger MySQL n'a pas retourné les identifiants."
        );
    }

    error_log("ID réservation généré : " . $reservationId);
    error_log("Référence générée : " . $reference);

    /* =========================================
       8. ASSOCIER LES SIEGES
    ========================================= */

    $sqlUpdateSiege = "
        UPDATE sieges_trajets
        SET statut = 'reserve',
            reservation_id = ?
        WHERE trajet_id = ?
          AND numero_siege = ?
          AND statut = 'disponible'
          AND reservation_id IS NULL
    ";

    $stmtUpdateSiege = $pdo->prepare($sqlUpdateSiege);

    foreach ($sieges as $numero) {
        $stmtUpdateSiege->execute([
            $reservationId,
            $trajetId,
            $numero
        ]);

        if ($stmtUpdateSiege->rowCount() !== 1) {
            throw new Exception(
                "Le siège numéro " . $numero
                . " n'a pas pu être réservé. Veuillez recommencer."
            );
        }
    }

    /* =========================================
       9. VALIDER LA TRANSACTION
    ========================================= */

    $pdo->commit();

    header(
        "Location: confirmation.php?"
        . http_build_query(["reference" => $reference])
    );

    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        "Erreur reservation-traitement.php : " . $e->getMessage()
    );

    if ($e instanceof PDOException) {
        $message = "Une erreur technique est survenue. "
            . "La réservation n'a pas été enregistrée.";
    } else {
        $message = $e->getMessage();
    }

    afficherErreur(
        $message,
        $trajetId,
        $passagers ?: 1
    );
}
?>
