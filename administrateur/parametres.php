
<?php
require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Paramètres - Burundi Transport</title>

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >
        
    <link rel="stylesheet" href="theme.css">
    <link rel="stylesheet" href="parametres.css">
</head>

<body>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">

        <div class="sidebar-logo">
            <div class="logo-icon">
                <i class="fa-solid fa-bus"></i>
            </div>

            <div>
                <h2>Burundi</h2>
                <span>Transport</span>
            </div>
        </div>

        <nav class="sidebar-nav">

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

            <a href="statistiques.php">
                <i class="fa-solid fa-chart-column"></i>
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

            <a href="profil.php">
                <i class="fa-solid fa-user"></i>
                <span>Mon profil</span>
            </a>

            <a href="parametres.php" class="active">
                <i class="fa-solid fa-gear"></i>
                <span>Paramètres</span>
            </a>

        </nav>

        <!-- ADMIN -->
        <div class="sidebar-bottom">

            <div class="admin-mini-profile">

                <div class="admin-avatar">
                    A
                </div>

                <div class="admin-info">
                    <strong>Administrateur</strong>
                    <span>Administrateur</span>
                </div>

            </div>

            <button class="logout-button" id="logoutButton">
                <i class="fa-solid fa-right-from-bracket"></i>
                <a href="../logout.php">Se déconnecter</a>
            </button>

        </div>

    </aside>


    <!-- CONTENU PRINCIPAL -->
    <main class="main-content">

        <!-- HEADER -->
        <header class="top-header">

            <button class="menu-button" id="menuButton">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="header-right">

                <a href="notifications.php" class="notification-link">
                    <i class="fa-solid fa-bell"></i>
                    <span id="notificationBadge">0</span>
                </a>

                <div class="header-profile">

                    <div class="header-avatar">
                        A
                    </div>

                    <div>
                        <strong>Administrateur</strong>
                        <span>Administrateur</span>
                    </div>

                </div>

            </div>

        </header>


        <!-- PAGE -->
        <section class="page-content">

            <div class="page-heading">

                <div>
                    <span class="page-label">ADMINISTRATION</span>

                    <h1>
                        <i class="fa-solid fa-gear"></i>
                        Paramètres
                    </h1>

                    <p>
                        Configurez les paramètres généraux de Burundi Transport.
                    </p>
                </div>

            </div>


            <!-- MESSAGE -->
            <div class="message-box" id="messageBox"></div>


            <!-- PARAMETRES GENERAUX -->
            <section class="settings-card">

                <div class="settings-card-header">

                    <div class="settings-icon">
                        <i class="fa-solid fa-building"></i>
                    </div>

                    <div>
                        <h2>Informations générales</h2>
                        <p>
                            Informations principales de l'entreprise.
                        </p>
                    </div>

                </div>


                <div class="settings-form">

                    <div class="form-group">

                        <label for="nomEntreprise">
                            Nom de l'entreprise
                        </label>

                        <input
                            type="text"
                            id="nomEntreprise"
                            placeholder="Ex : Burundi Transport"
                        >

                    </div>


                    <div class="form-group">

                        <label for="telephoneEntreprise">
                            Téléphone
                        </label>

                        <input
                            type="text"
                            id="telephoneEntreprise"
                            placeholder="+257 79 00 00 00"
                        >

                    </div>


                    <div class="form-group">

                        <label for="emailEntreprise">
                            Email
                        </label>

                        <input
                            type="email"
                            id="emailEntreprise"
                            placeholder="contact@burunditransport.bi"
                        >

                    </div>


                    <div class="form-group">

                        <label for="adresseEntreprise">
                            Adresse
                        </label>

                        <input
                            type="text"
                            id="adresseEntreprise"
                            placeholder="Bujumbura"
                        >

                    </div>

                </div>

            </section>


            <!-- PARAMETRES TRAJETS -->
            <section class="settings-card">

                <div class="settings-card-header">

                    <div class="settings-icon">
                        <i class="fa-solid fa-route"></i>
                    </div>

                    <div>
                        <h2>Paramètres des trajets</h2>
                        <p>
                            Configurez les valeurs utilisées pour les trajets.
                        </p>
                    </div>

                </div>


                <div class="settings-form">

                    <div class="form-group">

                        <label for="placesDefaut">
                            Nombre de places par défaut
                        </label>

                        <input
                            type="number"
                            id="placesDefaut"
                            min="1"
                            max="100"
                        >

                    </div>


                    <div class="form-group">

                        <label for="devise">
                            Devise
                        </label>

                        <select id="devise">

                            <option value="BIF">
                                BIF - Franc burundais
                            </option>

                            <option value="USD">
                                USD - Dollar américain
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="delaiReservation">
                            Délai d'expiration d'une réservation
                        </label>

                        <select id="delaiReservation">

                            <option value="15">15 minutes</option>
                            <option value="30">30 minutes</option>
                            <option value="60">1 heure</option>
                            <option value="120">2 heures</option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="passagersMaximum">
                            Nombre maximum de passagers
                        </label>

                        <input
                            type="number"
                            id="passagersMaximum"
                            min="1"
                            max="20"
                        >

                    </div>

                </div>

            </section>


            <!-- NOTIFICATIONS -->
            <section class="settings-card">

                <div class="settings-card-header">

                    <div class="settings-icon">
                        <i class="fa-solid fa-bell"></i>
                    </div>

                    <div>
                        <h2>Notifications</h2>
                        <p>
                            Configurez les notifications du système.
                        </p>
                    </div>

                </div>


                <div class="settings-options">

                    <div class="setting-option">

                        <div class="option-info">

                            <div class="option-icon">
                                <i class="fa-solid fa-calendar-check"></i>
                            </div>

                            <div>
                                <strong>Notifications de réservation</strong>

                                <p>
                                    Recevoir une notification lorsqu'une réservation
                                    est créée.
                                </p>
                            </div>

                        </div>

                        <label class="switch">

                            <input
                                type="checkbox"
                                id="notificationsReservation"
                            >

                            <span class="slider"></span>

                        </label>

                    </div>


                    <div class="setting-option">

                        <div class="option-info">

                            <div class="option-icon">
                                <i class="fa-solid fa-envelope"></i>
                            </div>

                            <div>
                                <strong>Notifications email</strong>

                                <p>
                                    Autoriser les notifications par email.
                                </p>
                            </div>

                        </div>

                        <label class="switch">

                            <input
                                type="checkbox"
                                id="notificationsEmail"
                            >

                            <span class="slider"></span>

                        </label>

                    </div>


                    <div class="setting-option">

                        <div class="option-info">

                            <div class="option-icon">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>

                            <div>
                                <strong>Alertes système</strong>

                                <p>
                                    Recevoir les alertes importantes du système.
                                </p>
                            </div>

                        </div>

                        <label class="switch">

                            <input
                                type="checkbox"
                                id="alertesSysteme"
                            >

                            <span class="slider"></span>

                        </label>

                    </div>

                </div>

            </section>


            <!-- APPARENCE -->
            <section class="settings-card">

                <div class="settings-card-header">

                    <div class="settings-icon">
                        <i class="fa-solid fa-palette"></i>
                    </div>

                    <div>
                        <h2>Apparence</h2>
                        <p>
                            Choisissez l'apparence de l'administration.
                        </p>
                    </div>

                </div>


                <div class="theme-options">

                    <label class="theme-option">

                        <input
                            type="radio"
                            name="theme"
                            value="clair"
                            id="themeClair"
                        >

                        <div class="theme-preview light-preview">

                            <i class="fa-solid fa-sun"></i>

                            <span>Clair</span>

                        </div>

                    </label>


                    <label class="theme-option">

                        <input
                            type="radio"
                            name="theme"
                            value="sombre"
                            id="themeSombre"
                        >

                        <div class="theme-preview dark-preview">

                            <i class="fa-solid fa-moon"></i>

                            <span>Sombre</span>

                        </div>

                    </label>

                </div>

            </section>


            <!-- MAINTENANCE -->
            <section class="settings-card">

                <div class="settings-card-header">

                    <div class="settings-icon warning-icon">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                    </div>

                    <div>
                        <h2>Maintenance</h2>
                        <p>
                            Contrôlez l'état général du système.
                        </p>
                    </div>

                </div>


                <div class="maintenance-option">

                    <div>

                        <strong>Mode maintenance</strong>

                        <p>
                            Empêche temporairement les clients d'utiliser
                            le système de réservation.
                        </p>

                    </div>

                    <label class="switch">

                        <input
                            type="checkbox"
                            id="modeMaintenance"
                        >

                        <span class="slider"></span>

                    </label>

                </div>

            </section>


            <!-- BOUTONS -->
            <div class="actions">

                <button
                    type="button"
                    class="btn btn-secondary"
                    id="resetButton"
                >
                    <i class="fa-solid fa-rotate-left"></i>
                    Réinitialiser
                </button>

                <button
                    type="button"
                    class="btn btn-primary"
                    id="saveButton"
                >
                    <i class="fa-solid fa-floppy-disk"></i>
                    Enregistrer les paramètres
                </button>

            </div>


        </section>


        <!-- FOOTER -->
        <footer class="footer">

            <p>
                © 2026 Burundi Transport. Tous droits réservés.
            </p>

            <span>
                Administration
            </span>

        </footer>

    </main>
     <script src="notification-badge.js"></script>
     <script src="theme.js"></script>
    <script src="parametres.js"></script>

</body>
</html>