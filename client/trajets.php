
<?php

require_once __DIR__ . "/../verification.php";
verifierAcces("client", "../login.html");

require_once __DIR__ . "/../database.php";

/* ================= FONCTION SECURITE ================= */

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

$prenom = "Client";
$nom = "";
$initiales = "C";

if ($userId) {
    $stmtUser = $pdo->prepare("
        SELECT prenom, nom
        FROM users
        WHERE user_id = :user_id
        LIMIT 1
    ");

    $stmtUser->execute([
        "user_id" => $userId
    ]);

    $utilisateur = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($utilisateur) {
        $prenom = $utilisateur["prenom"] ?? "Client";
        $nom = $utilisateur["nom"] ?? "";

        $initiales = strtoupper(
            mb_substr($prenom, 0, 1) .
            mb_substr($nom, 0, 1)
        );
    }
}

/* ================= CRITERES DE RECHERCHE ================= */

$depart = trim($_GET["depart"] ?? "");
$destination = trim($_GET["destination"] ?? "");
$date = trim($_GET["date"] ?? "");

$passagers = filter_var(
    $_GET["passagers"] ?? 1,
    FILTER_VALIDATE_INT
);

if (
    $passagers === false ||
    $passagers < 1 ||
    $passagers > 5
) {
    $passagers = 1;
}

/* Vérifier le format réel de la date */

$dateValide = false;

if ($date !== "") {
    $dateObj = DateTime::createFromFormat("!Y-m-d", $date);

    $dateValide = $dateObj
        && $dateObj->format("Y-m-d") === $date;
}

/* ================= RECUPERATION DES TRAJETS ================= */

$trajets = [];
$erreurChargement = false;

try {
    $sql = "
        SELECT
            t.trajet_id,
            t.depart,
            t.destination,
            t.date_depart,
            t.heure_depart,
            t.prix,
            t.nombre_places,

            (
                t.nombre_places -
                COALESCE(SUM(r.nombre_passagers), 0)
            ) AS places_disponibles

        FROM trajets t

        LEFT JOIN reservations r
            ON r.trajet_id = t.trajet_id
            AND (
                r.statut = 'confirmee'
                OR (
                    r.statut = 'en_attente'
                    AND (
                        r.date_expiration IS NULL
                        OR r.date_expiration > NOW()
                    )
                )
            )

        WHERE t.statut = 'actif'
          AND t.date_depart >= CURDATE()
    ";

    $parametres = [];

    if ($depart !== "") {
        $sql .= " AND t.depart = :depart";
        $parametres["depart"] = $depart;
    }

    if ($destination !== "") {
        $sql .= " AND t.destination = :destination";
        $parametres["destination"] = $destination;
    }

    if ($date !== "" && $dateValide) {
        $sql .= " AND t.date_depart = :date_depart";
        $parametres["date_depart"] = $date;
    }

    $sql .= "
        GROUP BY
            t.trajet_id,
            t.depart,
            t.destination,
            t.date_depart,
            t.heure_depart,
            t.prix,
            t.nombre_places

        HAVING places_disponibles >= :passagers

        ORDER BY
            t.date_depart ASC,
            t.heure_depart ASC
    ";

    $parametres["passagers"] = $passagers;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametres);

    $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($resultats as $trajet) {
        $trajets[] = [
            "id" => $trajet["trajet_id"],
            "depart" => $trajet["depart"],
            "destination" => $trajet["destination"],
            "dateDepart" => $trajet["date_depart"],
            "heureDepart" => substr(
                $trajet["heure_depart"],
                0,
                5
            ),
            "prix" => (float) $trajet["prix"],
            "places" => (int) $trajet["places_disponibles"]
        ];
    }

} catch (PDOException $e) {
    error_log(
        "Erreur lors du chargement des trajets : " .
        $e->getMessage()
    );

    $erreurChargement = true;
}

/* ================= LIENS DE RECHERCHE ================= */

$paramsRecherche = http_build_query([
    "depart" => $depart,
    "destination" => $destination,
    "date" => $date,
    "passagers" => $passagers
]);

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Trajets disponibles - Burundi Transport</title>

    <link rel="stylesheet" href="trajets.css">
</head>

<body>

    <!-- HEADER -->

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
                <a href="mes-reservations.php">Mes réservations</a>
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
                        <?= h($initiales) ?>
                    </div>

                    <span><?= h(trim($prenom . " " . $nom)) ?></span>
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

    <main>

        <!-- TITRE -->

        <section class="page-header">
            <div class="container">

                <span class="section-label">RÉSULTATS</span>

                <h1>Trajets disponibles</h1>

                <p>
                    Choisissez le trajet qui vous convient
                    pour continuer votre réservation.
                </p>

            </div>
        </section>

        <!-- RESUME DE LA RECHERCHE -->

        <section class="search-summary">
            <div class="container">

                <div class="summary-card">

                    <div class="summary-item">
                        <span class="summary-label">Départ</span>
                        <strong id="summaryDepart">-</strong>
                    </div>

                    <div class="route-arrow">→</div>

                    <div class="summary-item">
                        <span class="summary-label">Destination</span>
                        <strong id="summaryDestination">-</strong>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">Date</span>
                        <strong id="summaryDate">-</strong>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">Passagers</span>
                        <strong id="summaryPassagers">-</strong>
                    </div>

                    <button
                        type="button"
                        class="btn-modify"
                        id="modifySearch"
                    >
                        Modifier
                    </button>

                </div>
            </div>
        </section>

        <!-- RESULTATS -->

        <section class="results-section">
            <div class="container">

                <div class="results-top">

                    <div>
                        <h2>Les trajets</h2>

                        <p id="resultCount">
                            Chargement des trajets...
                        </p>
                    </div>

                    <div class="sort-container">
                        <label for="sortSelect">Trier par</label>

                        <select id="sortSelect">
                            <option value="departure">
                                Heure de départ
                            </option>

                            <option value="price-asc">
                                Prix : moins cher
                            </option>

                            <option value="price-desc">
                                Prix : plus cher
                            </option>
                        </select>
                    </div>

                </div>

                <!-- FILTRES -->

                <div class="filters">

                    <div class="filter-title">
                        Filtrer les trajets
                    </div>

                    <label class="check-filter">
                        <input type="checkbox" id="filterMorning">
                        Matin
                    </label>

                    <label class="check-filter">
                        <input type="checkbox" id="filterAfternoon">
                        Après-midi
                    </label>

                    <label class="check-filter">
                        <input type="checkbox" id="filterEvening">
                        Soir
                    </label>

                </div>

                <!-- MESSAGE D'ERREUR -->

                <?php if ($erreurChargement): ?>
                    <p class="error-message">
                        Impossible de charger les trajets pour le moment.
                        Veuillez réessayer plus tard.
                    </p>
                <?php endif; ?>

                <!-- LISTE DES TRAJETS -->

                <div
                    class="trips-list"
                    id="tripsList"
                    aria-live="polite"
                ></div>

                <!-- AUCUN RESULTAT -->

                <div
                    class="empty-state"
                    id="emptyState"
                    style="display: none;"
                >
                    <div class="empty-icon">🚌</div>

                    <h3>Aucun trajet disponible</h3>

                    <p>
                        Aucun trajet ne correspond à votre recherche.
                        Essayez de modifier vos critères.
                    </p>

                    <button
                        type="button"
                        class="btn-primary"
                        id="newSearchButton"
                    >
                        Nouvelle recherche
                    </button>
                </div>

            </div>
        </section>

    </main>

    <!-- FOOTER -->

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
                <a href="mes-reservations.php">Mes réservations</a>
                <a href="profil.php">Mon profil</a>
            </div>

        </div>

        <div class="footer-bottom">
            © 2026 Burundi Transport. Tous droits réservés.
        </div>

    </footer>

    <!-- DONNEES MYSQL TRANSMISES AU JAVASCRIPT -->

    <script>
        window.trajetsDepuisPHP = <?= json_encode(
            $trajets,
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_QUOT |
            JSON_HEX_AMP
        ) ?>;

        window.rechercheDepuisPHP = <?= json_encode(
            [
                "depart" => $depart,
                "destination" => $destination,
                "date" => $date,
                "passagers" => $passagers,
                "erreur" => $erreurChargement
            ],
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_QUOT |
            JSON_HEX_AMP
        ) ?>;
    </script>

    <script src="trajets.js"></script>
	<script src="deconnexion-auto.js"></script>

</body>
</html>
