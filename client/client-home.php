

<?php
require_once __DIR__ . "/../verification.php";
verifierAcces("client", "../login.html");
?>
  


<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Espace client - Burundi Transport</title>

    <link rel="stylesheet" href="client-home.css">
</head>

<body>

    <!-- ================================
         HEADER
    ================================= -->

    <header class="header">

        <a href="client-home.php" class="logo">
            <span class="logo-icon">BT</span>
            <span>Burundi Transport</span>
        </a>


        <nav class="navigation">

            <a href="client-home.php" class="active">
                Accueil
            </a>

            <a href="recherche.php">
                Rechercher un trajet
            </a>

            <a href="mes-reservations.php">
                Mes reservations
            </a>

        </nav>


        <div class="header-right">

            <div class="user-menu">

                <button id="userButton" class="user-button">

                    <span class="user-avatar">
                        C
                    </span>

                    <span id="userName">
                        Client
                    </span>

                    <span class="arrow">
                        ▾
                    </span>

                </button>


                <div id="userDropdown" class="user-dropdown">

                    <a href="profil.php">
                        Mon profil
                    </a>

                    <a href="mes-reservations.php">
                        Mes reservations
                    </a>

                    <button id="logoutButton">
                        <a href="../logout.php">Se déconnecter</a>
                    </button>

                </div>

            </div>

        </div>

    </header>


    <!-- ================================
         CONTENU PRINCIPAL
    ================================= -->

    <main>


        <!-- ================================
             HERO
        ================================= -->

        <section class="hero">

            <div class="hero-content">

                <span class="welcome-label">
                    ESPACE CLIENT
                </span>

                <h1>
                    Bonjour, <span id="welcomeName">Client</span> !
                </h1>

                <p>
                    Ou souhaitez-vous aller aujourd'hui ?
                    Recherchez votre trajet et reservez votre place
                    en quelques etapes.
                </p>

                <button
                    id="startSearchButton"
                    class="btn-main">

                    Rechercher un trajet

                </button>

            </div>

        </section>


        <!-- ================================
             RECHERCHE RAPIDE
        ================================= -->

        <section class="search-section">

            <div class="section-title">

                <span>
                    RESERVER UN TRAJET
                </span>

                <h2>
                    Trouvez votre prochain voyage
                </h2>

                <p>
                    Indiquez votre depart, votre destination et votre date.
                </p>

            </div>


            <div class="search-container">

                <form id="searchForm">


                    <!-- DEPART -->

                    <div class="form-group">

                        <label for="departure">
                            Départ
                        </label>

                        <select id="departure">

                            <option value="">
                                Choisir le départ
                            </option>

                            <option value="Bujumbura">
                                Bujumbura
                            </option>

                            <option value="Gitega">
                                Gitega
                            </option>

                            <option value="Ngozi">
                                Ngozi
                            </option>

                            <option value="Muyinga">
                                Muyinga
                            </option>

                            <option value="Bururi">
                                Bururi
                            </option>

                            <option value="Makamba">
                                Makamba
                            </option>

                        </select>

                    </div>


                    <!-- DESTINATION -->

                    <div class="form-group">

                        <label for="destination">
                            Destination
                        </label>

                        <select id="destination">

                            <option value="">
                                Choisir la destination
                            </option>

                            <option value="Bujumbura">
                                Bujumbura
                            </option>

                            <option value="Gitega">
                                Gitega
                            </option>

                            <option value="Ngozi">
                                Ngozi
                            </option>

                            <option value="Muyinga">
                                Muyinga
                            </option>

                            <option value="Bururi">
                                Bururi
                            </option>

                            <option value="Makamba">
                                Makamba
                            </option>

                        </select>

                    </div>


                    <!-- DATE -->

                    <div class="form-group">

                        <label for="date">
                            Date du voyage
                        </label>

                        <input
                            type="date"
                            id="date"
                        >

                    </div>


                    <!-- PASSAGERS -->

                    <div class="form-group">

                        <label for="passengers">
                            Passagers
                        </label>

                        <select id="passengers">

                            <option value="1">
                                1 passager
                            </option>

                            <option value="2">
                                2 passagers
                            </option>

                            <option value="3">
                                3 passagers
                            </option>

                            <option value="4">
                                4 passagers
                            </option>

                            <option value="5">
                                5 passagers
                            </option>

                        </select>

                    </div>


                    <button
                        type="submit"
                        class="btn-search">

                        Rechercher

                    </button>

                </form>


                <div
                    id="searchMessage"
                    class="search-message">
                </div>

            </div>

        </section>


        <!-- ================================
             ACCES RAPIDES
        ================================= -->

        <section class="quick-section">

            <div class="section-title">

                <span>
                    MON ESPACE
                </span>

                <h2>
                    Acces rapides
                </h2>

            </div>


            <div class="quick-container">


                <!-- RECHERCHE -->

                <a
                    href="recherche.php"
                    class="quick-card">

                    <div class="quick-icon">
                        🔎
                    </div>

                    <div>

                        <h3>
                            Rechercher un trajet
                        </h3>

                        <p>
                            Trouvez les trajets disponibles
                            selon votre destination.
                        </p>

                    </div>

                    <span class="card-arrow">
                        →
                    </span>

                </a>


                <!-- RESERVATIONS -->

                <a
                    href="mes-reservations.php"
                    class="quick-card">

                    <div class="quick-icon">
                        🎫
                    </div>

                    <div>

                        <h3>
                            Mes reservations
                        </h3>

                        <p>
                            Consultez et gerez vos reservations.
                        </p>

                    </div>

                    <span class="card-arrow">
                        →
                    </span>

                </a>


                <!-- PROFIL -->

                <a
                    href="profil.php"
                    class="quick-card">

                    <div class="quick-icon">
                        👤
                    </div>

                    <div>

                        <h3>
                            Mon profil
                        </h3>

                        <p>
                            Consultez et modifiez vos informations.
                        </p>

                    </div>

                    <span class="card-arrow">
                        →
                    </span>

                </a>

            </div>

        </section>


        <!-- ================================
             COMMENT CA MARCHE
        ================================= -->

        <section class="how-section">

            <div class="section-title">

                <span>
                    RESERVATION
                </span>

                <h2>
                    Comment ca marche ?
                </h2>

            </div>


            <div class="steps-container">


                <div class="step">

                    <div class="step-number">
                        1
                    </div>

                    <h3>
                        Rechercher
                    </h3>

                    <p>
                        Indiquez votre depart,
                        destination et date.
                    </p>

                </div>


                <div class="step">

                    <div class="step-number">
                        2
                    </div>

                    <h3>
                        Choisir
                    </h3>

                    <p>
                        Consultez les trajets disponibles
                        et choisissez votre voyage.
                    </p>

                </div>


                <div class="step">

                    <div class="step-number">
                        3
                    </div>

                    <h3>
                        Sélectionner
                    </h3>

                    <p>
                        Choisissez votre siege
                        dans le bus.
                    </p>

                </div>


                <div class="step">

                    <div class="step-number">
                        4
                    </div>

                    <h3>
                        Réserver
                    </h3>

                    <p>
                        Confirmez vos informations
                        et votre reservation.
                    </p>

                </div>

            </div>

        </section>

    </main>


    <!-- ================================
         FOOTER
    ================================= -->

    <footer class="footer">

        <div class="footer-content">

            <div>

                <div class="footer-logo">
                    Burundi Transport
                </div>

                <p>
                    Votre solution simple pour les voyages
                    interprovinciaux au Burundi.
                </p>

            </div>


            <div class="footer-links">

                <a href="recherche.php">
                    Rechercher
                </a>

                <a href="mes-reservations.php">
                    Mes reservations
                </a>

                <a href="profil.php">
                    Mon profil
                </a>

            </div>

        </div>


        <div class="footer-bottom">

            <p>
                © 2026 Burundi Transport.
                Tous droits réservés.
            </p>

        </div>

    </footer>


    <script src="client-home.js"></script>

</body>

</html>