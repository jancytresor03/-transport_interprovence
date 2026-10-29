
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

function formaterMontant($montant): string
{
    return number_format((float) $montant, 0, ",", " ") . " BIF";
}

function formaterDate($date): string
{
    if (empty($date)) {
        return "-";
    }

    $timestamp = strtotime($date);

    return $timestamp !== false
        ? date("d/m/Y", $timestamp)
        : "-";
}

function formaterHeure($heure): string
{
    return !empty($heure)
        ? h(substr((string) $heure, 0, 5))
        : "-";
}

/* =========================
   INITIALISATION
========================= */

$userId = $_SESSION["user_id"] ?? null;
$reference = trim($_GET["reference"] ?? "");

$reservation = null;
$places = [];
$erreur = "";

/* =========================
   VERIFICATION CLIENT
========================= */

if (!$userId) {
    header("Location: ../login.html");
    exit;
}

/* =========================
   RECUPERATION RESERVATION
========================= */

if ($reference === "") {

    $erreur = "Aucune référence de réservation n'a été fournie.";

} else {

    try {

        $sql = "
            SELECT
                r.reservation_id,
                r.reference,
                r.user_id,
                r.trajet_id,
                r.nom_passager,
                r.prenom_passager,
                r.telephone_passager,
                r.email_passager,
                r.type_identite,
                r.numero_identite,
                r.nombre_passagers,
                r.montant_total,
                r.statut,
                r.remarque,
                r.date_creation,

                t.depart,
                t.destination,
                t.date_depart,
                t.heure_depart,
                t.prix

            FROM reservations r

            INNER JOIN trajets t
                ON t.trajet_id = r.trajet_id

            WHERE r.reference = :reference
              AND r.user_id = :user_id

            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":reference" => $reference,
            ":user_id" => $userId
        ]);

        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reservation) {

            $erreur = "Réservation introuvable ou inaccessible.";

        } else {

            /* =========================
               RECUPERATION DES SIEGES
            ========================= */

            $sqlPlaces = "
                SELECT numero_siege

                FROM sieges_trajets

                WHERE reservation_id = :reservation_id
                  AND trajet_id = :trajet_id

                ORDER BY numero_siege ASC
            ";

            $stmtPlaces = $pdo->prepare($sqlPlaces);

            $stmtPlaces->execute([
                ":reservation_id" => $reservation["reservation_id"],
                ":trajet_id" => $reservation["trajet_id"]
            ]);

            $places = $stmtPlaces->fetchAll(PDO::FETCH_COLUMN);
        }

    } catch (PDOException $e) {

        error_log(
            "Erreur confirmation réservation : " . $e->getMessage()
        );

        $erreur = "Une erreur est survenue lors du chargement de la réservation.";
    }
}

/* =========================
   STATUT RESERVATION
========================= */

$statuts = [
    "en_attente" => "En attente",
    "confirmee" => "Confirmée",
    "annulee" => "Annulée",
    "expiree" => "Expirée"
];

$statut = $reservation["statut"] ?? "";
$libelleStatut = $statuts[$statut] ?? "Statut inconnu";

$classesStatut = [
    "en_attente" => "en-attente",
    "confirmee" => "confirmee",
    "annulee" => "annulee",
    "expiree" => "expiree"
];

$classeStatut = $classesStatut[$statut] ?? "inconnu";

/* =========================
   DONNEES CALCULEES
========================= */

$nombrePassagers = (int) ($reservation["nombre_passagers"] ?? 0);

$prixUnitaire = (float) ($reservation["prix"] ?? 0);

$total = (float) ($reservation["montant_total"] ?? 0);

$nomComplet = $reservation
    ? trim(
        ($reservation["prenom_passager"] ?? "") . " " .
        ($reservation["nom_passager"] ?? "")
    )
    : "";

$textePlaces = !empty($places)
    ? implode(", ", $places)
    : "Aucun siège associé à cette réservation";

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= $reservation
            ? "Réservation " . h($reservation["reference"]) . " - Burundi Transport"
            : "Réservation - Burundi Transport" ?>
    </title>

    <link rel="stylesheet" href="confirmation.css">
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
                <a href="mes-reservations.php">Mes réservations</a>
				<a href="chat.php">message</a>
            </nav>

            <div class="user-area">

                <button
                    type="button"
                    class="user-button"
                    id="userButton"
                >
                    <span class="user-avatar">C</span>
                    <span>Client</span>
                    <span class="arrow">⌄</span>
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

            <?php if ($reservation): ?>

                <div class="success-icon">
                    <?= $statut === "confirmee" ? "✓" : "i" ?>
                </div>

                <span class="page-label">
                    RESERVATION
                </span>

                <h1>
                    <?= h($libelleStatut) ?>
                </h1>

                <p>
                    Votre réservation a été enregistrée.
                    Consultez les détails ci-dessous.
                </p>

            <?php else: ?>

                <div class="success-icon">!</div>

                <span class="page-label">
                    RESERVATION
                </span>

                <h1>Réservation indisponible</h1>

                <p>
                    Les informations demandées ne peuvent pas être affichées.
                </p>

            <?php endif; ?>

        </div>

    </section>

    <!-- ================= MAIN ================= -->

    <main class="confirmation-container">

        <?php if ($erreur !== ""): ?>

            <section class="confirmation-card">

                <h2>Impossible d'afficher la réservation</h2>

                <p><?= h($erreur) ?></p>

                <div class="actions">

                    <a
                        href="mes-reservations.php"
                        class="btn btn-primary"
                    >
                        Voir mes réservations
                    </a>

                    <a
                        href="client-home.php"
                        class="btn btn-secondary"
                    >
                        Retour à l'accueil
                    </a>

                </div>

            </section>

        <?php elseif ($reservation): ?>

            <!-- ================= REFERENCE ================= -->

            <section class="reference-card">

                <div class="reference-icon">#</div>

                <div>

                    <span class="reference-label">
                        Référence de réservation
                    </span>

                    <strong id="referenceReservation">
                        <?= h($reservation["reference"]) ?>
                    </strong>

                    <p>
                        Statut :
                        <strong class="status-badge status-<?= h($classeStatut) ?>">
                            <?= h($libelleStatut) ?>
                        </strong>
                    </p>

                </div>

            </section>

            <!-- ================= CONTENU ================= -->

            <div class="confirmation-grid">

                <!-- DETAILS DU TRAJET -->

                <section class="confirmation-card">

                    <div class="card-title">

                        <div class="title-icon">🚌</div>

                        <div>
                            <h2>Détails du trajet</h2>

                            <p>
                                Informations concernant votre voyage
                            </p>
                        </div>

                    </div>

                    <div class="company-box">

                        <span class="company-label">
                            Transporteur
                        </span>

                        <strong>Burundi Transport</strong>

                    </div>

                    <div class="route-box">

                        <div class="location">

                            <span class="location-label">
                                Départ
                            </span>

                            <strong id="depart">
                                <?= h($reservation["depart"]) ?>
                            </strong>

                        </div>

                        <div class="route-arrow">→</div>

                        <div class="location">

                            <span class="location-label">
                                Destination
                            </span>

                            <strong id="destination">
                                <?= h($reservation["destination"]) ?>
                            </strong>

                        </div>

                    </div>

                    <div class="details-grid">

                        <div class="detail-item">

                            <span>Date</span>

                            <strong id="date">
                                <?= h(formaterDate($reservation["date_depart"])) ?>
                            </strong>

                        </div>

                        <div class="detail-item">

                            <span>Heure</span>

                            <strong id="heure">
                                <?= formaterHeure($reservation["heure_depart"]) ?>
                            </strong>

                        </div>

                        <div class="detail-item">

                            <span>Nombre de passagers</span>

                            <strong id="passagers">
                                <?= $nombrePassagers ?>
                            </strong>

                        </div>

                        <div class="detail-item">

                            <span>Places</span>

                            <strong id="places">
                                <?= h($textePlaces) ?>
                            </strong>

                        </div>

                    </div>

                </section>

                <!-- INFORMATIONS PASSAGER -->

                <section class="confirmation-card">

                    <div class="card-title">

                        <div class="title-icon">👤</div>

                        <div>
                            <h2>Informations du passager</h2>

                            <p>
                                Informations fournies lors de la réservation
                            </p>
                        </div>

                    </div>

                    <div class="passenger-info">

                        <div class="info-row">

                            <span>Nom complet</span>

                            <strong id="nomComplet">
                                <?= h($nomComplet) ?>
                            </strong>

                        </div>

                        <div class="info-row">

                            <span>Téléphone</span>

                            <strong id="telephone">
                                <?= h($reservation["telephone_passager"]) ?>
                            </strong>

                        </div>

                        <div class="info-row">

                            <span>Email</span>

                            <strong id="email">
                                <?= h($reservation["email_passager"] ?? "-") ?>
                            </strong>

                        </div>

                        <div class="info-row">

                            <span>Type de pièce</span>

                            <strong id="identite">
                                <?= h($reservation["type_identite"] ?? "-") ?>
                            </strong>

                        </div>

                        <div class="info-row">

                            <span>Numéro de pièce</span>

                            <strong>
                                <?= h($reservation["numero_identite"] ?? "-") ?>
                            </strong>

                        </div>

                    </div>

                </section>

                <!-- RECAPITULATIF DU PRIX -->

                <section class="confirmation-card price-card">

                    <div class="card-title">

                        <div class="title-icon">💰</div>

                        <div>
                            <h2>Récapitulatif du prix</h2>

                            <p>
                                Montant de votre réservation
                            </p>
                        </div>

                    </div>

                    <div class="price-details">

                        <div class="price-row">

                            <span>Prix par place</span>

                            <strong id="prixUnitaire">
                                <?= h(formaterMontant($prixUnitaire)) ?>
                            </strong>

                        </div>

                        <div class="price-row">

                            <span>Nombre de places</span>

                            <strong id="nombrePlaces">
                                <?= $nombrePassagers ?>
                            </strong>

                        </div>

                        <div class="price-total">

                            <span>Total</span>

                            <strong id="total">
                                <?= h(formaterMontant($total)) ?>
                            </strong>

                        </div>

                    </div>

                    <div class="info-message">

                        <span class="info-icon">ℹ</span>

                        <p>
                            Votre réservation est enregistrée dans notre
                            base de données. Son statut est :
                            <strong><?= h($libelleStatut) ?></strong>.
                            Le paiement est géré séparément.
                        </p>

                    </div>

                </section>

            </div>

            <!-- ================= ACTIONS ================= -->

            <section class="actions">

                <a
                    href="client-home.php"
                    class="btn btn-secondary"
                >
                    Retour à l'accueil
                </a>

                <a
                    href="mes-reservations.php"
                    class="btn btn-primary"
                >
                    Voir mes réservations
                </a>

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

                <a href="mes-reservations.php">
                    Mes réservations
                </a>

            </div>

        </div>

        <div class="footer-bottom">
            © 2026 Burundi Transport. Tous droits réservés.
        </div>

    </footer>

    <!-- Menu utilisateur -->
    <script>
        const userButton = document.getElementById("userButton");
        const userMenu = document.getElementById("userMenu");

        if (userButton && userMenu) {
            userButton.addEventListener("click", function () {
                userMenu.classList.toggle("active");
            });

            document.addEventListener("click", function (event) {
                if (
                    !userButton.contains(event.target) &&
                    !userMenu.contains(event.target)
                ) {
                    userMenu.classList.remove("active");
                }
            });
        }
    </script>
	
	<script src="deconnexion-auto.js"></script>

</body>
</html>
