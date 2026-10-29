
<?php

require_once __DIR__ . "/../verification.php";
verifierAcces("client", "../login.html");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../database.php";

/* =========================
   FONCTIONS
========================= */

function h($valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, "UTF-8");
}

function montantBIF($montant): string
{
    return number_format((float) $montant, 0, ",", " ") . " BIF";
}

function dateFr($date): string
{
    if (empty($date)) {
        return "-";
    }

    $timestamp = strtotime($date);

    return $timestamp !== false
        ? date("d/m/Y", $timestamp)
        : "-";
}

function heureFr($heure): string
{
    return !empty($heure)
        ? substr((string) $heure, 0, 5)
        : "-";
}

/* =========================
   CLIENT CONNECTE
========================= */

$userId = $_SESSION["user_id"] ?? null;

if (!$userId) {
    header("Location: ../login.html");
    exit;
}

/* =========================
   INITIALISATION
========================= */

$reservations = [];
$erreur = "";

$totalReservations = 0;
$confirmedReservations = 0;
$pendingReservations = 0;
$cancelledReservations = 0;
$expiredReservations = 0;

/* =========================
   RECUPERATION DES RESERVATIONS
========================= */

try {

    $sql = "
        SELECT
            r.reservation_id,
            r.reference,
            r.user_id,
            r.trajet_id,
            r.nombre_passagers,
            r.montant_total,
            r.statut,
            r.date_creation,

            t.depart,
            t.destination,
            t.date_depart,
            t.heure_depart,
            t.prix,

            (
                SELECT GROUP_CONCAT(
                    st.numero_siege
                    ORDER BY st.numero_siege ASC
                    SEPARATOR ', '
                )
                FROM sieges_trajets st
                WHERE st.reservation_id = r.reservation_id
                  AND st.trajet_id = r.trajet_id
            ) AS places

        FROM reservations r

        INNER JOIN trajets t
            ON t.trajet_id = r.trajet_id

        WHERE r.user_id = :user_id

        ORDER BY r.date_creation DESC
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":user_id" => $userId
    ]);

    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* =========================
       CALCUL DES STATISTIQUES
    ========================= */

    $totalReservations = count($reservations);

    foreach ($reservations as $reservation) {

        switch ($reservation["statut"]) {

            case "confirmee":
                $confirmedReservations++;
                break;

            case "en_attente":
                $pendingReservations++;
                break;

            case "annulee":
                $cancelledReservations++;
                break;

            case "expiree":
                $expiredReservations++;
                break;
        }
    }

} catch (PDOException $e) {

    error_log(
        "Erreur mes-reservations.php : " . $e->getMessage()
    );

    $erreur = "Impossible de charger vos réservations pour le moment.";
}

/* =========================
   LIBELLES DES STATUTS
========================= */

$statuts = [
    "en_attente" => "En attente",
    "confirmee" => "Confirmée",
    "annulee" => "Annulée",
    "expiree" => "Expirée"
];



if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$userId = $_SESSION["user_id"] ?? null;

$stmt = $pdo->prepare("
    SELECT nom, prenom
    FROM users
    WHERE user_id = :user_id
      AND statut = 'actif'
    LIMIT 1
");

$stmt->execute(["user_id" => $userId]);
$clientConnecte = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$clientConnecte) {
    header("Location: ../logout.php");
    exit;
}

$nomComplet = trim(
    $clientConnecte["prenom"] . " " . $clientConnecte["nom"]
);

$initiale = mb_strtoupper(
    mb_substr(
        $clientConnecte["prenom"] ?: $clientConnecte["nom"],
        0,
        1,
        "UTF-8"
    ),
    "UTF-8"
);

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Mes réservations - Burundi Transport</title>

    <link rel="stylesheet" href="mes-reservations.css">
</head>

<body>

    <!-- ================= HEADER ================= -->

    <header class="header">

        <div class="header-content">

            <a href="client-home.php" class="logo">
                <span class="logo-icon">BT</span>
                <span>Burundi Transport</span>
            </a>

            <nav class="nav">

                <a href="client-home.php">Accueil</a>

                <a href="recherche.php">Rechercher un trajet</a>

                <a href="mes-reservations.php" class="active">
                    Mes réservations
					
                </a>
				<a href="chat.php">message</a>

            </nav>

            <div class="user-area">

                               <button    type="button" id="userButton" class="user-button"  aria-expanded="false"
    aria-controls="userMenu">

                   <span class="user-avatar">
        <?= htmlspecialchars($initiale, ENT_QUOTES, "UTF-8") ?>
    </span>

    <span id="userName">
        <?= htmlspecialchars($nomComplet, ENT_QUOTES, "UTF-8") ?>
    </span>

    <span class="arrow">▾</span>

                </button>

                <div class="user-menu" id="userMenu">

                    <a href="profil.php">Mon profil</a>

                    <a href="mes-reservations.php">
                        Mes réservations
                    </a>

                    <a href="../logout.php">
                        Se déconnecter
                    </a>

                </div>

            </div>

        </div>

    </header>

    <!-- ================= PAGE HEADER ================= -->

    <section class="page-header">

        <div class="page-header-content">

            <span class="page-label">ESPACE CLIENT</span>

            <h1>Mes réservations</h1>

            <p>
                Consultez et suivez vos réservations de trajets.
            </p>

        </div>

    </section>

    <!-- ================= MAIN ================= -->

    <main class="main-container">

        <?php if ($erreur !== ""): ?>

            <section class="empty-state">
                <h2>Chargement impossible</h2>
                <p><?= h($erreur) ?></p>

                <button
                    type="button"
                    class="btn-primary"
                    onclick="window.location.reload()"
                >
                    Réessayer
                </button>
            </section>

        <?php else: ?>

            <!-- ================= STATISTIQUES ================= -->

            <section class="stats">

                <div class="stat-card">
                    <div class="stat-icon blue">📋</div>
                    <div>
                        <span>Total</span>
                        <strong id="totalReservations">
                            <?= $totalReservations ?>
                        </strong>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">✓</div>
                    <div>
                        <span>Confirmées</span>
                        <strong id="confirmedReservations">
                            <?= $confirmedReservations ?>
                        </strong>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">⏳</div>
                    <div>
                        <span>En attente</span>
                        <strong id="pendingReservations">
                            <?= $pendingReservations ?>
                        </strong>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon red">✕</div>
                    <div>
                        <span>Annulées</span>
                        <strong id="cancelledReservations">
                            <?= $cancelledReservations ?>
                        </strong>
                    </div>
                </div>

            </section>

            <!-- ================= OUTILS ================= -->

            <section class="reservation-tools">

                <div>
                    <h2>Historique des réservations</h2>

                    <p>
                        Retrouvez ici toutes vos réservations.
                    </p>
                </div>

                <div class="tools-right">

                    <select id="filterStatus">

                        <option value="toutes">
                            Toutes les réservations
                        </option>

                        <option value="confirmee">
                            Confirmées
                        </option>

                        <option value="en_attente">
                            En attente
                        </option>

                        <option value="annulee">
                            Annulées
                        </option>

                        <option value="expiree">
                            Expirées
                        </option>

                    </select>

                    <button
                        type="button"
                        class="btn-primary"
                        id="newReservationButton"
                    >
                        + Nouvelle réservation
                    </button>

                </div>

            </section>

            <!-- ================= LISTE ================= -->

            <section id="reservationsContainer">

                <?php if (empty($reservations)): ?>

                    <section class="empty-state" id="emptyState">

                        <div class="empty-icon">📋</div>

                        <h2>Aucune réservation</h2>

                        <p>
                            Vous n'avez pas encore effectué de réservation.
                        </p>

                        <button
                            type="button"
                            class="btn-primary"
                            id="emptyReservationButton"
                        >
                            Rechercher un trajet
                        </button>

                    </section>

                <?php else: ?>

                    <?php foreach ($reservations as $reservation): ?>

                        <?php
                        $statut = $reservation["statut"];

                        $libelleStatut =
                            $statuts[$statut] ?? "Inconnu";

                        $classeStatut = str_replace(
                            "_",
                            "-",
                            $statut
                        );

                        $referenceUrl =
                            "confirmation.php?reference=" .
                            rawurlencode($reservation["reference"]);
                        ?>

                        <article
                            class="reservation-card"
                            data-status="<?= h($statut) ?>"
                        >

                            <div class="reservation-card-header">

                                <div>
                                    <span class="reference-label">
                                        Référence
                                    </span>

                                    <h3>
                                        <?= h($reservation["reference"]) ?>
                                    </h3>
                                </div>

                                <span
                                    class="status-badge status-<?= h($classeStatut) ?>"
                                >
                                    <?= h($libelleStatut) ?>
                                </span>

                            </div>

                            <div class="reservation-route">

                                <div>
                                    <span>Départ</span>

                                    <strong>
                                        <?= h($reservation["depart"]) ?>
                                    </strong>
                                </div>

                                <span class="route-arrow">→</span>

                                <div>
                                    <span>Destination</span>

                                    <strong>
                                        <?= h($reservation["destination"]) ?>
                                    </strong>
                                </div>

                            </div>

                            <div class="reservation-details">

                                <div class="detail-item">
                                    <span>Date du trajet</span>

                                    <strong>
                                        <?= h(dateFr($reservation["date_depart"])) ?>
                                    </strong>
                                </div>

                                <div class="detail-item">
                                    <span>Heure</span>

                                    <strong>
                                        <?= h(heureFr($reservation["heure_depart"])) ?>
                                    </strong>
                                </div>

                                <div class="detail-item">
                                    <span>Passagers</span>

                                    <strong>
                                        <?= (int) $reservation["nombre_passagers"] ?>
                                    </strong>
                                </div>

                                <div class="detail-item">
                                    <span>Places</span>

                                    <strong>
                                        <?= h($reservation["places"] ?? "Aucune place associée") ?>
                                    </strong>
                                </div>

                            </div>

                            <div class="reservation-card-footer">

                                <div class="reservation-price">

                                    <span>Montant total</span>

                                    <strong>
                                        <?= h(montantBIF($reservation["montant_total"])) ?>
                                    </strong>

                                </div>

                                <a
                                    href="<?= h($referenceUrl) ?>"
                                    class="btn-primary"
                                >
                                    Voir les détails
                                </a>

                            </div>

                        </article>

                    <?php endforeach; ?>

                    <section
                        class="empty-state"
                        id="emptyState"
                        style="display: none;"
                    >
                        <div class="empty-icon">📋</div>

                        <h2>Aucune réservation correspondante</h2>

                        <p>
                            Aucune réservation ne correspond au statut sélectionné.
                        </p>
                    </section>

                <?php endif; ?>

            </section>

        <?php endif; ?>

    </main>

    <!-- ================= FOOTER ================= -->

    <footer class="footer">

        <div class="footer-content">

            <div>
                <strong>Burundi Transport</strong>

                <p>
                    Réservez vos trajets interprovinciaux
                    simplement et rapidement.
                </p>
            </div>

            <div class="footer-links">

                <a href="client-home.php">Accueil</a>

                <a href="recherche.php">Rechercher</a>

                <a href="profil.php">Mon profil</a>

            </div>

        </div>

        <div class="footer-bottom">
            © 2026 Burundi Transport. Tous droits réservés.
        </div>

    </footer>

    <script src="mes-reservations.js"></script>
	<script src="deconnexion-auto.js"></script>

</body>
</html>
