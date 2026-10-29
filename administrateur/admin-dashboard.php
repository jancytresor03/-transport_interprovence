
<?php
require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");

require_once __DIR__ . "/../database.php";
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Admin - Burundi Transport</title>

    <!-- Police -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <!-- CSS : design original conservé -->
    <link rel="stylesheet" href="theme.css">
    <link rel="stylesheet" href="admin-dashboard.css">
</head>

<body>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">

        <!-- LOGO -->
        <div class="sidebar-header">

            <a href="admin-dashboard.php" class="logo">
                <div class="logo-icon">
                    <i class="fa-solid fa-bus"></i>
                </div>

                <div class="logo-text">
                    <strong>Burundi</strong>
                    <span>Transport</span>
                </div>
            </a>

            <button class="close-menu" id="closeMenu" type="button">
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>

        <!-- MENU DÉFILANT -->
        <div class="sidebar-scroll">

            <div class="menu-title">MENU PRINCIPAL</div>

            <nav class="menu">

                <a href="admin-dashboard.php" class="menu-link active">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>Dashboard</span>
                </a>

                <a href="trajets.php" class="menu-link">
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
                    <small id="notificationBadge">0</small>
                </a>

                <a href="activites.php" class="menu-link">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Activités</span>
                </a>
				 <a href="chat.php" class="menu-link">
                   <i class="fa-solid fa-comments"></i>
                    <span>Message</span>
                </a>

            </nav>

            <div class="menu-title">MON COMPTE</div>

            <nav class="menu">

                <a href="profil.php" class="menu-link">
                    <i class="fa-solid fa-user"></i>
                    <span>Mon profil</span>
                </a>
				
				 <a href="historique-connexions.php" class="menu-link">
                   <i class="fa-solid fa-shield-halved"></i>
                    <span>Historique connexion</span>
                </a>

                <a href="parametres.php" class="menu-link">
                    <i class="fa-solid fa-gear"></i>
                    <span>Paramètres</span>
                </a>
				
				 <a href="documents.php" class="menu-link">
                <i class="fa-solid fa-folder-open"></i>
                Documents
            </a>
			
			<a href="partages-documents.php" class="menu-link">
                     <i class="fa-solid fa-share-nodes"></i>
                    <span>Partage des documents</span>
            </a>
			
			
			
			<a href="documents-partages.php" class="menu-link">
                   <i class="fa-solid fa-folder-open"></i>
                        <span>Documents partages</span>
            </a>
			

                <a href="archives.php" class="menu-link">
                    <i class="fa-solid fa-box-archive"></i>
                    <span>Archives</span>
                </a>

                <a href="aide.php" class="menu-link">
                    <i class="fa-solid fa-circle-question"></i>
                    <span>Aide</span>
                </a>

            </nav>

        </div>

        <!-- BAS DE LA SIDEBAR -->
        <div class="sidebar-footer">

            <div class="admin-box">

                <div class="admin-avatar" id="sidebarAvatar">
                    AD
                </div>

                <div class="admin-information">
                    <strong id="sidebarAdminName">Administrateur</strong>
                    <span id="sidebarAdminEmail">admin@gmail.com</span>
                </div>

            </div>

            <!-- Déconnexion : lien valide, sans lien imbriqué dans un bouton -->
            <a
                class="logout-button"
                id="logoutButton"
                href="../logout.php"
            >
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Se déconnecter</span>
            </a>

        </div>

    </aside>

    <!-- OVERLAY MOBILE -->
    <div class="overlay" id="overlay"></div>

    <!-- CONTENU PRINCIPAL -->
    <main class="main-content">

        <!-- HEADER -->
        <header class="top-header">

            <div class="header-left">

                <button
                    class="mobile-menu-button"
                    id="openMenu"
                    type="button"
                >
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div>
                    <span class="header-small">Administration</span>
                    <h1>Dashboard</h1>
                </div>

            </div>

            <div class="header-right">

                <!-- Notifications -->
                <button
                    class="notification-button"
                    id="notificationButton"
                    type="button"
                    aria-label="Consulter les notifications"
                >
                    <i class="fa-regular fa-bell"></i>
                   <!-- <span class="notification-dot"></span> --!>
                </button>

                <!-- Administrateur -->
                <div class="header-admin">

                    <div class="header-avatar" id="headerAvatar">
                           AD
                    </div>

                    <div class="header-admin-text">
                       <strong id="headerAdminName">Chargement...</strong>
                       <span id="headerAdminRole">Chargement...</span>
                    </div>

                </div>

            </div>

        </header>

        <!-- DASHBOARD -->
        <section class="dashboard">

            <!-- BIENVENUE -->
            <div class="welcome">

                <div class="welcome-text">

                    <span>ESPACE ADMINISTRATEUR</span>

                    <h2 id="welcomeAdminName">
                        Bienvenue, Administrateur
                    </h2>

                    <p>
                        Gérez les trajets, les réservations
                        et les utilisateurs de Burundi Transport.
                    </p>

                </div>

                <button
                    class="add-trip-button"
                    id="addTripButton"
                    type="button"
                >
                    <i class="fa-solid fa-plus"></i>
                    Ajouter un trajet
                </button>

            </div>

            <!-- STATISTIQUES -->
            <div class="statistics">

                <!-- Utilisateurs -->
                <div class="stat-card">

                    <div class="stat-header">

                        <div class="stat-icon blue">
                            <i class="fa-solid fa-users"></i>
                        </div>

                        <span class="stat-growth">Inscrits</span>

                    </div>

                    <h3 id="totalUtilisateurs">0</h3>

                    <p>Utilisateurs</p>

                    <span class="stat-description">
                        utilisateurs inscrits
                    </span>

                </div>

                <!-- Réservations -->
                <div class="stat-card">

                    <div class="stat-header">

                        <div class="stat-icon gold">
                            <i class="fa-solid fa-ticket"></i>
                        </div>

                        <span class="stat-growth">Ce mois</span>

                    </div>

                    <h3 id="reservationsMois">0</h3>

                    <p>Réservations</p>

                    <span class="stat-description">
                        réservations créées ce mois-ci
                    </span>

                </div>

                <!-- Trajets -->
                <div class="stat-card">

                    <div class="stat-header">

                        <div class="stat-icon green">
                            <i class="fa-solid fa-route"></i>
                        </div>

                        <span class="stat-active">Actifs</span>

                    </div>

                    <h3 id="totalTrajets">0</h3>

                    <p>Trajets</p>

                    <span class="stat-description">
                        trajets actifs à venir
                    </span>

                </div>

                <!-- Revenus -->
                <div class="stat-card">

                    <div class="stat-header">

                        <div class="stat-icon purple">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>

                        <span class="stat-growth">Ce mois</span>

                    </div>

                    <h3 id="revenusMois">0 BIF</h3>

                    <p>Revenus</p>

                    <span class="stat-description">
                        réservations confirmées ce mois-ci
                    </span>

                </div>

            </div>

            <!-- RÉSERVATIONS ET TRAJETS -->
            <div class="content-grid">

                <!-- RÉSERVATIONS RÉCENTES -->
                <div class="card">

                    <div class="card-header">

                        <div>
                            <h2>Réservations récentes</h2>
                            <p>Les dernières réservations</p>
                        </div>

                        <a href="reservations.php" class="view-link">
                            Voir tout
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </div>

                    <div class="table-container">

                        <table>

                            <thead>
                                <tr>
                                    <th>Référence</th>
                                    <th>Client</th>
                                    <th>Trajet</th>
                                    <th>Date</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>

                            <tbody id="reservationsTableBody">

                                <tr>
                                    <td colspan="6">
                                        Chargement des réservations...
                                    </td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

                <!-- TRAJETS À VENIR -->
                <div class="card">

                    <div class="card-header">

                        <div>
                            <h2>Trajets à venir</h2>
                            <p>Prochains départs</p>
                        </div>

                        <a href="trajets.php" class="view-link">
                            Voir tout
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </div>

                    <div class="trips" id="prochainsTrajets">

                        <p>Chargement des trajets...</p>

                    </div>

                </div>

            </div>

            <!-- ACTIONS RAPIDES ET ALERTES -->
            <div class="bottom-grid">

                <!-- ACTIONS RAPIDES -->
                <div class="card">

                    <div class="card-header">

                        <div>
                            <h2>Actions rapides</h2>
                            <p>Gestion du système</p>
                        </div>

                    </div>

                    <div class="actions">

                        <a href="trajets.php" class="action">

                            <div class="action-icon blue">
                                <i class="fa-solid fa-route"></i>
                            </div>

                            <div>
                                <strong>Gérer les trajets</strong>
                                <span>Ajouter, modifier ou supprimer</span>
                            </div>

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>

                        <a href="reservations.php" class="action">

                            <div class="action-icon gold">
                                <i class="fa-solid fa-ticket"></i>
                            </div>

                            <div>
                                <strong>Réservations</strong>
                                <span>Consulter les réservations</span>
                            </div>

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>

                        <a href="utilisateurs.php" class="action">

                            <div class="action-icon green">
                                <i class="fa-solid fa-users"></i>
                            </div>

                            <div>
                                <strong>Utilisateurs</strong>
                                <span>Gérer les comptes</span>
                            </div>

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>

                        <a href="statistiques.php" class="action">

                            <div class="action-icon purple">
                                <i class="fa-solid fa-chart-column"></i>
                            </div>

                            <div>
                                <strong>Statistiques</strong>
                                <span>Consulter les statistiques</span>
                            </div>

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>

                    </div>

                </div>

                <!-- ALERTES -->
                <div class="card">

                    <div class="card-header">

                        <div>
                            <h2>Alertes récentes</h2>
                            <p>Informations importantes</p>
                        </div>

                        <a href="notifications.php" class="view-link">
                            Voir tout
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </div>

                    <!-- Les alertes seront ajoutées dynamiquement ensuite -->
                    <div class="alerts" id="dashboardAlerts">

                        <div class="alert">

                            <div class="alert-icon info">
                                <i class="fa-solid fa-circle-info"></i>
                            </div>

                            <div class="alert-content">
                                <strong>Tableau de bord</strong>
                                <span>
                                    Les informations sont chargées depuis la base de données.
                                </span>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </section>

        <!-- FOOTER -->
        <footer class="footer">

            <span>© 2026 Burundi Transport</span>

            <span>Administration</span>

        </footer>

    </main>

    <!-- JavaScript -->
	<script src="notification-badge.js"></script>
    <script src="theme.js"></script>
    <script src="admin-dashboard.js"></script>
	<script src="header-admin.js"></script>

</body>
</html>
