
<?php
require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestion des réservations - Burundi Transport</title>

    <link rel="stylesheet" href="reservations.css">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
		  
	<link rel="stylesheet" href="theme.css">
</head>

<body>

    <!-- SIDEBAR -->
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

            <a href="reservations.php" class="active">
                <i class="fa-solid fa-ticket"></i>
                <span>Réservations</span>
            </a>

            <a href="utilisateurs.php">
                <i class="fa-solid fa-users"></i>
                <span>Utilisateurs</span>
            </a>

            <a href="statistiques.php">
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


    <!-- CONTENU PRINCIPAL -->
    <main class="main-content">

        <!-- HEADER -->
        <header class="top-header">

            <button id="menuButton" class="menu-button">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="header-title">
                <h1>Gestion des réservations</h1>
                <p>Gérez les réservations des clients</p>
            </div>

            <div class="header-actions">

                <a href="notifications.php" class="header-icon">
                    <i class="fa-regular fa-bell"></i>
                    <span>3</span>
                </a>

                <div class="header-admin">


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


        <!-- CONTENU -->
        <section class="page-content">

            <!-- STATISTIQUES -->
            <div class="stats-grid">

                <div class="stat-card">

                    <div class="stat-icon blue">
                        <i class="fa-solid fa-ticket"></i>
                    </div>

                    <div>
                        <span>Total réservations</span>
                        <strong id="totalReservations">0</strong>
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon green">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>

                    <div>
                        <span>Confirmées</span>
                        <strong id="reservationsConfirmees">0</strong>
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon orange">
                        <i class="fa-solid fa-clock"></i>
                    </div>

                    <div>
                        <span>En attente</span>
                        <strong id="reservationsAttente">0</strong>
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon red">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </div>

                    <div>
                        <span>Annulées</span>
                        <strong id="reservationsAnnulees">0</strong>
                    </div>

                </div>

            </div>


            <!-- BARRE DE RECHERCHE ET FILTRES -->
            <div class="filters-card">

                <div class="search-box">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="searchInput"
                        placeholder="Rechercher par référence, client ou destination..."
                    >

                </div>


                <div class="filter-group">

                    <label for="statusFilter">Statut</label>

                    <select id="statusFilter">

                        <option value="tous">Tous les statuts</option>
                        <option value="confirmee">Confirmée</option>
                        <option value="attente">En attente</option>
                        <option value="annulee">Annulée</option>

                    </select>

                </div>


                <div class="filter-group">

                    <label for="dateFilter">Date du voyage</label>

                    <input
                        type="date"
                        id="dateFilter"
                    >

                </div>


                <button id="resetFilters" class="reset-button">
                    <i class="fa-solid fa-rotate-left"></i>
                    Réinitialiser
                </button>

            </div>


            <!-- TABLEAU -->
            <div class="table-card">

                <div class="table-header">

                    <div>
                        <h2>Liste des réservations</h2>
                        <p id="resultCount">0 réservation</p>
                    </div>

                    <button id="refreshButton" class="refresh-button">
                        <i class="fa-solid fa-rotate"></i>
                        Actualiser
                    </button>

                </div>


                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>Référence</th>
                                <th>Client</th>
                                <th>Trajet</th>
                                <th>Date</th>
                                <th>Heure</th>
                                <th>Places</th>
                                <th>Total</th>
                                <th>Statut</th>
                                <th>Actions</th>

                            </tr>

                        </thead>


                        <tbody id="reservationsTableBody">

                        </tbody>

                    </table>

                </div>


                <div id="emptyState" class="empty-state">

                    <div class="empty-icon">
                        <i class="fa-solid fa-ticket"></i>
                    </div>

                    <h3>Aucune réservation trouvée</h3>

                    <p>
                        Aucune réservation ne correspond aux critères sélectionnés.
                    </p>

                </div>

            </div>

        </section>


        <!-- FOOTER -->
        <footer>

            <p>
                © 2026 Burundi Transport. Tous droits réservés.
            </p>

        </footer>

    </main>


    <!-- MODAL DETAILS -->
    <div id="reservationModal" class="modal">

        <div class="modal-content">

            <div class="modal-header">

                <div>
                    <h2>Détails de la réservation</h2>
                    <p>Informations complètes du client</p>
                </div>

                <button id="closeModal" class="close-modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>

            </div>


            <div id="reservationDetails" class="reservation-details">

            </div>


            <div class="modal-footer">

                <button id="closeModalBottom" class="cancel-button">
                    Fermer
                </button>

            </div>

        </div>

    </div>
    <script src="notification-badge.js"></script>
    <script src="theme.js"></script>
    <script src="reservations.js"></script>
	<script src="header-admin.js"></script>

</body>
</html>