
<?php
require_once __DIR__ . "/../verification.php";
verifierAcces("client", "../login.html");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mon profil - Burundi Transport</title>

    <link rel="stylesheet" href="profil.css">
</head>

<body>

    <!-- ================= HEADER ================= -->

    <header class="header">

        <div class="header-content">

            <a href="client-home.php" class="logo">
                <span class="logo-icon">BT</span>
                <span>Burundi Transport</span>
            </a>


            <nav class="nav">

                <a href="client-home.php">
                    Accueil
                </a>

                <a href="recherche.php">
                    Rechercher un trajet
                </a>

                <a href="mes-reservations.php">
                    Mes réservations
                </a>

            </nav>


            <div class="user-area">

                <button
                    class="user-button"
                    id="userButton"
                >

                    <span class="user-avatar">
                        C
                    </span>

                    <span>
                        Client
                    </span>

                    <span class="arrow">
                        ⌄
                    </span>

                </button>


                <div
                    class="user-menu"
                    id="userMenu"
                >

                    <a href="profil.php">
                        Mon profil
                    </a>

                    <a href="mes-reservations.php">
                        Mes réservations
                    </a>

                    <button id="logoutButton">
                        <a href="../logout.php">Se déconnecter</a>
                    </button>

                </div>

            </div>

        </div>

    </header>


    <!-- ================= PAGE HEADER ================= -->

    <section class="page-header">

        <div class="page-header-content">

            <span class="page-label">
                ESPACE CLIENT
            </span>

            <h1>
                Mon profil
            </h1>

            <p>
                Gérez vos informations personnelles et
                les paramètres de votre compte.
            </p>

        </div>

    </section>


    <!-- ================= MAIN ================= -->

    <main class="main-container">


        <!-- ================= PROFIL HEADER ================= -->

        <section class="profile-header">

            <div class="profile-avatar">
                C
            </div>

            <div class="profile-name">

                <h2 id="profileName">
                    Client
                </h2>

                <p id="profileEmail">
                    client@example.com
                </p>

                <span class="account-status">
                    ● Compte actif
                </span>

            </div>

        </section>


        <!-- ================= GRID ================= -->

        <div class="profile-grid">


            <!-- ================= INFORMATIONS ================= -->

            <section class="profile-card">

                <div class="card-title">

                    <div class="title-icon">
                        👤
                    </div>

                    <div>

                        <h2>
                            Informations personnelles
                        </h2>

                        <p>
                            Modifiez vos informations personnelles.
                        </p>

                    </div>

                </div>


                <form id="profileForm">

                    <div class="form-row">

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

                            <small
                                class="error-message"
                                id="nomError"
                            ></small>

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

                            <small
                                class="error-message"
                                id="prenomError"
                            ></small>

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="telephone">
                            Téléphone
                        </label>

                        <input
                            type="tel"
                            id="telephone"
                            placeholder="+257 00 00 00 00"
                            required
                        >

                        <small
                            class="error-message"
                            id="telephoneError"
                        ></small>

                    </div>


                    <div class="form-group">

                        <label for="email">
                            Adresse email
                        </label>

                        <input
                            type="email"
                            id="email"
                            placeholder="exemple@email.com"
                            required
                        >

                        <small
                            class="error-message"
                            id="emailError"
                        ></small>

                    </div>


                    <div class="form-group">

                        <label for="adresse">
                            Adresse
                        </label>

                        <input
                            type="text"
                            id="adresse"
                            placeholder="Votre adresse"
                        >

                    </div>


                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        Enregistrer les modifications
                    </button>

                </form>

            </section>


            <!-- ================= COMPTE ================= -->

            <section class="profile-card">

                <div class="card-title">

                    <div class="title-icon">
                        🔐
                    </div>

                    <div>

                        <h2>
                            Sécurité du compte
                        </h2>

                        <p>
                            Gérez votre mot de passe et votre sécurité.
                        </p>

                    </div>

                </div>


                <div class="security-item">

                    <div>

                        <strong>
                            Mot de passe
                        </strong>

                        <p>
                            Dernière modification :
                            il y a 30 jours
                        </p>

                    </div>

                    <button
                        class="btn-outline"
                        id="passwordButton"
                    >
                        Modifier
                    </button>

                </div>


                <div class="security-item">

                    <div>

                        <strong>
                            Authentification à deux facteurs
                        </strong>

                        <p>
                            Ajoutez une protection supplémentaire
                            à votre compte.
                        </p>

                    </div>

                    <button
                        class="btn-outline"
                        id="twoFactorButton"
                    >
                        Activer
                    </button>

                </div>


                <div class="security-item">

                    <div>

                        <strong>
                            Sessions actives
                        </strong>

                        <p>
                            Gérez les appareils connectés
                            à votre compte.
                        </p>

                    </div>

                    <button
                        class="btn-outline"
                        id="sessionsButton"
                    >
                        Voir
                    </button>

                </div>

            </section>


            <!-- ================= PREFERENCES ================= -->

            <section class="profile-card">

                <div class="card-title">

                    <div class="title-icon">
                        ⚙
                    </div>

                    <div>

                        <h2>
                            Préférences
                        </h2>

                        <p>
                            Personnalisez votre expérience.
                        </p>

                    </div>

                </div>


                <div class="preference-item">

                    <div>

                        <strong>
                            Notifications
                        </strong>

                        <p>
                            Recevoir les notifications concernant
                            mes réservations.
                        </p>

                    </div>

                    <label class="switch">

                        <input
                            type="checkbox"
                            id="notificationSwitch"
                            checked
                        >

                        <span class="slider"></span>

                    </label>

                </div>


                <div class="preference-item">

                    <div>

                        <strong>
                            Notifications par email
                        </strong>

                        <p>
                            Recevoir les confirmations par email.
                        </p>

                    </div>

                    <label class="switch">

                        <input
                            type="checkbox"
                            id="emailSwitch"
                            checked
                        >

                        <span class="slider"></span>

                    </label>

                </div>

            </section>


            <!-- ================= COMPTE DANGER ================= -->

            <section class="profile-card danger-card">

                <div class="card-title">

                    <div class="title-icon danger-icon">
                        ⚠
                    </div>

                    <div>

                        <h2>
                            Gestion du compte
                        </h2>

                        <p>
                            Actions importantes concernant votre compte.
                        </p>

                    </div>

                </div>


                <div class="danger-content">

                    <div>

                        <strong>
                            Supprimer mon compte
                        </strong>

                        <p>
                            Cette action supprimera définitivement
                            votre compte.
                        </p>

                    </div>

                    <button
                        class="btn-danger"
                        id="deleteAccountButton"
                    >
                        Supprimer le compte
                    </button>

                </div>

            </section>

        </div>

    </main>


    <!-- ================= FOOTER ================= -->

    <footer class="footer">

        <div class="footer-content">

            <div>

                <strong>
                    Burundi Transport
                </strong>

                <p>
                    Réservez vos trajets interprovinciaux
                    simplement et rapidement.
                </p>

            </div>


            <div class="footer-links">

                <a href="client-home.html">
                    Accueil
                </a>

                <a href="recherche.html">
                    Rechercher
                </a>

                <a href="mes-reservations.html">
                    Mes réservations
                </a>

            </div>

        </div>


        <div class="footer-bottom">

            © 2026 Burundi Transport.
            Tous droits réservés.

        </div>

    </footer>


    <script src="profil.js"></script>

</body>
</html>