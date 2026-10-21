

<?php
require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archives | Burundi Transport</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="archives.css">
</head>
<body>

    <aside class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-bus"></i>
            <span>Burundi Transport</span>
        </div>

        <div class="admin-info">
            <div class="admin-avatar"><i class="fa-solid fa-user-shield"></i></div>
            <div>
                <strong>Administrateur</strong>
                <small>Gestion du système</small>
            </div>
        </div>

        <nav class="menu">
            <a href="admin-dashboard.php">
                <i class="fa-solid fa-gauge-high"></i>
                Tableau de bord
            </a>
            <a href="trajets.php">
                <i class="fa-solid fa-route"></i>
                Gestion des trajets
            </a>
            <a href="reservations.php">
                <i class="fa-solid fa-ticket"></i>
                Réservations
            </a>
            <a href="utilisateurs.php">
                <i class="fa-solid fa-users"></i>
                Utilisateurs
            </a>
            <a href="statistiques.php">
                <i class="fa-solid fa-chart-column"></i>
                Statistiques
            </a>
            <a href="notifications.php">
                <i class="fa-solid fa-bell"></i>
                Notifications
            </a>
            <a href="activites.php">
                <i class="fa-solid fa-clock-rotate-left"></i>
                Journal des activités
            </a>
            <a href="archives.php" class="active">
                <i class="fa-solid fa-box-archive"></i>
                Archives
            </a>
            <a href="documents.php">
                <i class="fa-solid fa-folder-open"></i>
                Documents
            </a>
            <a href="parametres.php">
                <i class="fa-solid fa-gear"></i>
                Parametres
            </a>
        </nav>

        <a href="../logout.php" class="logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            Deconnexion
        </a>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Gestion des archives</h1>
                <p>Consultez, recherchez et restaurez les données archivées.</p>
            </div>

            <div class="topbar-right">
                <span class="admin-badge">
                    <i class="fa-solid fa-user-shield"></i>
                    Administrateur
                </span>
            </div>
        </header>

        <section class="stats-grid">
            <article class="stat-card">
                <div class="stat-icon blue">
                    <i class="fa-solid fa-box-archive"></i>
                </div>
                <div>
                    <p>Total des archives</p>
                    <h2 id="totalArchives">0</h2>
                </div>
            </article>

            <article class="stat-card">
                <div class="stat-icon orange">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div>
                    <p>En attente de restauration</p>
                    <h2 id="archivesActives">0</h2>
                </div>
            </article>

            <article class="stat-card">
                <div class="stat-icon green">
                    <i class="fa-solid fa-rotate-left"></i>
                </div>
                <div>
                    <p>Archives restaurées</p>
                    <h2 id="archivesRestaurees">0</h2>
                </div>
            </article>
        </section>

        <section class="content-card">
            <div class="section-heading">
                <div>
                    <h2>Liste des archives</h2>
                    <p>Les données affichées sont des données de démonstration.</p>
                </div>
            </div>

            <div class="filters">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input
                        type="search"
                        id="rechercheArchive"
                        placeholder="Rechercher par ID, table ou raison..."
                    >
                </div>

                <select id="filtreType">
                    <option value="">Tous les types</option>
                    <option value="trajet">Trajets</option>
                    <option value="reservation">Réservations</option>
                    <option value="paiement">Paiements</option>
                    <option value="document">Documents</option>
                    <option value="utilisateur">Utilisateurs</option>
                    <option value="autre">Autres</option>
                </select>

                <select id="filtreStatut">
                    <option value="">Tous les statuts</option>
                    <option value="archive">À restaurer</option>
                    <option value="restauree">Restaurées</option>
                </select>

                <button type="button" class="btn-secondary" id="reinitialiserFiltres">
                    <i class="fa-solid fa-rotate"></i>
                    Réinitialiser
                </button>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Identifiant</th>
                            <th>Type</th>
                            <th>Table d'origine</th>
                            <th>ID d'origine</th>
                            <th>Date d'archivage</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="listeArchives"></tbody>
                </table>
            </div>

            <div id="messageVide" class="empty-state hidden">
                <i class="fa-solid fa-box-open"></i>
                <h3>Aucune archive trouvée</h3>
                <p>Modifiez les filtres ou la recherche.</p>
            </div>

            <div class="table-footer">
                <span id="nombreResultats">0 résultat</span>
            </div>
        </section>

        <footer class="footer">
            <p>© 2026 Burundi Transport — Administration</p>
        </footer>
    </main>

    <!-- Fenêtre des détails -->
    <div id="modalDetails" class="modal hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fa-solid fa-circle-info"></i> Details de l'archive</h2>
                <button type="button" class="close-modal" id="fermerModal"
                        aria-label="Fermer">&times;</button>
            </div>

            <div id="contenuDetails" class="details-content"></div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="fermerDetails">
                    Fermer
                </button>
            </div>
        </div>
    </div>
    <script src="notification-badge.js"></script>
    <script src="archives.js"></script>
</body>
</html>
