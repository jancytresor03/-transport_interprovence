
<?php
require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");
?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Statistiques - Burundi Transport</title>
	
    <link rel="stylesheet" href="theme.css">
    <link rel="stylesheet" href="statistiques.css">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>


<body>


    <!-- =========================
         SIDEBAR
    ========================== -->

    <aside class="sidebar">


        <div class="logo">

            <div class="logo-icon">

                <i class="fa-solid fa-bus"></i>

            </div>


            <div>

                <h2>Burundi</h2>

                <span>Transport</span>

            </div>

        </div>


        <nav class="menu">


            <a href="admin-dashboard.php">

                <i class="fa-solid fa-chart-line"></i>

                <span>Dashboard</span>

            </a>


            <a href="trajets.php">

                <i class="fa-solid fa-route"></i>

                <span>Trajets</span>

            </a>


            <a href="reservations.php">

                <i class="fa-solid fa-ticket"></i>

                <span>Réservations</span>

            </a>


            <a href="utilisateurs.php">

                <i class="fa-solid fa-users"></i>

                <span>Utilisateurs</span>

            </a>


            <a href="statistiques.php" class="active">

                <i class="fa-solid fa-chart-pie"></i>

                <span>Statistiques</span>

            </a>


            <a href="notifications.php">

                <i class="fa-solid fa-bell"></i>

                <span>Notifications</span>

            </a>


            <a href="activites.php">

                <i class="fa-solid fa-clock-rotate-left"></i>

                <span>Activités</span>

            </a>
			
			 <a href="chat.php" class="menu-link">
                   <i class="fa-solid fa-comments"></i>
                    <span>Message</span>
                </a>


            <a href="profil.php">

                <i class="fa-solid fa-user"></i>

                <span>Mon profil</span>

            </a>
			
			 <a href="historique-connexions.php" class="menu-link">
                   <i class="fa-solid fa-shield-halved"></i>
                    <span>Historique connexion</span>
                </a>
				
				 <a href="documents.php" class="menu-link">
                <i class="fa-solid fa-folder-open"></i>
                Documents
            </a>


            <a href="parametres.php">

                <i class="fa-solid fa-gear"></i>

                <span>Paramètres</span>

            </a>
			
			<a href="archives.php" class="menu-link">
                    <i class="fa-solid fa-box-archive"></i>
                    <span>Archives</span>
                </a>


        </nav>


        <div class="sidebar-bottom">


            <div class="admin-mini">

                <div class="admin-avatar">
                    A
                </div>


                <div>

                    <strong>Administrateur</strong>

                    <span>Admin</span>

                </div>

            </div>


            <button id="logoutButton" class="logout-button">

                <i class="fa-solid fa-right-from-bracket"></i>

               <a href="../logout.php">Se déconnecter</a>

            </button>

        </div>

    </aside>



    <!-- =========================
         CONTENU
    ========================== -->

    <main class="main-content">


        <!-- HEADER -->

        <header class="top-header">


            <button id="menuButton" class="menu-button">

                <i class="fa-solid fa-bars"></i>

            </button>


            <div class="header-title">

                <h1>Statistiques</h1>

                <p>
                    Analyse des activités de Burundi Transport
                </p>

            </div>


            <div class="header-actions">


                <a href="notifications.php" class="header-icon">

                    <i class="fa-regular fa-bell"></i>

                    <span>3</span>

                </a>


                <div class="header-admin">

                  <div class="admin-avatar" id="sidebarAvatar">
    A
</div>

<div>
    <strong id="sidebarName">Chargement...</strong>
    <small id="sidebarRole">Chargement...</small>
</div>

                </div>

            </div>

        </header>



        <!-- =========================
             PAGE CONTENT
        ========================== -->

        <section class="page-content">



<!-- OUTILS DES RAPPORTS -->

<div class="report-toolbar">

    <div class="report-toolbar-heading">
        <div>
            <span class="section-label">OUTILS DE GESTION</span>
            <h2>Rapports et analyses</h2>
            <p>Générez, imprimez et analysez les données de Burundi Transport.</p>
        </div>
    </div>

    <div class="report-buttons">

        <button type="button" id="printReportButton" class="report-btn report-pdf">
            <i class="fa-solid fa-file-pdf"></i>
            Télécharger PDF / Imprimer
        </button>

        <button type="button" id="exportExcelButton" class="report-btn report-excel">
            <i class="fa-solid fa-file-excel"></i>
            Exporter Excel
        </button>

        <button type="button" id="periodicReportButton" class="report-btn">
            <i class="fa-solid fa-calendar-days"></i>
            Rapports périodiques
        </button>

        <button type="button" id="customReportButton" class="report-btn">
            <i class="fa-solid fa-sliders"></i>
            Rapport personnalisé
        </button>

        <button type="button" id="comparisonButton" class="report-btn">
            <i class="fa-solid fa-chart-column"></i>
            Analyse comparative
        </button>
		
		
<section id="comparisonPanel" class="table-card" hidden>
    <h3>Analyse comparative</h3>
    <p>Comparez les statistiques de deux périodes.</p>

    <div class="report-filters">
        <div class="report-field">
            <label for="comparisonStart1">Début de la première période</label>
            <input type="date" id="comparisonStart1">
        </div>

        <div class="report-field">
            <label for="comparisonEnd1">Fin de la première période</label>
            <input type="date" id="comparisonEnd1">
        </div>

        <div class="report-field">
            <label for="comparisonStart2">Début de la deuxième période</label>
            <input type="date" id="comparisonStart2">
        </div>

        <div class="report-field">
            <label for="comparisonEnd2">Fin de la deuxième période</label>
            <input type="date" id="comparisonEnd2">
        </div>
    </div>

    <p id="comparisonMessage" role="status"></p>

    <button type="button" id="runComparisonButton" class="report-btn">
        Comparer les périodes
    </button>

    <div id="comparisonResults" hidden>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Indicateur</th>
                        <th>Première période</th>
                        <th>Deuxième période</th>
                        <th>Différence</th>
                        <th>Évolution (%)</th>
                    </tr>
                </thead>
                <tbody id="comparisonResultsBody"></tbody>
            </table>
        </div>

        <div class="chart-container">
            <canvas id="comparisonChart"></canvas>
        </div>
    </div>
</section>


        <button type="button" id="forecastButton" class="report-btn">
            <i class="fa-solid fa-chart-line"></i>
            Prévisions statistiques
        </button>


<section id="forecastPanel" class="table-card" hidden>
    <h3>Prévisions statistiques</h3>

    <label for="forecastMonths">Nombre de mois à prévoir</label>
    <select id="forecastMonths">
        <option value="3">3 mois</option>
        <option value="6">6 mois</option>
        <option value="12">12 mois</option>
    </select>

    <p id="forecastMessage" role="status"></p>

    <button type="button" id="runForecastButton" class="report-btn">
        Calculer les prévisions
    </button>

    <div id="forecastResults" hidden>
        <div class="chart-container">
            <canvas id="forecastReservationsChart"></canvas>
        </div>

        <div class="chart-container">
            <canvas id="forecastRevenueChart"></canvas>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Mois prévu</th>
                    <th>Réservations estimées</th>
                    <th>Revenus estimés (BIF)</th>
                </tr>
            </thead>
            <tbody id="forecastResultsBody"></tbody>
        </table>
    </div>
</section>


    </div>

  
<div id="reportControls" class="report-controls" hidden>

    <h3 id="reportControlsTitle">Rapport personnalisé</h3>

    <div class="report-filters">

        <!-- TYPE DE RAPPORT -->
        <div class="report-field">
            <label for="reportType">Type de rapport</label>

            <select id="reportType">
                <option value="trajets">Rapport des trajets</option>
                <option value="reservations">Rapport des réservations</option>
                <option value="revenus">Rapport des revenus</option>
                <option value="utilisateurs">Rapport des utilisateurs</option>
                <option value="complet">Rapport complet</option>
            </select>
        </div>

        <!-- DATE DE DEBUT -->
        <div class="report-field">
            <label for="reportStartDate">Date de début (facultative)</label>
            <input type="date" id="reportStartDate">
        </div>

        <!-- DATE DE FIN -->
        <div class="report-field">
            <label for="reportEndDate">Date de fin (facultative)</label>
            <input type="date" id="reportEndDate">
        </div>

    </div>

    <p id="reportMessage" role="status"></p>

    <button type="button" id="applyReportButton" class="report-btn report-pdf">
        <i class="fa-solid fa-check"></i>
        Générer le rapport
    </button>

</div>
<div id="reportResults" class="table-card" hidden>
    <div class="table-header">
        <div>
            <h3 id="reportResultsTitle">Résultat du rapport</h3>
            <p id="reportResultsDescription"></p>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead id="reportResultsHead"></thead>
            <tbody id="reportResultsBody"></tbody>
        </table>
    </div>
</div>








        <p id="reportMessage" role="status"></p>

        <button type="button" id="applyReportButton" class="report-btn report-pdf">
            <i class="fa-solid fa-check"></i>
            Appliquer
        </button>

    </div>

</div>





            <!-- TITRE -->

            <div class="page-heading">

                <div>

                    <span class="section-label">
                        ANALYSE
                    </span>

                    <h2>
                        Vue générale
                    </h2>

                    <p>
                        Consultez les principales statistiques
                        de la plateforme.
                    </p>

                </div>


                <button id="refreshButton" class="refresh-button">

                    <i class="fa-solid fa-rotate"></i>

                    Actualiser

                </button>

            </div>



            <!-- =========================
                 CARTES STATISTIQUES
            ========================== -->

            <div class="stats-grid">


                <div class="stat-card">


                    <div class="stat-icon blue">

                        <i class="fa-solid fa-ticket"></i>

                    </div>


                    <div>

                        <span>Total réservations</span>

                        <strong id="totalReservations">
                            0
                        </strong>

                        <small>
                            Réservations enregistrées
                        </small>

                    </div>

                </div>



                <div class="stat-card">


                    <div class="stat-icon green">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>


                    <div>

                        <span>Réservations confirmées</span>

                        <strong id="reservationsConfirmees">
                            0
                        </strong>

                        <small>
                            Réservations validées
                        </small>

                    </div>

                </div>



                <div class="stat-card">


                    <div class="stat-icon orange">

                        <i class="fa-solid fa-money-bill-wave"></i>

                    </div>


                    <div>

                        <span>Revenus</span>

                        <strong id="totalRevenus">
                            0 BIF
                        </strong>

                        <small>
                            Revenus confirmés
                        </small>

                    </div>

                </div>



                <div class="stat-card">


                    <div class="stat-icon purple">

                        <i class="fa-solid fa-users"></i>

                    </div>


                    <div>

                        <span>Utilisateurs</span>

                        <strong id="totalUtilisateurs">
                            0
                        </strong>

                        <small>
                            Comptes enregistrés
                        </small>

                    </div>

                </div>


            </div>



            <!-- =========================
                 GRAPHIQUE RESERVATIONS
            ========================== -->

            <div class="chart-card large-chart">


                <div class="chart-header">

                    <div>

                        <h3>
                            Évolution des réservations
                        </h3>

                        <p>
                            Nombre de réservations par mois
                        </p>

                    </div>


                    <div class="chart-icon">

                        <i class="fa-solid fa-chart-line"></i>

                    </div>

                </div>


                <div class="chart-container">

                    <canvas id="reservationsChart"></canvas>

                </div>

            </div>



            <!-- =========================
                 DEUX GRAPHIQUES
            ========================== -->

            <div class="charts-grid">


                <!-- STATUTS -->

                <div class="chart-card">


                    <div class="chart-header">

                        <div>

                            <h3>
                                Statut des réservations
                            </h3>

                            <p>
                                Répartition des réservations
                            </p>

                        </div>


                        <div class="chart-icon">

                            <i class="fa-solid fa-chart-pie"></i>

                        </div>

                    </div>


                    <div class="chart-container pie-container">

                        <canvas id="statutChart"></canvas>

                    </div>

                </div>



                <!-- DESTINATIONS -->

                <div class="chart-card">


                    <div class="chart-header">

                        <div>

                            <h3>
                                Destinations populaires
                            </h3>

                            <p>
                                Nombre de réservations par destination
                            </p>

                        </div>


                        <div class="chart-icon">

                            <i class="fa-solid fa-location-dot"></i>

                        </div>

                    </div>


                    <div class="chart-container">

                        <canvas id="destinationChart"></canvas>

                    </div>

                </div>


            </div>



            <!-- =========================
                 REVENUS
            ========================== -->

            <div class="chart-card large-chart">


                <div class="chart-header">

                    <div>

                        <h3>
                            Évolution des revenus
                        </h3>

                        <p>
                            Revenus générés par mois
                        </p>

                    </div>


                    <div class="chart-icon">

                        <i class="fa-solid fa-money-bill-trend-up"></i>

                    </div>

                </div>


                <div class="chart-container">

                    <canvas id="revenusChart"></canvas>

                </div>

            </div>



            <!-- =========================
                 TABLEAU RECAPITULATIF
            ========================== -->

            <div class="table-card">


                <div class="table-header">

                    <div>

                        <h3>
                            Résumé des données
                        </h3>

                        <p>
                            Informations statistiques
                            principales
                        </p>

                    </div>

                </div>


                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>Indicateur</th>

                                <th>Valeur</th>

                                <th>Détails</th>

                            </tr>

                        </thead>


                        <tbody>

                            <tr>

                                <td>
                                    Total des trajets
                                </td>

                                <td id="tableTrajets">
                                    0
                                </td>

                                <td>
                                    Trajets enregistrés
                                </td>

                            </tr>


                            <tr>

                                <td>
                                    Réservations
                                </td>

                                <td id="tableReservations">
                                    0
                                </td>

                                <td>
                                    Toutes les réservations
                                </td>

                            </tr>


                            <tr>

                                <td>
                                    Utilisateurs
                                </td>

                                <td id="tableUtilisateurs">
                                    0
                                </td>

                                <td>
                                    Clients et administrateurs
                                </td>

                            </tr>


                            <tr>

                                <td>
                                    Revenus
                                </td>

                                <td id="tableRevenus">
                                    0 BIF
                                </td>

                                <td>
                                    Réservations confirmées
                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>


        </section>



        <!-- FOOTER -->

        <footer>

            <p>
                © 2026 Burundi Transport.
                Tous droits réservés.
            </p>

        </footer>


    </main>
    <script src="notification-badge.js"></script>
    <script src="theme.js"></script>
    <script src="statistiques.js"></script>
	<script src="header-admin.js"></script>

</body>

</html>