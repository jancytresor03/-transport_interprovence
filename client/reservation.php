<?php

require_once __DIR__ . "/../verification.php";
verifierAcces("client", "../login.html");

require_once __DIR__ . "/../database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================================
   FONCTION DE PROTECTION HTML
========================================= */

function h($valeur) {
    return htmlspecialchars(
        (string) $valeur,
        ENT_QUOTES,
        "UTF-8"
    );
}

/* =========================================
   INITIALISATION
========================================= */

$erreurPage = "";
$utilisateur = null;
$trajet = null;
$passagers = 0;
$siegesSelectionnes = [];

/* =========================================
   CHARGEMENT DES DONNEES
========================================= */

try {

    // 1. Vérifier le client connecté

    if (empty($_SESSION["user_id"])) {
        throw new Exception(
            "Votre session a expiré. Veuillez vous reconnecter."
        );
    }

    $sqlUtilisateur = "
        SELECT user_id, nom, prenom, email, telephone
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sqlUtilisateur);
    $stmt->execute([$_SESSION["user_id"]]);

    $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$utilisateur) {
        throw new Exception(
            "Impossible de retrouver votre compte."
        );
    }

    // 2. Récupérer le trajet

    $trajetId = trim($_GET["trajet"] ?? "");

    if ($trajetId === "") {
        throw new Exception(
            "Aucun trajet n'a été sélectionné."
        );
    }

    $sqlTrajet = "
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
        WHERE trajet_id = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sqlTrajet);
    $stmt->execute([$trajetId]);

    $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trajet) {
        throw new Exception(
            "Le trajet demandé n'existe pas."
        );
    }

    if ($trajet["statut"] !== "actif") {
        throw new Exception(
            "Ce trajet n'est plus disponible."
        );
    }

    // 3. Vérifier le nombre de passagers

    $passagers = filter_var(
        $_GET["passagers"] ?? null,
        FILTER_VALIDATE_INT
    );

    if (
        $passagers === false ||
        $passagers < 1 ||
        $passagers > 5
    ) {
        throw new Exception(
            "Le nombre de passagers est invalide."
        );
    }

    // 4. Récupérer les sièges sélectionnés

    $placesParam = trim($_GET["places"] ?? "");

    if ($placesParam === "") {
        throw new Exception(
            "Aucun siège n'a été sélectionné. Retournez à la sélection des places."
        );
    }

    $numeros = explode(",", $placesParam);

    // Vérifier le format des numéros

    foreach ($numeros as $numero) {
        if (
            $numero === "" ||
            !ctype_digit($numero)
        ) {
            throw new Exception(
                "La sélection contient un numéro de siège invalide."
            );
        }
    }

    $siegesSelectionnes = array_map(
        "intval",
        $numeros
    );

    // Éliminer les doublons en les refusant

    if (
        count($siegesSelectionnes) !==
        count(array_unique($siegesSelectionnes))
    ) {
        throw new Exception(
            "Un même siège ne peut pas être sélectionné plusieurs fois."
        );
    }

    // Le nombre de sièges doit correspondre aux passagers

    if (count($siegesSelectionnes) !== $passagers) {
        throw new Exception(
            "Le nombre de sièges doit correspondre au nombre de passagers."
        );
    }

    // Vérifier les limites de sièges

    foreach ($siegesSelectionnes as $numero) {
        if ($numero < 1 || $numero > 35) {
            throw new Exception(
                "Les numéros de sièges doivent être compris entre 1 et 35."
            );
        }
    }

    sort($siegesSelectionnes);

    // 5. Vérifier que les sièges existent et sont disponibles

    $placeholders = implode(
        ",",
        array_fill(
            0,
            count($siegesSelectionnes),
            "?"
        )
    );

    $sqlSieges = "
        SELECT numero_siege, statut
        FROM sieges_trajets
        WHERE trajet_id = ?
          AND numero_siege IN ($placeholders)
    ";

    $stmt = $pdo->prepare($sqlSieges);

    $stmt->execute(
        array_merge(
            [$trajet["trajet_id"]],
            $siegesSelectionnes
        )
    );

    $siegesBDD = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($siegesBDD) !== $passagers) {
        throw new Exception(
            "Certains sièges n'existent pas pour ce trajet."
        );
    }

    foreach ($siegesBDD as $siege) {
        if ($siege["statut"] !== "disponible") {
            throw new Exception(
                "Au moins un siège n'est plus disponible. "
                . "Veuillez retourner choisir d'autres sièges."
            );
        }
    }

} catch (Exception $e) {
    $erreurPage = $e->getMessage();
}

/* =========================================
   PREPARER LES DONNEES POUR JAVASCRIPT
========================================= */

$donneesReservation = [
    "erreur" => $erreurPage,

    "utilisateur" => $utilisateur ? [
        "nom" => $utilisateur["nom"],
        "prenom" => $utilisateur["prenom"],
        "email" => $utilisateur["email"],
        "telephone" => $utilisateur["telephone"]
    ] : null,

    "trajet" => $trajet ? [
        "id" => $trajet["trajet_id"],
        "depart" => $trajet["depart"],
        "destination" => $trajet["destination"],
        "date" => $trajet["date_depart"],
        "heure" => $trajet["heure_depart"],
        "prix" => (float) $trajet["prix"]
    ] : null,

    "passagers" => $passagers ?: 0,
    "places" => $siegesSelectionnes
];

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Réservation - Burundi Transport</title>

    <link rel="stylesheet" href="reservation.css">

</head>

<body>

    <!-- ================= HEADER ================= -->

    <header class="header">

        <div class="container header-content">

            <a href="client-home.php" class="logo">

                <div class="logo-icon">BT</div>

                <div class="logo-text">
                    <strong>Burundi</strong>
                    <span>Transport</span>
                </div>

            </a>

            <nav class="nav">

                <a href="client-home.php">Accueil</a>

                <a href="recherche.php">Rechercher</a>

                <a href="mes-reservations.php">
                    Mes réservations
                </a>
                <a href="chat.php">message</a>
            </nav>

            <div class="user-area">

                <button
                    type="button"
                    class="user-button"
                    id="userButton"
                    aria-expanded="false"
                >

                    <div class="avatar">
                        <?= h(
                            strtoupper(
                                substr(
                                    $utilisateur["prenom"] ?? "C",
                                    0,
                                    1
                                )
                            )
                        ) ?>
                    </div>

                    <span>
                        <?= h(
                            trim(
                                ($utilisateur["prenom"] ?? "") . " " .
                                ($utilisateur["nom"] ?? "")
                            )
                        ) ?>
                    </span>

                    <span class="arrow">⌄</span>

                </button>

                <div class="user-menu" id="userMenu">

                    <a href="profil.php">Mon profil</a>

                    <a href="mes-reservations.php">
                        Mes réservations
                    </a>

                    <button
                        type="button"
                        id="logoutButton"
                    >
                        Se déconnecter
                    </button>

                </div>

            </div>

        </div>

    </header>

    <!-- ================= CONTENU PRINCIPAL ================= -->

    <main>

        <section class="page-header">

            <div class="container">

                <span class="section-label">
                    DERNIÈRE ÉTAPE
                </span>

                <h1>Finalisez votre réservation</h1>

                <p>
                    Vérifiez votre trajet et renseignez les
                    informations nécessaires pour confirmer votre réservation.
                </p>

            </div>

        </section>

        <section class="reservation-section">

            <div class="container reservation-layout">

                <!-- ================= FORMULAIRE ================= -->

                <div class="form-card">

                    <div class="card-header">

                        <h2>Informations du passager</h2>

                        <p>
                            Remplissez les informations demandées.
                        </p>

                    </div>

                    <?php if ($erreurPage !== ""): ?>

                        <div
                            class="error-message"
                            id="pageError"
                            role="alert"
                        >
                            <?= h($erreurPage) ?>

                            <p>
                                <a href="trajets.php">
                                    Retour aux trajets
                                </a>
                            </p>
                        </div>

                    <?php else: ?>

                        <form id="reservationForm">

                            <div class="form-group">

                                <label for="nom">
                                    Nom <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    id="nom"
                                    name="nom"
                                    value="<?= h($utilisateur["nom"]) ?>"
                                    placeholder="Ex : Ndayizeye"
                                    autocomplete="family-name"
                                    required
                                    maxlength="100"
                                >

                                <small
                                    class="error-message"
                                    id="nomError"
                                ></small>

                            </div>

                            <div class="form-group">

                                <label for="prenom">
                                    Prénom <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    id="prenom"
                                    name="prenom"
                                    value="<?= h($utilisateur["prenom"]) ?>"
                                    placeholder="Ex : Jean"
                                    autocomplete="given-name"
                                    required
                                    maxlength="100"
                                >

                                <small
                                    class="error-message"
                                    id="prenomError"
                                ></small>

                            </div>

                            <div class="form-group">

                                <label for="telephone">
                                    Numéro de téléphone <span>*</span>
                                </label>

                                <input
                                    type="tel"
                                    id="telephone"
                                    name="telephone"
                                    value="<?= h($utilisateur["telephone"]) ?>"
                                    placeholder="Ex : 79 12 34 56"
                                    autocomplete="tel"
                                    required
                                    maxlength="30"
                                >

                                <small
                                    class="error-message"
                                    id="telephoneError"
                                ></small>

                            </div>

                            <div class="form-group">

                                <label for="email">
                                    Adresse e-mail
                                </label>

                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="<?= h($utilisateur["email"]) ?>"
                                    placeholder="Ex : jean@email.com"
                                    autocomplete="email"
                                    maxlength="150"
                                >

                                <small
                                    class="error-message"
                                    id="emailError"
                                ></small>

                            </div>

                            <div class="form-group">

                                <label for="identite">
                                    Type de pièce d'identité <span>*</span>
                                </label>

                                <select
                                    id="identite"
                                    name="identite"
                                    required
                                >

                                    <option value="">
                                        Sélectionner
                                    </option>

                                    <option value="CNI">
                                        Carte nationale d'identité
                                    </option>

                                    <option value="passeport">
                                        Passeport
                                    </option>

                                    <option value="autre">
                                        Autre
                                    </option>

                                </select>

                                <small
                                    class="error-message"
                                    id="identiteError"
                                ></small>

                            </div>

                            <div class="form-group">

                                <label for="numeroIdentite">
                                    Numéro de pièce d'identité <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    id="numeroIdentite"
                                    name="numeroIdentite"
                                    placeholder="Ex : 123456789"
                                    required
                                    maxlength="100"
                                >

                                <small
                                    class="error-message"
                                    id="numeroIdentiteError"
                                ></small>

                            </div>

                            <div class="form-group full-width">

                                <label for="remarque">
                                    Remarque
                                </label>

                                <textarea
                                    id="remarque"
                                    name="remarque"
                                    rows="4"
                                    maxlength="1000"
                                    placeholder="Une remarque particulière ? (facultatif)"
                                ></textarea>

                            </div>

                            <div class="conditions">

                                <label>

                                    <input
                                        type="checkbox"
                                        id="conditions"
										name="conditions"
                                        value="1"
                                        required
                                    >

                                    <span>
                                        J'accepte les conditions de réservation
                                        et confirme que les informations fournies
                                        sont correctes.
                                    </span>

                                </label>

                                <small
                                    class="error-message"
                                    id="conditionsError"
                                ></small>

                            </div>

                            <div class="form-actions">

                                <button
                                    type="button"
                                    class="btn-secondary"
                                    id="backButton"
                                >
                                    Retour
                                </button>

                                <button
                                    type="submit"
                                    class="btn-primary"
                                >
                                    Confirmer la réservation
                                </button>

                            </div>

                        </form>

                    <?php endif; ?>

                </div>

                <!-- ================= RESUME DU TRAJET ================= -->

                <aside class="summary-card">

                    <h2>Résumé du trajet</h2>

                    <div class="company">

                        <div class="company-logo">BT</div>

                        <div>

                            <strong>Burundi Transport</strong>

                            <span>
                                Transport interprovincial
                            </span>

                        </div>

                    </div>

                    <div class="route-summary">

                        <div class="route-point">

                            <span>Départ</span>

                            <strong id="depart">
                                <?= h($trajet["depart"] ?? "-") ?>
                            </strong>

                            <small id="heure">
                                <?= h($trajet["heure_depart"] ?? "-") ?>
                            </small>

                        </div>

                        <div class="route-line">→</div>

                        <div class="route-point">

                            <span>Destination</span>

                            <strong id="destination">
                                <?= h($trajet["destination"] ?? "-") ?>
                            </strong>

                        </div>

                    </div>

                    <div class="summary-details">

                        <div>

                            <span>Date</span>

                            <strong id="date">
                                <?= h($trajet["date_depart"] ?? "-") ?>
                            </strong>

                        </div>

                        <div>

                            <span>Passagers</span>

                            <strong id="passagers">
                                <?= h($passagers ?: "-") ?>
                            </strong>

                        </div>

                        <div>

                            <span>Places</span>

                            <strong id="places">
                                <?= h(implode(", ", $siegesSelectionnes) ?: "-") ?>
                            </strong>

                        </div>

                    </div>

                    <div class="separator"></div>

                    <div class="price-line">

                        <span>Prix par place</span>

                        <strong id="prixUnitaire">
                            <?= $trajet
                                ? number_format((float)$trajet["prix"], 0, ",", " ") . " FBu"
                                : "-" ?>
                        </strong>

                    </div>

                    <div class="total-line">

                        <span>Total</span>

                        <strong id="total">
                            <?= $trajet
                                ? number_format(
                                    (float)$trajet["prix"] * $passagers,
                                    0,
                                    ",",
                                    " "
                                ) . " FBu"
                                : "-" ?>
                        </strong>

                    </div>

                    <div class="secure-message">

                        <span class="secure-icon">✓</span>

                        <p>
                            Vos informations sont utilisées pour traiter
                            votre réservation.
                        </p>

                    </div>

                </aside>

            </div>

        </section>

    </main>

    <!-- ================= FOOTER ================= -->

    <footer class="footer">

        <div class="container footer-content">

            <div>

                <strong>Burundi Transport</strong>

                <p>
                    Votre solution simple pour réserver
                    vos trajets interprovinciaux.
                </p>

            </div>

            <div class="footer-links">

                <a href="client-home.php">Accueil</a>

                <a href="mes-reservations.php">
                    Mes réservations
                </a>

                <a href="profil.php">Mon profil</a>

            </div>

        </div>

        <div class="footer-bottom">
            © 2026 Burundi Transport. Tous droits réservés.
        </div>

    </footer>

    <script>
        window.reservationPageData = <?= json_encode(
            $donneesReservation,
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_AMP |
            JSON_HEX_QUOT
        ) ?>;
    </script>

    <script src="reservation.js"></script>
	<script src="deconnexion-auto.js"></script>

</body>

</html>
