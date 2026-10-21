
<?php
require_once __DIR__ . "/../verification.php";
verifierAcces("client", "../login.html");
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rechercher un trajet - Burundi Transport</title>

    <link rel="stylesheet" href="recherche.css">
</head>

<body>

    <!-- HEADER -->
    <header class="header">

        <a href="client-home.php" class="logo">
            <span class="logo-icon">BT</span>
            <span>Burundi Transport</span>
        </a>

        <nav class="navigation">

            <a href="client-home.php">
                Accueil
            </a>

            <a href="recherche.php" class="active">
                Rechercher
            </a>

            <a href="mes-reservations.php">
                Mes réservations
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
                        Mes réservations
                    </a>

                    <button id="logoutButton">
                        <a href="../logout.php">Se déconnecter</a>
                    </button>

                </div>

            </div>

        </div>

    </header>


    <!-- CONTENU -->
    <main>

        <!-- TITRE -->
        <section class="page-header">

            <div class="page-header-content">

                <span>
                    RÉSERVATION
                </span>

                <h1>
                    Rechercher un trajet
                </h1>

                <p>
                    Trouvez un trajet correspondant à vos besoins.
                </p>

            </div>

        </section>


        <!-- FORMULAIRE -->
        <section class="search-section">

            <div class="search-card">

                <div class="card-title">

                    <h2>
                        Vos critères de voyage
                    </h2>

                    <p>
                        Modifiez les informations puis recherchez
                        les trajets disponibles.
                    </p>

                </div>


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

                        <small id="departureError"></small>

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

                        <small id="destinationError"></small>

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

                        <small id="dateError"></small>

                    </div>


                    <!-- PASSAGERS -->
                    <div class="form-group">

                        <label for="passengers">
                            Nombre de passagers
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


                    <div class="form-actions">

                        <a
                            href="client-home.html"
                            class="btn-secondary">

                            Retour

                        </a>

                        <button
                            type="submit"
                            class="btn-search">

                            Rechercher les trajets

                        </button>

                    </div>

                </form>


                <div
                    id="searchMessage"
                    class="search-message">
                </div>

            </div>

        </section>


        <!-- INFORMATIONS -->
        <section class="info-section">

            <div class="info-title">

                <span>
                    À SAVOIR
                </span>

                <h2>
                    Comment fonctionne la recherche ?
                </h2>

            </div>


            <div class="info-container">


                <div class="info-card">

                    <div class="info-number">
                        01
                    </div>

                    <h3>
                        Choisissez votre trajet
                    </h3>

                    <p>
                        Sélectionnez votre ville de départ
                        et votre destination.
                    </p>

                </div>


                <div class="info-card">

                    <div class="info-number">
                        02
                    </div>

                    <h3>
                        Choisissez une date
                    </h3>

                    <p>
                        Indiquez la date à laquelle
                        vous souhaitez voyager.
                    </p>

                </div>


                <div class="info-card">

                    <div class="info-number">
                        03
                    </div>

                    <h3>
                        Consultez les trajets
                    </h3>

                    <p>
                        Vous verrez ensuite les trajets
                        disponibles pour vos critères.
                    </p>

                </div>

            </div>

        </section>

    </main>


    <!-- FOOTER -->
    <footer class="footer">

        <div class="footer-content">

            <div class="footer-logo">
                Burundi Transport
            </div>

            <div class="footer-links">

                <a href="client-home.html">
                    Accueil
                </a>

                <a href="mes-reservations.html">
                    Mes réservations
                </a>

                <a href="profil.html">
                    Mon profil
                </a>

            </div>

        </div>

        <div class="footer-bottom">

            <p>
                © 2026 Burundi Transport.
                Tous droits reserves.
            </p>

        </div>

    </footer>


    <script src="recherche.js"></script>

</body>

</html>