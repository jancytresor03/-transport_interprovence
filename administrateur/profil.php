
<?php
require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");
?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Mon profil - Burundi Transport</title>

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- CSS -->
	<link rel="stylesheet" href="theme.css">
    <link rel="stylesheet" href="profil.css">

</head>


<body>


    <!-- =========================
         SIDEBAR
    ========================== -->

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

            <p class="nav-title">
                MENU PRINCIPAL
            </p>


            <a href="admin-dashboard.php"
               class="nav-link">

                <i class="fa-solid fa-chart-pie"></i>

                <span>Dashboard</span>

            </a>


            <a href="trajets.php"
               class="nav-link">

                <i class="fa-solid fa-route"></i>

                <span>Trajets</span>

            </a>


            <a href="reservations.php"
               class="nav-link">

                <i class="fa-solid fa-calendar-check"></i>

                <span>Réservations</span>

            </a>


            <a href="utilisateurs.php"
               class="nav-link">

                <i class="fa-solid fa-users"></i>

                <span>Utilisateurs</span>

            </a>


            <a href="statistiques.php"
               class="nav-link">

                <i class="fa-solid fa-chart-line"></i>

                <span>Statistiques</span>

            </a>


            <p class="nav-title">
                COMMUNICATION
            </p>


            <a href="notifications.php"
               class="nav-link">

                <i class="fa-solid fa-bell"></i>

                <span>Notifications</span>

                <span class="menu-badge"
                      id="notificationBadge">
                    0
                </span>

            </a>


            <a href="activites.php"
               class="nav-link">

                <i class="fa-solid fa-clock-rotate-left"></i>

                <span>Activités</span>

            </a>
			
			 <a href="chat.php" class="nav-link">
                   <i class="fa-solid fa-comments"></i>
                    <span>Message</span>
                </a>


            <p class="nav-title">
                SYSTÈME
            </p>


            <a href="profil.php"
               class="nav-link active">

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


            <a href="parametres.php"
               class="nav-link">

                <i class="fa-solid fa-gear"></i>

                <span>Paramètres</span>

            </a>
			
			<a href="archives.php" class="nav-link">
                    <i class="fa-solid fa-box-archive"></i>
                    <span>Archives</span>
                </a>

        </nav>


        <!-- PROFIL ADMIN SIDEBAR -->

        <div class="sidebar-bottom">

            <div class="admin-profile">

                <div class="admin-avatar"
                     id="sidebarAvatar">

                    A

                </div>

                <div class="admin-info">

                    <strong id="sidebarName">
                        Administrateur
                    </strong>

                    <span id="sidebarEmail">
                        admin@gmail.com
                    </span>

                </div>

            </div>


            <button class="logout-button"
                    id="logoutButton">

                <i class="fa-solid fa-right-from-bracket"></i>

               <a href="../logout.php">Se déconnecter</a>

            </button>

        </div>

    </aside>



    <!-- =========================
         CONTENU PRINCIPAL
    ========================== -->

    <main class="main-content">


        <!-- HEADER -->

        <header class="top-header">

            <button class="menu-button"
                    id="menuButton">

                <i class="fa-solid fa-bars"></i>

            </button>


            <div class="header-right">

                <a href="notifications.php"
                   class="notification-button">

                    <i class="fa-solid fa-bell"></i>

                    <span class="notification-count"
                          id="headerBadge">
                        0
                    </span>

                </a>


                <div class="header-profile">

                    <div class="header-avatar"
                         id="headerAvatar">

                        A

                    </div>

                    <div>

                        <strong id="headerName">
                            Administrateur
                        </strong>

                        <span>
                            Administrateur
                        </span>

                    </div>

                </div>

            </div>

        </header>



        <!-- =========================
             PAGE
        ========================== -->

        <section class="page-container">


            <!-- TITRE -->

            <div class="page-heading">

                <div>

                    <span class="page-label">
                        COMPTE ADMINISTRATEUR
                    </span>

                    <h1>
                        Mon profil
                    </h1>

                    <p>
                        Gérez vos informations personnelles et
                        les paramètres de sécurité de votre compte.
                    </p>

                </div>

            </div>



            <!-- =========================
                 PROFIL HEADER
            ========================== -->

            <div class="profile-header-card">

                <div class="large-avatar"
                     id="profileAvatar">

                    A

                </div>


                <div class="profile-header-info">

                    <h2 id="profileFullName">
                        Administrateur
                    </h2>

                    <p id="profileEmail">
                        admin@gmail.com
                    </p>

                    <span class="role-badge">

                        <i class="fa-solid fa-shield-halved"></i>

                        Administrateur

                    </span>

                </div>


                <div class="profile-status">

                    <span class="status-dot"></span>

                    Compte actif

                </div>

            </div>



            <!-- =========================
                 INFORMATIONS PERSONNELLES
            ========================== -->

            <div class="content-grid">


                <div class="content-card">


                    <div class="card-header">

                        <div>

                            <h2>
                                Informations personnelles
                            </h2>

                            <p>
                                Modifiez les informations de votre compte.
                            </p>

                        </div>

                    </div>


                    <form id="profileForm">


                        <div class="form-grid">


                            <div class="form-group">

                                <label for="nom">
                                    Nom
                                </label>

                                <input
                                    type="text"
                                    id="nom"
                                    placeholder="Votre nom"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label for="prenom">
                                    Prénom
                                </label>

                                <input
                                    type="text"
                                    id="prenom"
                                    placeholder="Votre prénom"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label for="email">
                                    Adresse email
                                </label>

                                <input
                                    type="email"
                                    id="email"
                                    placeholder="admin@gmail.com"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label for="telephone">
                                    Téléphone
                                </label>

                                <input
                                    type="tel"
                                    id="telephone"
                                    placeholder="+257 79 00 00 00"
                                >

                            </div>


                            <div class="form-group full-width">

                                <label for="adresse">
                                    Adresse
                                </label>

                                <input
                                    type="text"
                                    id="adresse"
                                    placeholder="Votre adresse"
                                >

                            </div>

                        </div>


                        <div class="form-footer">

                            <button
                                type="button"
                                class="secondary-button"
                                id="cancelProfileButton">

                                Annuler

                            </button>


                            <button
                                type="submit"
                                class="primary-button">

                                <i class="fa-solid fa-floppy-disk"></i>

                                Enregistrer

                            </button>

                        </div>

                    </form>

                </div>



                <!-- =========================
                     SECURITE
                ========================== -->

                <div class="content-card">


                    <div class="card-header">

                        <div>

                            <h2>
                                Sécurité
                            </h2>

                            <p>
                                Protégez votre compte administrateur.
                            </p>

                        </div>

                    </div>


                    <!-- MOT DE PASSE -->

                    <div class="security-item">

                        <div class="security-icon blue">

                            <i class="fa-solid fa-lock"></i>

                        </div>


                        <div class="security-content">

                            <strong>
                                Mot de passe
                            </strong>

                            <span>
                                Dernière modification récente
                            </span>

                        </div>


                        <button
                            class="outline-button"
                            id="passwordButton">

                            Modifier

                        </button>

                    </div>


                    <!-- 2FA -->

                    <div class="security-item">

                        <div class="security-icon gold">

                            <i class="fa-solid fa-mobile-screen-button"></i>

                        </div>


                        <div class="security-content">

                            <strong>
                                Double authentification
                            </strong>

                            <span id="twoFactorText">
                                Non activée
                            </span>

                        </div>


                        <button
						type="button"
						class="outline-button"
						id="twoFactorButton">

						Activer

                         </button>

                    </div>


                    <!-- SESSIONS -->

                    <div class="security-item">

                        <div class="security-icon green">

                            <i class="fa-solid fa-desktop"></i>

                        </div>


                        <div class="security-content">

                            <strong>
                                Sessions actives
                            </strong>

                            <span>
                                1 session actuellement active
                            </span>

                        </div>


                        <button
                            class="outline-button"
                            id="sessionsButton">

                            Voir

                        </button>

                    </div>

                </div>

            </div>



            <!-- =========================
                 PREFERENCES
            ========================== -->

            <div class="content-card preferences-card">


                <div class="card-header">

                    <div>

                        <h2>
                            Préférences
                        </h2>

                        <p>
                            Gérez vos préférences de communication.
                        </p>

                    </div>

                </div>


                <div class="preference-item">

                    <div class="preference-info">

                        <div class="preference-icon">

                            <i class="fa-solid fa-bell"></i>

                        </div>

                        <div>

                            <strong>
                                Notifications système
                            </strong>

                            <span>
                                Recevoir les notifications importantes
                                du système.
                            </span>

                        </div>

                    </div>


                    <label class="switch">

                        <input
                            type="checkbox"
                            id="systemNotifications"
                            checked
                        >

                        <span class="slider"></span>

                    </label>

                </div>


                <div class="preference-item">

                    <div class="preference-info">

                        <div class="preference-icon">

                            <i class="fa-solid fa-envelope"></i>

                        </div>

                        <div>

                            <strong>
                                Notifications par email
                            </strong>

                            <span>
                                Recevoir les alertes importantes
                                par email.
                            </span>

                        </div>

                    </div>


                    <label class="switch">

                        <input
                            type="checkbox"
                            id="emailNotifications"
                            checked
                        >

                        <span class="slider"></span>

                    </label>

                </div>


                <div class="preference-item">

                    <div class="preference-info">

                        <div class="preference-icon">

                            <i class="fa-solid fa-calendar-check"></i>

                        </div>

                        <div>

                            <strong>
                                Alertes de réservation
                            </strong>

                            <span>
                                Recevoir une alerte lorsqu'une réservation
                                est créée ou modifiée.
                            </span>

                        </div>

                    </div>


                    <label class="switch">

                        <input
                            type="checkbox"
                            id="reservationNotifications"
                            checked
                        >

                        <span class="slider"></span>

                    </label>

                </div>

            </div>



            <!-- =========================
                 ZONE DANGER
            ========================== -->

            <div class="danger-card">

                <div class="danger-icon">

                    <i class="fa-solid fa-triangle-exclamation"></i>

                </div>


                <div class="danger-content">

                    <h3>
                        Zone de sécurité
                    </h3>

                    <p>
                        La suppression du compte administrateur
                        est une action importante.
                    </p>

                </div>


                <button
                    class="danger-button"
                    id="deleteAccountButton">

                    Supprimer le compte

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



    <!-- =========================
         MODAL MOT DE PASSE
    ========================== -->

    <div class="modal-overlay"
         id="passwordModal">

        <div class="modal">

            <div class="modal-header">

                <div>

                    <h2>
                        Modifier le mot de passe
                    </h2>

                    <p>
                        Choisissez un nouveau mot de passe.
                    </p>

                </div>

                <button
                    class="close-button"
                    id="closePasswordModal">

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </div>


            <form id="passwordForm">

                <div class="form-group">

                    <label for="oldPassword">
                        Ancien mot de passe
                    </label>

                    <input
                        type="password"
                        id="oldPassword"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="newPassword">
                        Nouveau mot de passe
                    </label>

                    <input
                        type="password"
                        id="newPassword"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="confirmPassword">
                        Confirmer le mot de passe
                    </label>

                    <input
                        type="password"
                        id="confirmPassword"
                        required
                    >

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="secondary-button"
                        id="cancelPasswordButton">

                        Annuler

                    </button>


                    <button
                        type="submit"
                        class="primary-button">

                        Modifier le mot de passe

                    </button>

                </div>

            </form>

        </div>

    </div>



    <!-- JAVASCRIPT -->
	<script src="notification-badge.js"></script>
    <script src="theme.js"></script>
    <script src="profil.js"></script>
	<script src="header-admin.js"></script>

</body>

</html>