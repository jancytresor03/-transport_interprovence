
<?php
require_once __DIR__ . "/../verification.php";
verifierAcces("client", "../login.html");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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

                <a href="client-home.php">
                    Accueil
                </a>

                <a href="recherche.php">
                    Rechercher un trajet
                </a>

                <a href="mes-reservations.php" class="active">
                    Mes réservations
                </a>

            </nav>


            <div class="user-area">

                <button class="user-button" id="userButton">

                    <span class="user-avatar">
                        C
                    </span>

                    <span>
                        Client
                    </span>

                    <span class="arrow">
                        ⌄
                    </span>

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

            <span class="page-label">
                ESPACE CLIENT
            </span>

            <h1>
                Mes réservations
            </h1>

            <p>
                Consultez et gérez vos réservations de trajets.
            </p>

        </div>

    </section>


    <!-- ================= MAIN ================= -->

    <main class="main-container">


        <!-- ================= STATISTIQUES ================= -->

        <section class="stats">

            <div class="stat-card">

                <div class="stat-icon blue">
                    📋
                </div>

                <div>
                    <span>
                        Total
                    </span>

                    <strong id="totalReservations">
                        0
                    </strong>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon green">
                    ✓
                </div>

                <div>
                    <span>
                        Confirmées
                    </span>

                    <strong id="confirmedReservations">
                        0
                    </strong>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon orange">
                    ⏳
                </div>

                <div>
                    <span>
                        En attente
                    </span>

                    <strong id="pendingReservations">
                        0
                    </strong>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon red">
                    ✕
                </div>

                <div>
                    <span>
                        Annulées
                    </span>

                    <strong id="cancelledReservations">
                        0
                    </strong>
                </div>

            </div>

        </section>


        <!-- ================= BARRE D'ACTIONS ================= -->

        <section class="reservation-tools">

            <div>

                <h2>
                    Historique des réservations
                </h2>

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

                    <option value="attente">
                        En attente
                    </option>

                    <option value="annulee">
                        Annulées
                    </option>

                </select>


                <button
                    class="btn-primary"
                    id="newReservationButton"
                >
                    + Nouvelle réservation
                </button>

            </div>

        </section>


        <!-- ================= LISTE ================= -->

        <section id="reservationsContainer">

            <!-- Les réservations seront ajoutées
                 automatiquement avec JavaScript -->

        </section>


        <!-- ================= MESSAGE VIDE ================= -->

        <section
            class="empty-state"
            id="emptyState"
            style="display: none;"
        >

            <div class="empty-icon">
                📋
            </div>

            <h2>
                Aucune réservation
            </h2>

            <p>
                Vous n'avez pas encore effectué de réservation.
            </p>

            <button
                class="btn-primary"
                id="emptyReservationButton"
            >
                Rechercher un trajet
            </button>

        </section>


    </main>


    <!-- ================= FOOTER ================= -->

    <footer class="footer">

        <div class="footer-content">

            <div>

                <strong>
                    Burundi Transport
                </strong>

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

                <a href="profil.html">
                    Mon profil
                </a>

            </div>

        </div>


        <div class="footer-bottom">

            © 2026 Burundi Transport.
            Tous droits réservés.

        </div>

    </footer>


    <script src="mes-reservations.js"></script>

</body>
</html>