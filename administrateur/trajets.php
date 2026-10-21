
<?php
require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");
?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestion des trajets - Burundi Transport</title>


    <!-- Google Font -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- CSS -->
	
    <link rel="stylesheet" href="theme.css">
    <link rel="stylesheet" href="trajets.css">

</head>


<body>


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar" id="sidebar">


        <!-- LOGO -->

        <div class="sidebar-header">

            <a href="admin-dashboard.html" class="logo">

                <div class="logo-icon">

                    <i class="fa-solid fa-bus"></i>

                </div>


                <div class="logo-text">

                    <strong>Burundi</strong>

                    <span>Transport</span>

                </div>

            </a>


            <button class="close-menu" id="closeMenu">

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>



        <!-- MENU SCROLLABLE -->

        <div class="sidebar-scroll">


            <div class="menu-title">

                MENU PRINCIPAL

            </div>


            <nav class="menu">


                <a href="admin-dashboard.php" class="menu-link">

                    <i class="fa-solid fa-gauge-high"></i>

                    <span>Dashboard</span>

                </a>


                <a href="trajets.php" class="menu-link active">

                    <i class="fa-solid fa-route"></i>

                    <span>Trajets</span>

                </a>


                <a href="reservations.php" class="menu-link">

                    <i class="fa-solid fa-ticket"></i>

                    <span>Réservations</span>

                </a>


                <a href="utilisateurs.php" class="menu-link">

                    <i class="fa-solid fa-users"></i>

                    <span>Utilisateurs</span>

                </a>


                <a href="statistiques.php" class="menu-link">

                    <i class="fa-solid fa-chart-column"></i>

                    <span>Statistiques</span>

                </a>


                <a href="notifications.php" class="menu-link">

                    <i class="fa-solid fa-bell"></i>

                    <span>Notifications</span>

                    <small>3</small>

                </a>


                <a href="activites.php" class="menu-link">

                    <i class="fa-solid fa-clock-rotate-left"></i>

                    <span>Activités</span>

                </a>

            </nav>



            <div class="menu-title">

                MON COMPTE

            </div>


            <nav class="menu">


                <a href="profil.php" class="menu-link">

                    <i class="fa-solid fa-user"></i>

                    <span>Mon profil</span>

                </a>


                <a href="parametres.php" class="menu-link">

                    <i class="fa-solid fa-gear"></i>

                    <span>Paramètres</span>

                </a>


                <a href="aide.php" class="menu-link">

                    <i class="fa-solid fa-circle-question"></i>

                    <span>Aide</span>

                </a>


            </nav>

        </div>



        <!-- FOOTER SIDEBAR -->

        <div class="sidebar-footer">


            <div class="admin-box">

                <div class="admin-avatar">

                    AD

                </div>


                <div class="admin-information">

                    <strong>Administrateur</strong>

                    <span>admin@gmail.com</span>

                </div>

            </div>


            <button
                class="logout-button"
                id="logoutButton"
            >

                <i class="fa-solid fa-right-from-bracket"></i>

                <a href="../logout.php">Se déconnecter</a>

            </button>

        </div>


    </aside>



    <!-- OVERLAY MOBILE -->

    <div class="overlay" id="overlay"></div>



    <!-- =====================================================
         CONTENU
    ====================================================== -->

    <main class="main-content">


        <!-- HEADER -->

        <header class="top-header">


            <div class="header-left">


                <button
                    class="mobile-menu-button"
                    id="openMenu"
                >

                    <i class="fa-solid fa-bars"></i>

                </button>


                <div>

                    <span class="header-small">

                        Administration

                    </span>


                    <h1>

                        Gestion des trajets

                    </h1>

                </div>

            </div>



            <div class="header-right">


                <button
                    class="notification-button"
                    id="notificationButton"
                >

                    <i class="fa-regular fa-bell"></i>

                    <span class="notification-dot"></span>

                </button>


                <div class="header-admin">

                    <div class="header-avatar">

                        AD

                    </div>


                    <div class="header-admin-text">

                        <strong>

                            Administrateur

                        </strong>


                        <span>

                            Super Admin

                        </span>

                    </div>


                    <i class="fa-solid fa-chevron-down"></i>

                </div>

            </div>

        </header>



        <!-- =====================================================
             CONTENU PAGE
        ====================================================== -->

        <section class="page-content">


            <!-- TITRE -->

            <div class="page-introduction">


                <div>

                    <span class="page-label">

                        TRANSPORT

                    </span>


                    <h2>

                        Gestion des trajets

                    </h2>


                    <p>

                        Ajoutez, modifiez, recherchez ou supprimez
                        les trajets proposés par Burundi Transport.

                    </p>

                </div>


                <button
                    class="primary-button"
                    id="addTripButton"
                >

                    <i class="fa-solid fa-plus"></i>

                    Ajouter un trajet

                </button>

            </div>



            <!-- =================================================
                 STATISTIQUES
            ================================================== -->

            <div class="summary-cards">


                <div class="summary-card">

                    <div class="summary-icon blue">

                        <i class="fa-solid fa-route"></i>

                    </div>


                    <div>

                        <span>Total trajets</span>

                        <strong id="totalTrajets">

                            0

                        </strong>

                    </div>

                </div>



                <div class="summary-card">

                    <div class="summary-icon green">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>


                    <div>

                        <span>Trajets actifs</span>

                        <strong id="trajetsActifs">

                            0

                        </strong>

                    </div>

                </div>



                <div class="summary-card">

                    <div class="summary-icon gold">

                        <i class="fa-solid fa-chair"></i>

                    </div>


                    <div>

                        <span>Places disponibles</span>

                        <strong id="placesDisponibles">

                            0

                        </strong>

                    </div>

                </div>



                <div class="summary-card">

                    <div class="summary-icon purple">

                        <i class="fa-solid fa-location-dot"></i>

                    </div>


                    <div>

                        <span>Destinations</span>

                        <strong id="nombreDestinations">

                            0

                        </strong>

                    </div>

                </div>


            </div>



            <!-- =================================================
                 FILTRES
            ================================================== -->

            <div class="filters-card">


                <div class="filter-title">

                    <i class="fa-solid fa-filter"></i>

                    <span>Rechercher et filtrer</span>

                </div>



                <div class="filters">


                    <!-- Recherche -->

                    <div class="filter-group search-group">

                        <label for="searchInput">

                            Recherche

                        </label>


                        <div class="input-with-icon">

                            <i class="fa-solid fa-magnifying-glass"></i>


                            <input
                                type="text"
                                id="searchInput"
                                placeholder="Rechercher un trajet..."
                            >

                        </div>

                    </div>



                    <!-- Départ -->

                    <div class="filter-group">

                        <label for="departureFilter">

                            Départ

                        </label>


                        <select id="departureFilter">

                            <option value="">

                                Toutes les villes

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



                    <!-- Statut -->

                    <div class="filter-group">

                        <label for="statusFilter">

                            Statut

                        </label>


                        <select id="statusFilter">

                            <option value="">

                                Tous les statuts

                            </option>

                            <option value="actif">

                                Actif

                            </option>

                            <option value="inactif">

                                Inactif

                            </option>

                        </select>

                    </div>



                    <!-- Tri -->

                    <div class="filter-group">

                        <label for="sortFilter">

                            Trier par

                        </label>


                        <select id="sortFilter">

                            <option value="date">

                                Date

                            </option>

                            <option value="prix">

                                Prix

                            </option>

                            <option value="places">

                                Places disponibles

                            </option>

                            <option value="depart">

                                Ville de départ

                            </option>

                        </select>

                    </div>


                </div>

            </div>



            <!-- =================================================
                 TABLE DES TRAJETS
            ================================================== -->

            <div class="table-card">


                <div class="table-header">


                    <div>

                        <h3>

                            Liste des trajets

                        </h3>


                        <span id="resultCount">

                            0 trajet

                        </span>

                    </div>


                    <button
                        class="refresh-button"
                        id="refreshButton"
                        title="Actualiser"
                    >

                        <i class="fa-solid fa-rotate"></i>

                    </button>

                </div>



                <div class="table-container">

                    <table>


                        <thead>

                            <tr>

                                <th>Trajet</th>

                                <th>Date</th>

                                <th>Heure</th>

                                <th>Prix</th>

                                <th>Places</th>

                                <th>Statut</th>

                                <th>Actions</th>

                            </tr>

                        </thead>


                        <tbody id="tripsTableBody">

                            <!-- JavaScript -->

                        </tbody>


                    </table>


                    <!-- MESSAGE AUCUN RESULTAT -->

                    <div
                        class="empty-state"
                        id="emptyState"
                    >

                        <div class="empty-icon">

                            <i class="fa-solid fa-route"></i>

                        </div>


                        <h3>

                            Aucun trajet trouvé

                        </h3>


                        <p>

                            Aucun trajet ne correspond à votre recherche.

                        </p>

                    </div>

                </div>

            </div>


        </section>



        <!-- FOOTER -->

        <footer class="footer">

            <span>

                © 2026 Burundi Transport

            </span>


            <span>

                Administration

            </span>

        </footer>


    </main>



    <!-- =====================================================
         MODAL AJOUT / MODIFICATION
    ====================================================== -->

    <div
        class="modal-container"
        id="tripModal"
    >

        <div class="modal">


            <div class="modal-header">

                <div>

                    <h2 id="modalTitle">

                        Ajouter un trajet

                    </h2>


                    <p>

                        Remplissez les informations du trajet.

                    </p>

                </div>


                <button
                    class="modal-close"
                    id="closeModal"
                >

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </div>



            <form id="tripForm">


                <input
                    type="hidden"
                    id="tripId"
                >


                <div class="form-grid">


                    <!-- Départ -->

                    <div class="form-group">

                        <label for="departure">

                            Ville de départ

                        </label>


                        <select id="departure" required>

                            <option value="">

                                Sélectionner

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



                    <!-- Destination -->

                    <div class="form-group">

                        <label for="destination">

                            Destination

                        </label>


                        <select id="destination" required>

                            <option value="">

                                Sélectionner

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



                    <!-- Date -->

                    <div class="form-group">

                        <label for="tripDate">

                            Date

                        </label>


                        <input
                            type="date"
                            id="tripDate"
                            required
                        >

                    </div>



                    <!-- Heure -->

                    <div class="form-group">

                        <label for="tripTime">

                            Heure de départ

                        </label>


                        <input
                            type="time"
                            id="tripTime"
                            required
                        >

                    </div>



                    <!-- Prix -->

                    <div class="form-group">

                        <label for="tripPrice">

                            Prix (BIF)

                        </label>


                        <input
                            type="number"
                            id="tripPrice"
                            min="0"
                            placeholder="30000"
                            required
                        >

                    </div>



                    <!-- Places -->

                    <div class="form-group">

                        <label for="tripSeats">

                            Nombre de places

                        </label>


                        <input
                            type="number"
                            id="tripSeats"
                            min="1"
                            max="100"
                            placeholder="28"
                            required
                        >

                    </div>



                    <!-- Statut -->

                    <div class="form-group full-width">

                        <label for="tripStatus">

                            Statut

                        </label>


                        <select id="tripStatus">

                            <option value="actif">

                                Actif

                            </option>

                            <option value="inactif">

                                Inactif

                            </option>

                        </select>

                    </div>


                </div>



                <div class="form-error" id="formError">

                </div>



                <div class="modal-actions">

                    <button
                        type="button"
                        class="cancel-button"
                        id="cancelButton"
                    >

                        Annuler

                    </button>


                    <button
                        type="submit"
                        class="save-button"
                    >

                        <i class="fa-solid fa-floppy-disk"></i>

                        Enregistrer

                    </button>

                </div>


            </form>

        </div>

    </div>



    <!-- =====================================================
         JS
    ====================================================== -->
    <script src="theme.js"></script>
    <script src="trajets.js"></script>
	<script src="notification-badge.js"></script>

</body>

</html>