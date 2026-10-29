
<?php
require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestion des utilisateurs - Burundi Transport</title>

    <link rel="stylesheet" href="theme.css">
    <link rel="stylesheet" href="utilisateurs.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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

            <a href="reservations.php">
                <i class="fa-solid fa-ticket"></i>
                <span>Réservations</span>
            </a>

            <a href="utilisateurs.php" class="active">
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
                <div class="admin-avatar">A</div>

                <div>
                    <strong>Administrateur</strong>
                    <span>Admin</span>
                </div>
            </div>

            <button id="logoutButton" class="logout-button" type="button">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Se déconnecter</span>
            </button>
        </div>

    </aside>

    <!-- CONTENU PRINCIPAL -->
    <main class="main-content">

        <!-- HEADER -->
        <header class="top-header">

            <button id="menuButton" class="menu-button" type="button">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="header-title">
                <h1>Gestion des utilisateurs</h1>
                <p>Gérez les comptes des clients et des administrateurs</p>
            </div>

            <div class="header-actions">

                <a href="notifications.php" class="header-icon">
                    <i class="fa-regular fa-bell"></i>
                </a>

                <div class="header-admin">
                    <div class="admin-avatar" id="sidebarAvatar">A</div>

<div>
    <strong id="sidebarName">Chargement...</strong>
    <small id="sidebarRole">Chargement...</small>
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
                        <i class="fa-solid fa-users"></i>
                    </div>

                    <div>
                        <span>Total utilisateurs</span>
                        <strong id="totalUtilisateurs">0</strong>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fa-solid fa-user-check"></i>
                    </div>

                    <div>
                        <span>Comptes actifs</span>
                        <strong id="utilisateursActifs">0</strong>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fa-solid fa-user-lock"></i>
                    </div>

                    <div>
                        <span>Comptes bloqués</span>
                        <strong id="utilisateursBloques">0</strong>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>

                    <div>
                        <span>Administrateurs</span>
                        <strong id="nombreAdmins">0</strong>
                    </div>
                </div>

            </div>

            <!-- BARRE OUTILS -->
            <div class="toolbar-card">

                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="searchInput"
                        placeholder="Rechercher par nom ou email..."
                    >
                </div>

                <div class="filter-group">
                    <label for="roleFilter">Rôle</label>

                    <select id="roleFilter">
                        <option value="tous">Tous les rôles</option>
                        <option value="client">Client</option>
                        <option value="admin">Administrateur</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="statusFilter">Statut</label>

                    <select id="statusFilter">
                        <option value="tous">Tous les statuts</option>
                        <option value="active">Actif</option>
                        <option value="bloque">Bloqué</option>
                        <option value="inactif">Inactif</option>
                    </select>
                </div>

                <button id="resetFilters" class="reset-button" type="button">
                    <i class="fa-solid fa-rotate-left"></i>
                    Réinitialiser
                </button>

                <button id="addUserButton" class="add-button" type="button">
                    <i class="fa-solid fa-user-plus"></i>
                    Ajouter
                </button>

            </div>

            <!-- TABLE -->
            <div class="table-card">

                <div class="table-header">

                    <div>
                        <h2>Liste des utilisateurs</h2>
                        <p id="resultCount">0 utilisateur</p>
                    </div>

                    <button id="refreshButton"
                            class="refresh-button"
                            type="button">
                        <i class="fa-solid fa-rotate"></i>
                        Actualiser
                    </button>

                </div>

                <div class="table-container">

                    <table>
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Rôle</th>
                                <th>Date inscription</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody id="usersTableBody"></tbody>
                    </table>

                </div>

                <div id="emptyState" class="empty-state">

                    <div class="empty-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>

                    <h3>Aucun utilisateur trouvé</h3>

                    <p>Aucun utilisateur ne correspond aux critères.</p>

                </div>

            </div>

        </section>

        <!-- FOOTER -->
        <footer>
            <p>© 2026 Burundi Transport. Tous droits réservés.</p>
        </footer>

    </main>

    <!-- MODAL AJOUT / MODIFICATION -->
    <div id="userModal" class="modal">

        <div class="modal-content">

            <div class="modal-header">

                <div>
                    <h2 id="modalTitle">Ajouter un utilisateur</h2>
                    <p>Remplissez les informations du compte</p>
                </div>

                <button id="closeModal"
                        class="close-modal"
                        type="button">
                    <i class="fa-solid fa-xmark"></i>
                </button>

            </div>

            <form id="userForm">

                <input type="hidden" id="userId" name="user_id">

                <div class="form-grid">

                    <div class="form-group">
                        <label for="nom">Nom</label>

                        <input
                            type="text"
                            id="nom"
                            name="nom"
                            maxlength="100"
                            autocomplete="family-name"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="prenom">Prénom</label>

                        <input
                            type="text"
                            id="prenom"
                            name="prenom"
                            maxlength="100"
                            autocomplete="given-name"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="email">E-mail</label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            maxlength="150"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="telephone">Téléphone</label>

                        <input
                            type="tel"
                            id="telephone"
                            name="telephone"
                            maxlength="30"
                            placeholder="+257..."
                            autocomplete="tel"
                            required
                        >
                    </div>

                    <!-- Mot de passe : nécessaire pour un nouvel utilisateur -->
                    <div class="form-group"
                         id="passwordGroup"
                         style="display: none;">

                        <label for="password">Mot de passe</label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            minlength="8"
                            maxlength="128"
                            autocomplete="new-password"
                            placeholder="Au moins 8 caractères"
                        >

                        <small>
                            Utilisez au moins 8 caractères.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="role">Rôle</label>

                        <select id="role" name="role" required>
                            <option value="client">Client</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="statut">Statut</label>

                        <select id="statut" name="statut" required>
                            <option value="actif">Actif</option>
                            <option value="bloque">Bloqué</option>
                            <option value="inactif">Inactif</option>
                        </select>
                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        id="cancelModal"
                        class="cancel-button">
                        Annuler
                    </button>

                    <button
                        type="submit"
                        class="save-button">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Enregistrer
                    </button>

                </div>

            </form>

        </div>

    </div>
    <script src="notification-badge.js"></script>
    <script src="theme.js"></script>
    <script src="utilisateurs.js"></script>
	<script src="header-admin.js"></script>

</body>
</html>
