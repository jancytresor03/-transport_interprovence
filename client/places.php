
<?php

/* ================= VERIFICATION CLIENT ================= */

require_once __DIR__ . "/../verification.php";
verifierAcces("client", "../login.html");

require_once __DIR__ . "/../database.php";


/* ================= FONCTIONS ================= */

function h($valeur)
{
    return htmlspecialchars(
        (string) $valeur,
        ENT_QUOTES,
        "UTF-8"
    );
}


/* ================= UTILISATEUR CONNECTE ================= */

$userId = $_SESSION["user_id"] ?? null;

$nomUtilisateur = "Client";
$initiales = "C";

if ($userId) {
    $sqlUser = "
        SELECT prenom, nom
        FROM users
        WHERE user_id = :user_id
        LIMIT 1
    ";

    $stmtUser = $pdo->prepare($sqlUser);

    $stmtUser->execute([
        "user_id" => $userId
    ]);

    $utilisateur = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($utilisateur) {
        $prenom = trim($utilisateur["prenom"] ?? "");
        $nom = trim($utilisateur["nom"] ?? "");

        $nomUtilisateur = trim($prenom . " " . $nom);

        if ($nomUtilisateur === "") {
            $nomUtilisateur = "Client";
        }

        $initiales = strtoupper(
            substr($prenom, 0, 1) .
            substr($nom, 0, 1)
        );

        if ($initiales === "") {
            $initiales = "C";
        }
    }
}


/* ================= PARAMETRES URL ================= */

$trajetId = trim($_GET["trajet"] ?? "");

$passagers = filter_var(
    $_GET["passagers"] ?? 1,
    FILTER_VALIDATE_INT
);

if ($passagers === false || $passagers < 1 || $passagers > 5) {
    $passagers = 1;
}


/* ================= DONNEES DU TRAJET ================= */

$trajet = null;
$sieges = [];
$erreurPage = "";

try {

    if ($trajetId === "") {
        throw new RuntimeException(
            "Aucun trajet n'a été sélectionné. Veuillez choisir un trajet."
        );
    }

    /*
       Récupérer le trajet depuis MySQL.
       Le prix et les autres informations ne viennent
       pas des paramètres transmis par le navigateur.
    */

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
        WHERE trajet_id = :trajet_id
        LIMIT 1
    ";

    $stmtTrajet = $pdo->prepare($sqlTrajet);

    $stmtTrajet->execute([
        "trajet_id" => $trajetId
    ]);

    $trajet = $stmtTrajet->fetch(PDO::FETCH_ASSOC);

    if (!$trajet) {
        throw new RuntimeException(
            "Le trajet demandé n'existe pas."
        );
    }

    if ($trajet["statut"] !== "actif") {
        throw new RuntimeException(
            "Ce trajet n'est plus disponible à la réservation."
        );
    }

    if ($trajet["date_depart"] < date("Y-m-d")) {
        throw new RuntimeException(
            "La date de départ de ce trajet est déjà passée."
        );
    }


    /*
       Créer les sièges manquants de 1 à 35.
       Les sièges déjà présents ne sont pas modifiés.
    */

    $sqlInsertion = "
        INSERT IGNORE INTO sieges_trajets (
            trajet_id,
            numero_siege,
            statut
        )
        VALUES (
            :trajet_id,
            :numero_siege,
            'disponible'
        )
    ";

    $stmtInsertion = $pdo->prepare($sqlInsertion);

    for ($numero = 1; $numero <= 35; $numero++) {
        $stmtInsertion->execute([
            "trajet_id" => $trajet["trajet_id"],
            "numero_siege" => $numero
        ]);
    }


    /*
       Récupérer l'état actuel des 35 sièges.
    */

    $sqlSieges = "
        SELECT
            numero_siege,
            statut,
            reservation_id
        FROM sieges_trajets
        WHERE trajet_id = :trajet_id
        ORDER BY numero_siege ASC
    ";

    $stmtSieges = $pdo->prepare($sqlSieges);

    $stmtSieges->execute([
        "trajet_id" => $trajet["trajet_id"]
    ]);

    $sieges = $stmtSieges->fetchAll(PDO::FETCH_ASSOC);


    /*
       Si le nombre de passagers demandé dépasse
       le nombre de sièges actuellement disponibles,
       afficher un message explicite.
    */

    $nombreSiegesDisponibles = 0;

    foreach ($sieges as $siege) {
        if ($siege["statut"] === "disponible") {
            $nombreSiegesDisponibles++;
        }
    }

    if ($nombreSiegesDisponibles < $passagers) {
        throw new RuntimeException(
            "Il n'y a pas assez de sièges disponibles pour le nombre de passagers demandé."
        );
    }

} catch (Throwable $e) {

    $trajet = null;
    $sieges = [];

    $erreurPage = $e instanceof RuntimeException
        ? $e->getMessage()
        : "Une erreur est survenue lors du chargement du trajet. Veuillez réessayer.";

    if (!($e instanceof RuntimeException)) {
        error_log("Erreur places.php : " . $e->getMessage());
    }
}


/* ================= DONNEES POUR JAVASCRIPT ================= */

$donneesPage = null;

if ($trajet !== null) {

    $donneesPage = [
        "trajetId" => $trajet["trajet_id"],
        "depart" => $trajet["depart"],
        "destination" => $trajet["destination"],
        "date" => $trajet["date_depart"],
        "heure" => substr($trajet["heure_depart"], 0, 5),
        "prix" => (float) $trajet["prix"],
        "passagers" => (int) $passagers,
        "sieges" => array_map(
            function ($siege) {
                return [
                    "numero" => (int) $siege["numero_siege"],
                    "statut" => $siege["statut"]
                ];
            },
            $sieges
        )
    ];
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Choisir une place - Burundi Transport</title>

    <link rel="stylesheet" href="places.css">
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
                    aria-controls="userMenu"
                >

                    <div class="avatar">
                        <?= h($initiales) ?>
                    </div>

                    <span><?= h($nomUtilisateur) ?></span>

                    <span class="arrow">⌄</span>

                </button>

                <div class="user-menu" id="userMenu">

                    <a href="profil.php">Mon profil</a>

                    <a href="mes-reservations.php">
                        Mes réservations
                    </a>

                    <button type="button" id="logoutButton">
                        Se déconnecter
                    </button>

                </div>

            </div>

        </div>

    </header>


    <!-- ================= MAIN ================= -->

    <main>

        <section class="page-header">

            <div class="container">

                <span class="section-label">
                    RÉSERVATION
                </span>

                <h1>Choisissez votre place</h1>

                <p>
                    Sélectionnez les places que vous souhaitez
                    réserver dans le bus.
                </p>

            </div>

        </section>


        <!-- MESSAGE D'ERREUR -->

        <section class="container">

            <div
                id="pageMessage"
                role="alert"
                <?= $erreurPage === "" ? "hidden" : "" ?>
            >
                <?= h($erreurPage) ?>

                <?php if ($erreurPage !== ""): ?>

                    <p>
                        <a href="trajets.php">
                            Retourner aux trajets
                        </a>
                    </p>

                <?php endif; ?>

            </div>

        </section>


        <!-- ================= TRAJET ================= -->

        <section class="trip-summary">

            <div class="container">

                <div class="trip-card">

                    <div class="trip-main">

                        <div class="trip-company">

                            <div class="company-logo">BT</div>

                            <div>
                                <strong>Burundi Transport</strong>

                                <span>
                                    Transport interprovincial
                                </span>
                            </div>

                        </div>

                        <div class="route">

                            <div class="route-point">

                                <strong id="depart">-</strong>

                                <span id="heureDepart">-</span>

                            </div>

                            <div class="route-line">
                                <span>→</span>
                            </div>

                            <div class="route-point">

                                <strong id="destination">-</strong>

                                <span>Arrivée</span>

                            </div>

                        </div>

                        <div class="trip-details">

                            <div>
                                <span>Date</span>
                                <strong id="date">-</strong>
                            </div>

                            <div>
                                <span>Passagers</span>
                                <strong id="passagers">-</strong>
                            </div>

                            <div>
                                <span>Prix</span>
                                <strong id="prix">-</strong>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        <!-- ================= SELECTION ================= -->

        <section class="seat-section">

            <div class="container seat-layout">

                <!-- BUS -->

                <div class="bus-container">

                    <div class="bus-header">

                        <div class="bus-front">
                            AVANT DU BUS
                        </div>

                    </div>

                    <div
                        class="seats-container"
                        id="seatsContainer"
                    ></div>

                    <div class="bus-back">
                        ARRIÈRE
                    </div>

                </div>


                <!-- RESUME -->

                <aside class="selection-card">

                    <h2>Votre sélection</h2>

                    <div class="selection-info">

                        <span>Places sélectionnées</span>

                        <strong id="selectedSeats">Aucune</strong>

                    </div>

                    <div class="selection-info">

                        <span>Nombre de passagers</span>

                        <strong id="passengerNumber">0</strong>

                    </div>

                    <div class="separator"></div>

                    <div class="price-row">

                        <span>Prix par place</span>

                        <strong id="pricePerSeat">0 FBu</strong>

                    </div>

                    <div class="total-row">

                        <span>Total</span>

                        <strong id="totalPrice">0 FBu</strong>

                    </div>

                    <button
                        type="button"
                        class="btn-primary"
                        id="continueButton"
                        disabled
                    >
                        Continuer
                    </button>

                    <button
                        type="button"
                        class="btn-secondary"
                        id="backButton"
                    >
                        Retour
                    </button>

                    <div class="legend">

                        <div class="legend-item">
                            <span class="legend-seat available-seat"></span>
                            Disponible
                        </div>

                        <div class="legend-item">
                            <span class="legend-seat selected-seat"></span>
                            Sélectionnée
                        </div>

                        <div class="legend-item">
                            <span class="legend-seat occupied-seat"></span>
                            Occupée
                        </div>

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


    <!-- Données récupérées depuis MySQL -->

    <script>
        window.placesPageData = <?= json_encode(
            $donneesPage,
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_QUOT |
            JSON_HEX_AMP |
            JSON_UNESCAPED_UNICODE
        ) ?>;

        window.placesPageError = <?= json_encode(
            $erreurPage,
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_QUOT |
            JSON_HEX_AMP |
            JSON_UNESCAPED_UNICODE
        ) ?>;
    </script>

    <script src="places.js"></script>
	<script src="deconnexion-auto.js"></script>

</body>

</html>
