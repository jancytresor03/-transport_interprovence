
<?php
require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Notifications - Burundi Transport</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="theme.css">
    <link rel="stylesheet" href="notifications.css">
</head>

<body>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">

        <div class="sidebar-logo">
            <div class="logo-icon">
                <i class="fa-solid fa-bus"></i>
            </div>

            <div>
                <h2>Burundi Transport</h2>
                <span>Administration</span>
            </div>
        </div>

        <nav class="sidebar-nav">

            <p class="nav-title">MENU PRINCIPAL</p>

            <a href="admin-dashboard.php" class="nav-link">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>

            <a href="trajets.php" class="nav-link">
                <i class="fa-solid fa-route"></i>
                <span>Trajets</span>
            </a>

            <a href="reservations.php" class="nav-link">
                <i class="fa-solid fa-calendar-check"></i>
                <span>Réservations</span>
            </a>

            <a href="utilisateurs.php" class="nav-link">
                <i class="fa-solid fa-users"></i>
                <span>Utilisateurs</span>
            </a>

            <a href="statistiques.php" class="nav-link">
                <i class="fa-solid fa-chart-line"></i>
                <span>Statistiques</span>
            </a>

            <p class="nav-title">COMMUNICATION</p>

            <a href="notifications.php" class="nav-link active">
                <i class="fa-solid fa-bell"></i>
                <span>Notifications</span>
                <span class="menu-badge" id="menuBadge">0</span>
            </a>

            <a href="activites.php" class="nav-link">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Activités</span>
            </a>
			
			 <a href="chat.php" class="nav-link">
                   <i class="fa-solid fa-comments"></i>
                    <span>Message</span>
                </a>

            <p class="nav-title">SYSTÈME</p>

            <a href="profil.php" class="nav-link">
                <i class="fa-solid fa-user"></i>
                <span>Mon profil</span>
            </a>
			
			 <a href="historique-connexions.php" class="nav-link">
                   <i class="fa-solid fa-shield-halved"></i>
                    <span>Historique connexion</span>
                </a>
				
				 <a href="documents.php" class="nav-link">
                <i class="fa-solid fa-folder-open"></i>
                Documents
            </a>

            <a href="parametres.php" class="nav-link">
                <i class="fa-solid fa-gear"></i>
                <span>Paramètres</span>
            </a>
			
			<a href="archives.php" class="nav-link">
                    <i class="fa-solid fa-box-archive"></i>
                    <span>Archives</span>
                </a>

        </nav>

        <div class="sidebar-bottom">

            <div class="admin-profile">
                <div class="admin-avatar">A</div>

                <div class="admin-info">
                    <strong>Administrateur</strong>
                    <span>admin@gmail.com</span>
                </div>
            </div>

            <a href="../logout.php" class="logout-button">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Se déconnecter</span>
            </a>

        </div>

    </aside>


    <!-- CONTENU PRINCIPAL -->
    <main class="main-content">

        <!-- HEADER -->
        <header class="top-header">

            <button class="menu-button" id="menuButton" type="button">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="header-right">

                <!-- Lien corrigé : notifications.php -->
                <a href="notifications.php" class="notification-button">

                    <i class="fa-solid fa-bell"></i>

                    <span class="notification-count" id="headerBadge">
                        0
                    </span>

                </a>

                <div class="header-profile">

                    
                <div class="header-avatar" id="headerAvatar">
                           A
                </div>

                <div class="header-admin-text">
                    <strong id="headerAdminName">Chargement...</strong>
                    <span id="headerAdminRole">Chargement...</span>
                </div>


                </div>

            </div>

        </header>


        <!-- PAGE -->
        <section class="page-container">

            <!-- TITRE -->
            <div class="page-heading">

                <div>
                    <span class="page-label">
                        COMMUNICATION
                    </span>

                    <h1>Notifications</h1>

                    <p>
                        Consultez et gérez les notifications du système.
                    </p>
                </div>

                <button class="primary-button"
                        id="markAllButton"
                        type="button">

                    <i class="fa-solid fa-check-double"></i>
                    Tout marquer comme lu

                </button>

            </div>


            <!-- STATISTIQUES -->
            <div class="notification-stats">

                <div class="stat-card">

                    <div class="stat-icon blue">
                        <i class="fa-solid fa-bell"></i>
                    </div>

                    <div>
                        <span>Total notifications</span>
                        <strong id="totalNotifications">0</strong>
                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-icon gold">
                        <i class="fa-solid fa-envelope"></i>
                    </div>

                    <div>
                        <span>Non lues</span>
                        <strong id="notificationsNonLues">0</strong>
                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-icon green">
                        <i class="fa-solid fa-check"></i>
                    </div>

                    <div>
                        <span>Lues</span>
                        <strong id="notificationsLues">0</strong>
                    </div>

                </div>

            </div>


            <!-- FILTRES -->
            <div class="filter-card">

                <div class="search-box">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="searchInput"
                        placeholder="Rechercher une notification..."
                    >

                </div>

                <!-- Types correspondant à la base de données -->
                <select id="typeFilter">

                    <option value="tous">Tous les types</option>
                    <option value="reservation">Réservation</option>
                    <option value="paiement">Paiement</option>
                    <option value="trajet">Trajet</option>
                    <option value="systeme">Système</option>
                    <option value="securite">Sécurité</option>

                </select>

                <select id="statusFilter">

                    <option value="tous">Tous les statuts</option>
                    <option value="non-lue">Non lues</option>
                    <option value="lue">Lues</option>

                </select>

            </div>


            <!-- LISTE DES NOTIFICATIONS -->
            <div class="notifications-card">

                <div class="card-header">

                    <div>
                        <h2>Liste des notifications</h2>

                        <p id="resultCount">
                            0 notification
                        </p>
                    </div>

                    <button class="refresh-button"
                            id="refreshButton"
                            type="button">

                        <i class="fa-solid fa-rotate"></i>
                        Actualiser

                    </button>

                </div>

                <div id="notificationsList" class="notifications-list">
                    <!-- Rempli par notifications.js depuis MySQL -->
                </div>

                <!-- MESSAGE VIDE -->
                <div id="emptyState"
                     class="empty-state"
                     style="display: none;">

                    <div class="empty-icon">
                        <i class="fa-regular fa-bell-slash"></i>
                    </div>

                    <h3>Aucune notification</h3>

                    <p>
                        Aucune notification ne correspond à votre recherche.
                    </p>

                </div>

            </div>

        </section>


        <!-- FOOTER -->
        <footer class="footer">

            <p>
                © 2026 Burundi Transport. Tous droits réservés.
            </p>

            <span>Administration</span>

        </footer>

    </main>


    <!-- JAVASCRIPT -->
	<script src="notification-badge.js"></script>
    <script src="theme.js"></script>
    <script src="notifications.js"></script>
	<script src="header-admin.js"></script>

</body>
</html>
