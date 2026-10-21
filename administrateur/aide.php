
<?php
require_once __DIR__ . "/../verification.php";
verifierAcces("admin", "../login.html");
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Aide - Burundi Transport</title>

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >
    <link rel="stylesheet" href="theme.css">
    <link rel="stylesheet" href="aide.css">
</head>

<body>

    <!-- =========================
         SIDEBAR
    ========================== -->

    <aside class="sidebar" id="sidebar">

        <!-- LOGO -->

        <div class="sidebar-logo">

            <div class="logo-icon">
                <i class="fa-solid fa-bus"></i>
            </div>

            <div>
                <h2>Burundi</h2>
                <span>Transport</span>
            </div>

        </div>


        <!-- NAVIGATION -->

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

            <a href="parametres.php">
                <i class="fa-solid fa-gear"></i>
                <span>Paramètres</span>
            </a>

            <a href="aide.php" class="active">
                <i class="fa-solid fa-circle-question"></i>
                <span>Aide</span>
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


            <button
                class="logout-button"
                id="logoutButton"
            >

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

            <button
                class="menu-button"
                id="menuButton"
            >

                <i class="fa-solid fa-bars"></i>

            </button>


            <div class="header-right">

                <a
                    href="notifications.html"
                    class="notification-link"
                >

                    <i class="fa-solid fa-bell"></i>

                    <span id="notificationBadge">
                        0
                    </span>

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



        <!-- =========================
             CONTENU
        ========================== -->

        <section class="page-content">


            <!-- TITRE -->

            <div class="page-heading">

                <span class="page-label">
                    CENTRE D'AIDE
                </span>

                <h1>

                    <i class="fa-solid fa-circle-question"></i>

                    Aide et guide

                </h1>

                <p>
                    Retrouvez les informations nécessaires
                    pour utiliser l'espace administrateur.
                </p>

            </div>



            <!-- =========================
                 RECHERCHE
            ========================== -->

            <div class="search-box">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    id="searchInput"
                    placeholder="Rechercher une aide..."
                >

            </div>



            <!-- =========================
                 CATEGORIES
            ========================== -->

            <div class="help-categories">


                <button
                    class="category-button active"
                    data-category="tous"
                >

                    <i class="fa-solid fa-layer-group"></i>

                    Tous

                </button>


                <button
                    class="category-button"
                    data-category="trajets"
                >

                    <i class="fa-solid fa-route"></i>

                    Trajets

                </button>


                <button
                    class="category-button"
                    data-category="reservations"
                >

                    <i class="fa-solid fa-ticket"></i>

                    Réservations

                </button>


                <button
                    class="category-button"
                    data-category="utilisateurs"
                >

                    <i class="fa-solid fa-users"></i>

                    Utilisateurs

                </button>


                <button
                    class="category-button"
                    data-category="securite"
                >

                    <i class="fa-solid fa-shield-halved"></i>

                    Sécurité

                </button>


                <button
                    class="category-button"
                    data-category="systeme"
                >

                    <i class="fa-solid fa-gear"></i>

                    Système

                </button>

            </div>



            <!-- =========================
                 QUESTIONS
            ========================== -->

            <div class="faq-container" id="faqContainer">


                <!-- TRAJETS -->

                <article
                    class="faq-item"
                    data-category="trajets"
                >

                    <button class="faq-question">

                        <div>

                            <i class="fa-solid fa-route"></i>

                            <span>
                                Comment ajouter un nouveau trajet ?
                            </span>

                        </div>

                        <i class="fa-solid fa-chevron-down arrow"></i>

                    </button>


                    <div class="faq-answer">

                        <p>
                            Pour ajouter un trajet, ouvrez le menu
                            <strong>Trajets</strong>, puis cliquez sur
                            <strong>Ajouter un trajet</strong>.
                        </p>

                        <p>
                            Remplissez ensuite le départ, la destination,
                            la date, l'heure, le prix et le nombre de places.
                        </p>

                        <p>
                            Cliquez finalement sur
                            <strong>Enregistrer</strong>.
                        </p>

                    </div>

                </article>



                <!-- TRAJETS -->

                <article
                    class="faq-item"
                    data-category="trajets"
                >

                    <button class="faq-question">

                        <div>

                            <i class="fa-solid fa-pen-to-square"></i>

                            <span>
                                Comment modifier un trajet ?
                            </span>

                        </div>

                        <i class="fa-solid fa-chevron-down arrow"></i>

                    </button>


                    <div class="faq-answer">

                        <p>
                            Dans la page <strong>Trajets</strong>,
                            recherchez le trajet concerné.
                        </p>

                        <p>
                            Cliquez ensuite sur le bouton
                            <strong>Modifier</strong>.
                        </p>

                        <p>
                            Modifiez les informations nécessaires puis
                            enregistrez les changements.
                        </p>

                    </div>

                </article>



                <!-- TRAJETS -->

                <article
                    class="faq-item"
                    data-category="trajets"
                >

                    <button class="faq-question">

                        <div>

                            <i class="fa-solid fa-trash"></i>

                            <span>
                                Comment supprimer un trajet ?
                            </span>

                        </div>

                        <i class="fa-solid fa-chevron-down arrow"></i>

                    </button>


                    <div class="faq-answer">

                        <p>
                            Dans la liste des trajets, cliquez sur
                            <strong>Supprimer</strong>.
                        </p>

                        <p>
                            Une confirmation vous sera demandée avant
                            la suppression.
                        </p>

                        <p>
                            Dans une version avec une base de données,
                            il est recommandé d'archiver les données
                            importantes plutôt que de les supprimer
                            définitivement.
                        </p>

                    </div>

                </article>



                <!-- RESERVATIONS -->

                <article
                    class="faq-item"
                    data-category="reservations"
                >

                    <button class="faq-question">

                        <div>

                            <i class="fa-solid fa-ticket"></i>

                            <span>
                                Comment gérer une réservation ?
                            </span>

                        </div>

                        <i class="fa-solid fa-chevron-down arrow"></i>

                    </button>


                    <div class="faq-answer">

                        <p>
                            Ouvrez la page
                            <strong>Réservations</strong>.
                        </p>

                        <p>
                            Vous pouvez consulter les informations
                            du client, le trajet, les places et le
                            montant de la réservation.
                        </p>

                        <p>
                            Selon les droits de l'administrateur,
                            une réservation peut être confirmée,
                            annulée ou modifiée.
                        </p>

                    </div>

                </article>



                <!-- RESERVATIONS -->

                <article
                    class="faq-item"
                    data-category="reservations"
                >

                    <button class="faq-question">

                        <div>

                            <i class="fa-solid fa-clock"></i>

                            <span>
                                Quand une réservation expire-t-elle ?
                            </span>

                        </div>

                        <i class="fa-solid fa-chevron-down arrow"></i>

                    </button>


                    <div class="faq-answer">

                        <p>
                            Le délai d'expiration est configurable
                            dans <strong>Paramètres</strong>.
                        </p>

                        <p>
                            Les valeurs disponibles sont actuellement :
                        </p>

                        <ul>

                            <li>15 minutes</li>
                            <li>30 minutes</li>
                            <li>1 heure</li>
                            <li>2 heures</li>

                        </ul>

                        <p>
                            Une réservation en attente peut être
                            considérée comme expirée lorsque le délai
                            configuré depuis sa création est dépassé.
                        </p>

                    </div>

                </article>



                <!-- UTILISATEURS -->

                <article
                    class="faq-item"
                    data-category="utilisateurs"
                >

                    <button class="faq-question">

                        <div>

                            <i class="fa-solid fa-users"></i>

                            <span>
                                Comment gérer les utilisateurs ?
                            </span>

                        </div>

                        <i class="fa-solid fa-chevron-down arrow"></i>

                    </button>


                    <div class="faq-answer">

                        <p>
                            La page <strong>Utilisateurs</strong>
                            permet à l'administrateur de consulter
                            les comptes enregistrés.
                        </p>

                        <p>
                            Les informations peuvent notamment
                            comprendre le nom, l'email, le rôle
                            et le statut du compte.
                        </p>

                    </div>

                </article>



                <!-- SECURITE -->

                <article
                    class="faq-item"
                    data-category="securite"
                >

                    <button class="faq-question">

                        <div>

                            <i class="fa-solid fa-shield-halved"></i>

                            <span>
                                Comment protéger l'espace administrateur ?
                            </span>

                        </div>

                        <i class="fa-solid fa-chevron-down arrow"></i>

                    </button>


                    <div class="faq-answer">

                        <p>
                            L'espace administrateur doit être protégé
                            par une authentification et un contrôle
                            des rôles.
                        </p>

                        <p>
                            Dans la version finale avec PHP et MySQL,
                            les pages administrateur devront vérifier
                            la session avant d'autoriser l'accès.
                        </p>

                        <p>
                            Les mots de passe devront également être
                            stockés sous forme de hash et non en texte
                            simple.
                        </p>

                    </div>

                </article>



                <!-- SYSTEME -->

                <article
                    class="faq-item"
                    data-category="systeme"
                >

                    <button class="faq-question">

                        <div>

                            <i class="fa-solid fa-gear"></i>

                            <span>
                                À quoi servent les paramètres ?
                            </span>

                        </div>

                        <i class="fa-solid fa-chevron-down arrow"></i>

                    </button>


                    <div class="faq-answer">

                        <p>
                            La page <strong>Paramètres</strong>
                            permet de configurer certaines règles
                            générales du système.
                        </p>

                        <p>
                            L'administrateur peut notamment définir
                            le nombre de places par défaut, la devise,
                            les notifications et le délai d'expiration
                            des réservations.
                        </p>

                    </div>

                </article>



                <!-- STATISTIQUES -->

                <article
                    class="faq-item"
                    data-category="systeme"
                >

                    <button class="faq-question">

                        <div>

                            <i class="fa-solid fa-chart-column"></i>

                            <span>
                                Comment consulter les statistiques ?
                            </span>

                        </div>

                        <i class="fa-solid fa-chevron-down arrow"></i>

                    </button>


                    <div class="faq-answer">

                        <p>
                            Ouvrez la page
                            <strong>Statistiques</strong>.
                        </p>

                        <p>
                            Cette page présente notamment les
                            réservations, les revenus, les destinations
                            et les utilisateurs sous forme de graphiques
                            et de tableaux.
                        </p>

                    </div>

                </article>



                <!-- NOTIFICATIONS -->

                <article
                    class="faq-item"
                    data-category="systeme"
                >

                    <button class="faq-question">

                        <div>

                            <i class="fa-solid fa-bell"></i>

                            <span>
                                Où consulter les notifications ?
                            </span>

                        </div>

                        <i class="fa-solid fa-chevron-down arrow"></i>

                    </button>


                    <div class="faq-answer">

                        <p>
                            Cliquez sur <strong>Notifications</strong>
                            dans le menu administrateur.
                        </p>

                        <p>
                            Vous pouvez consulter les notifications
                            du système et identifier celles qui n'ont
                            pas encore été lues.
                        </p>

                    </div>

                </article>



                <!-- ACTIVITES -->

                <article
                    class="faq-item"
                    data-category="securite"
                >

                    <button class="faq-question">

                        <div>

                            <i class="fa-solid fa-clock-rotate-left"></i>

                            <span>
                                À quoi sert la page Activités ?
                            </span>

                        </div>

                        <i class="fa-solid fa-chevron-down arrow"></i>

                    </button>


                    <div class="faq-answer">

                        <p>
                            La page <strong>Activités</strong> permet
                            de consulter le journal des actions réalisées
                            dans le système.
                        </p>

                        <p>
                            Elle peut notamment servir à suivre les
                            connexions, modifications, suppressions
                            et autres opérations administratives.
                        </p>

                    </div>

                </article>



            </div>



            <!-- MESSAGE AUCUN RESULTAT -->

            <div
                class="no-result"
                id="noResult"
            >

                <i class="fa-solid fa-magnifying-glass"></i>

                <h3>
                    Aucun résultat
                </h3>

                <p>
                    Aucun élément d'aide ne correspond à votre recherche.
                </p>

            </div>



            <!-- CONTACT -->

            <section class="contact-card">

                <div class="contact-icon">

                    <i class="fa-solid fa-headset"></i>

                </div>

                <div>

                    <h2>
                        Besoin d'aide supplémentaire ?
                    </h2>

                    <p>
                        Si vous rencontrez un problème avec
                        l'administration du système, contactez
                        le responsable technique.
                    </p>

                </div>

                <a
                    href="mailto:contact@burunditransport.bi"
                    class="contact-button"
                >

                    <i class="fa-solid fa-envelope"></i>

                    Contacter

                </a>

            </section>


        </section>



        <!-- FOOTER -->

        <footer class="footer">

            <p>
                © 2026 Burundi Transport. Tous droits réservés.
            </p>

            <span>
                Centre d'aide
            </span>

        </footer>

    </main>


    <script src="theme.js"></script>
    <script src="aide.js"></script>

</body>

</html>