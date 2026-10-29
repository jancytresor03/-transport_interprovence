
<?php

/*require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");*/

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Historique des connexions | Burundi Transport</title>

    <link rel="stylesheet" href="theme.css">

    <link rel="stylesheet" href="historique-connexions.css">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >
</head>

<body>

<div class="admin-container">

    <!-- BARRE LATERALE -->
    <aside class="sidebar" id="sidebar">

        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fa-solid fa-bus"></i>
            </div>

            <div class="sidebar-brand">
                <h2>Burundi Transport</h2>
                <span>Administration</span>
            </div>
        </div>

        <nav class="sidebar-nav">

            <a href="admin-dashboard.php" class="nav-link">
                <i class="fa-solid fa-chart-line"></i>
                <span>Dashboard</span>
            </a>

            <a href="trajets.php" class="nav-link">
                <i class="fa-solid fa-route"></i>
                <span>Trajets</span>
            </a>

            <a href="reservations.php" class="nav-link">
                <i class="fa-solid fa-ticket"></i>
                <span>Réservations</span>
            </a>

            <a href="utilisateurs.php" class="nav-link">
                <i class="fa-solid fa-users"></i>
                <span>Utilisateurs</span>
            </a>

            <a href="statistiques.php" class="nav-link">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Statistiques</span>
            </a>

            <a href="notifications.php" class="nav-link">
                <i class="fa-solid fa-bell"></i>
                <span>Notifications</span>
            </a>

            <a href="activites.php" class="nav-link">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Activités</span>
            </a>

            <a href="historique-connexions.php"
               class="nav-link active">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Historique des connexions</span>
            </a>

            <a href="profil.php" class="nav-link">
                <i class="fa-solid fa-user"></i>
                <span>Mon profil</span>
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

        <div class="sidebar-footer">
            <a href="../logout.php" class="nav-link logout-link">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Déconnexion</span>
            </a>
        </div>

    </aside>

    <!-- CONTENU PRINCIPAL -->
    <main class="main-content">

        <!-- EN-TETE -->
        <header class="topbar">

            <div class="topbar-left">
                <button
                    type="button"
                    class="menu-toggle"
                    id="menuToggle"
                    aria-label="Ouvrir le menu"
                >
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div>
                    <h1>Historique des connexions</h1>
                    <p>Suivez les connexions à votre plateforme.</p>
                </div>
            </div>

            <div class="topbar-right">
                <div class="admin-profile">
                    <div class="admin-avatar">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>

                    <div class="admin-info">
                        <strong>
                            <?= htmlspecialchars(
                                trim(
                                    ($_SESSION["prenom"] ?? "") . " "
                                    . ($_SESSION["nom"] ?? "")
                                ),
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </strong>

                        <span>Administrateur</span>
                    </div>
                </div>
            </div>

        </header>

        <!-- CONTENU -->
        <section class="page-content">

            <div class="page-heading">
                <div>
                    <h2>Vue d'ensemble</h2>
                    <p>
                        Consultez les connexions réussies et les tentatives
                        de connexion échouées enregistrées.
                    </p>
                </div>

                <button
                    type="button"
                    id="refreshButton"
                    class="btn-primary"
                >
                    <i class="fa-solid fa-rotate"></i>
                    Actualiser
                </button>
            </div>

            <!-- CARTES STATISTIQUES -->
            <div class="stats-grid">

                <article class="stat-card">
                    <div class="stat-icon stat-icon-blue">
                        <i class="fa-solid fa-list"></i>
                    </div>

                    <div>
                        <p>Total des connexions</p>
                        <h3 id="totalConnexions">0</h3>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon stat-icon-green">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>

                    <div>
                        <p>Connexions réussies</p>
                        <h3 id="connexionsReussies">0</h3>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon stat-icon-red">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </div>

                    <div>
                        <p>Connexions échouées</p>
                        <h3 id="connexionsEchouees">0</h3>
                    </div>
                </article>

            </div>

            <!-- TABLEAU -->
            <section class="history-card">

                <div class="history-header">
                    <div>
                        <h2>Journal des connexions</h2>
                        <p>Historique enregistré dans la base de données.</p>
                    </div>

                    <span id="resultCount" class="result-count">
                        0 résultat
                    </span>
                </div>

                <!-- RECHERCHE ET FILTRES -->
                <div class="filters">

                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="search"
                            id="searchInput"
                            placeholder="Rechercher un e-mail, un nom ou une IP..."
                            autocomplete="off"
                        >
                    </div>

                    <div class="filter-group">
                        <label for="statusFilter">Statut</label>

                        <select id="statusFilter">
                            <option value="tous">Tous les statuts</option>
                            <option value="reussie">Réussies</option>
                            <option value="echouee">Échouées</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="limitSelect">Par page</label>

                        <select id="limitSelect">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                    </div>

                </div>

                <!-- MESSAGE -->
                <div
                    id="historyMessage"
                    class="history-message"
                    role="status"
                    aria-live="polite"
                >
                    Chargement de l'historique...
                </div>

                <!-- TABLEAU -->
                <div class="table-wrapper">

                    <table class="history-table">

                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>E-mail</th>
                                <th>Statut</th>
                                <th>Adresse IP</th>
                                <th>Navigateur</th>
                                <th>Date de connexion</th>
                            </tr>
                        </thead>

                        <tbody id="historyTableBody">
                            <tr>
                                <td colspan="6" class="empty-cell">
                                    Chargement...
                                </td>
                            </tr>
                        </tbody>

                    </table>

                </div>

                <!-- PAGINATION -->
                <div class="pagination-container">

                    <p id="paginationInfo">
                        Page 1 sur 1
                    </p>

                    <div class="pagination-buttons">
                        <button
                            type="button"
                            id="previousPage"
                            class="pagination-button"
                            disabled
                        >
                            <i class="fa-solid fa-chevron-left"></i>
                            Précédent
                        </button>

                        <span id="currentPage">1</span>

                        <button
                            type="button"
                            id="nextPage"
                            class="pagination-button"
                            disabled
                        >
                            Suivant
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>

                </div>

            </section>

        </section>

        <footer class="page-footer">
            <p>&copy; 2026 Burundi Transport. Tous droits réservés.</p>
        </footer>

    </main>

</div>

<script src="historique-connexions.js"></script>

</body>
</html>
