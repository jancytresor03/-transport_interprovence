
<?php
require_once __DIR__ . "/../verification.php";
verifierAcces("client", "../login.html");
require_once __DIR__ . "/../database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h($valeur) {
    return htmlspecialchars((string) $valeur, ENT_QUOTES, "UTF-8");
}

$userId = $_SESSION["user_id"] ?? null;
$reference = trim($_GET["reference"] ?? "");

$reservation = null;
$places = [];
$erreur = "";

if (!$userId) {
    header("Location: ../login.html");
    exit;
}

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
            INNER JOIN trajets t ON t.trajet_id = r.trajet_id
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
        error_log("Erreur confirmation réservation : " . $e->getMessage());
        $erreur = "Une erreur est survenue lors du chargement de la réservation.";
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Confirmation - Burundi Transport</title>

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
            </nav>

            <div class="user-area">

                <button class="user-button" id="userButton">
                    <span class="user-avatar">C</span>
                    <span>Client</span>
                    <span class="arrow">⌄</span>
                </button>

                <div class="user-menu" id="userMenu">

                    <a href="profil.php">
                        Mon profil
                    </a>

                    <a href="mes-reservations.php">
                        Mes réservations
                    </a>

                    <button id="logoutButton">
                        <a href="../logout.php">Se déconnecter</a>
                    </button>

                </div>

            </div>

        </div>

    </header>


    <!-- ================= PAGE HEADER ================= -->
    <section class="page-header">

        <div class="page-header-content">

            <div class="success-icon">
                ✓
            </div>

            <span class="page-label">
                RÉSERVATION TERMINÉE
            </span>

            <h1>
                Réservation confirmée !
            </h1>

            <p>
                Votre réservation a été enregistrée avec succès.
            </p>

        </div>

    </section>


    <!-- ================= MAIN ================= -->
    <main class="confirmation-container">

        <!-- Numéro de réservation -->
        <section class="reference-card">

            <div class="reference-icon">
                #
            </div>

            <div>
                <span class="reference-label">
                    Numéro de réservation
                </span>

                <strong id="referenceReservation">
                    BT-000000
                </strong>
            </div>

        </section>


        <!-- Contenu principal -->
        <div class="confirmation-grid">

            <!-- ================= RÉSUMÉ TRAJET ================= -->
            <section class="confirmation-card">

                <div class="card-title">

                    <div class="title-icon">
                        🚌
                    </div>

                    <div>
                        <h2>
                            Détails du trajet
                        </h2>

                        <p>
                            Informations concernant votre voyage
                        </p>
                    </div>

                </div>


                <div class="company-box">

                    <span class="company-label">
                        Transporteur
                    </span>

                    <strong>
                        Burundi Transport
                    </strong>

                </div>


                <div class="route-box">

                    <div class="location">

                        <span class="location-label">
                            Départ
                        </span>

                        <strong id="depart">
                            -
                        </strong>

                    </div>


                    <div class="route-arrow">
                        →
                    </div>


                    <div class="location">

                        <span class="location-label">
                            Destination
                        </span>

                        <strong id="destination">
                            -
                        </strong>

                    </div>

                </div>


                <div class="details-grid">

                    <div class="detail-item">

                        <span>
                            Date
                        </span>

                        <strong id="date">
                            -
                        </strong>

                    </div>


                    <div class="detail-item">

                        <span>
                            Heure
                        </span>

                        <strong id="heure">
                            -
                        </strong>

                    </div>


                    <div class="detail-item">

                        <span>
                            Nombre de passagers
                        </span>

                        <strong id="passagers">
                            -
                        </strong>

                    </div>


                    <div class="detail-item">

                        <span>
                            Places
                        </span>

                        <strong id="places">
                            -
                        </strong>

                    </div>

                </div>

            </section>


            <!-- ================= PASSAGER ================= -->
            <section class="confirmation-card">

                <div class="card-title">

                    <div class="title-icon">
                        👤
                    </div>

                    <div>
                        <h2>
                            Informations du passager
                        </h2>

                        <p>
                            Informations fournies lors de la réservation
                        </p>
                    </div>

                </div>


                <div class="passenger-info">

                    <div class="info-row">

                        <span>
                            Nom complet
                        </span>

                        <strong id="nomComplet">
                            -
                        </strong>

                    </div>


                    <div class="info-row">

                        <span>
                            Téléphone
                        </span>

                        <strong id="telephone">
                            -
                        </strong>

                    </div>


                    <div class="info-row">

                        <span>
                            Email
                        </span>

                        <strong id="email">
                            -
                        </strong>

                    </div>


                    <div class="info-row">

                        <span>
                            Pièce d'identité
                        </span>

                        <strong id="identite">
                            -
                        </strong>

                    </div>

                </div>

            </section>


            <!-- ================= PAIEMENT ================= -->
            <section class="confirmation-card price-card">

                <div class="card-title">

                    <div class="title-icon">
                        💰
                    </div>

                    <div>
                        <h2>
                            Récapitulatif du prix
                        </h2>

                        <p>
                            Montant de votre réservation
                        </p>
                    </div>

                </div>


                <div class="price-details">

                    <div class="price-row">

                        <span>
                            Prix par place
                        </span>

                        <strong id="prixUnitaire">
                            0 BIF
                        </strong>

                    </div>


                    <div class="price-row">

                        <span>
                            Nombre de places
                        </span>

                        <strong id="nombrePlaces">
                            0
                        </strong>

                    </div>


                    <div class="price-total">

                        <span>
                            Total
                        </span>

                        <strong id="total">
                            0 BIF
                        </strong>

                    </div>

                </div>


                <div class="info-message">

                    <span class="info-icon">
                        ℹ
                    </span>

                    <p>
                        Cette page représente actuellement une
                        confirmation frontend. Le paiement et
                        l'enregistrement définitif seront ajoutés
                        avec PHP et MySQL.
                    </p>

                </div>

            </section>

        </div>


        <!-- ================= ACTIONS ================= -->
        <section class="actions">

            <button
                class="btn btn-secondary"
                id="homeButton"
            >
                Retour à l'accueil
            </button>

            <button
                class="btn btn-primary"
                id="reservationsButton"
            >
                Voir mes réservations
            </button>

        </section>

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

                <a href="client-home.html">
                    Accueil
                </a>

                <a href="recherche.html">
                    Rechercher
                </a>

                <a href="mes-reservations.html">
                    Mes reservations
                </a>

            </div>

        </div>

        <div class="footer-bottom">

            © 2026 Burundi Transport. Tous droits reserves.

        </div>

    </footer>


    <script src="confirmation.js"></script>

</body>
</html>