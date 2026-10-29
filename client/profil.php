
<?php

require_once __DIR__ . "/../verification.php";
verifierAcces("client", "../login.html");

require_once __DIR__ . "/../database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.html");
    exit;
}

$userId = $_SESSION["user_id"];

/* =========================
   FONCTION DE PROTECTION
========================= */

function h($valeur)
{
    return htmlspecialchars(
        (string) ($valeur ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

/* =========================
   RECUPERATION DU CLIENT
========================= */

$sql = "
    SELECT
        user_id,
        nom,
        prenom,
        email,
        telephone,
        adresse,
        statut,
        deux_facteurs,
        date_creation,
        date_modification
    FROM users
    WHERE user_id = :user_id
    LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    "user_id" => $userId
]);

$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client || $client["statut"] !== "actif") {
    header("Location: ../logout.php");
    exit;
}

$nomComplet = trim($client["prenom"] . " " . $client["nom"]);

$initiale = strtoupper(
    mb_substr($client["prenom"] ?: $client["nom"], 0, 1, "UTF-8")
);

$statutCompte = match ($client["statut"]) {
    "actif" => "Compte actif",
    "bloque" => "Compte bloqué",
    "inactif" => "Compte inactif",
    default => "Statut inconnu"
};

$dateModification = !empty($client["date_modification"])
    ? date("d/m/Y à H:i", strtotime($client["date_modification"]))
    : "Non disponible";

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
                <a href="client-home.php">Accueil</a>
                <a href="recherche.php">Rechercher un trajet</a>
                <a href="mes-reservations.php">Mes réservations</a>
				<a href="chat.php">message</a>
            </nav>

            <div class="user-area">

                <button
                    type="button"
                    class="user-button"
                    id="userButton"
                    aria-expanded="false"
                    aria-controls="userMenu"
                >
                    <span class="user-avatar">
                        <?= h($initiale) ?>
                    </span>

                    <span><?= h($nomComplet) ?></span>
                    <span class="arrow">⌄</span>
                </button>

                <div class="user-menu" id="userMenu">
                    <a href="profil.php">Mon profil</a>
                    <a href="mes-reservations.php">Mes réservations</a>
                    <a href="../logout.php">Se déconnecter</a>
                </div>

            </div>

        </div>

    </header>

    <!-- ================= PAGE HEADER ================= -->

    <section class="page-header">

        <div class="page-header-content">

            <span class="page-label">ESPACE CLIENT</span>

            <h1>Mon profil</h1>

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
                <?= h($initiale) ?>
            </div>

            <div class="profile-name">

                <h2 id="profileName">
                    <?= h($nomComplet) ?>
                </h2>

                <p id="profileEmail">
                    <?= h($client["email"]) ?>
                </p>

                <span class="account-status">
                    ● <?= h($statutCompte) ?>
                </span>

                <p class="profile-updated">
                    Dernière modification :
                    <?= h($dateModification) ?>
                </p>

            </div>

        </section>

        <!-- Message de retour du formulaire -->
        <div
            id="profileMessage"
            class="profile-message"
            role="status"
            aria-live="polite"
            hidden
        ></div>

        <!-- ================= GRID ================= -->

        <div class="profile-grid">

            <!-- ================= INFORMATIONS PERSONNELLES ================= -->

            <section class="profile-card">

                <div class="card-title">

                    <div class="title-icon">👤</div>

                    <div>
                        <h2>Informations personnelles</h2>
                        <p>Modifiez vos informations personnelles.</p>
                    </div>

                </div>

                <form id="profileForm" method="post">

                    <div class="form-row">

                        <div class="form-group">
                            <label for="nom">Nom</label>

                            <input
                                type="text"
                                id="nom"
                                name="nom"
                                maxlength="100"
                                value="<?= h($client["nom"]) ?>"
                                required
                            >

                            <small class="error-message" id="nomError"></small>
                        </div>

                        <div class="form-group">
                            <label for="prenom">Prénom</label>

                            <input
                                type="text"
                                id="prenom"
                                name="prenom"
                                maxlength="100"
                                value="<?= h($client["prenom"]) ?>"
                                required
                            >

                            <small class="error-message" id="prenomError"></small>
                        </div>

                    </div>

                    <div class="form-group">

                        <label for="telephone">Téléphone</label>

                        <input
                            type="tel"
                            id="telephone"
                            name="telephone"
                            maxlength="30"
                            value="<?= h($client["telephone"]) ?>"
                            placeholder="+257 00 00 00 00"
                            required
                        >

                        <small class="error-message" id="telephoneError"></small>

                    </div>

                    <div class="form-group">

                        <label for="email">Adresse email</label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            maxlength="150"
                            value="<?= h($client["email"]) ?>"
                            required
                        >

                        <small class="error-message" id="emailError"></small>

                    </div>

                    <div class="form-group">

                        <label for="adresse">Adresse</label>

                        <input
                            type="text"
                            id="adresse"
                            name="adresse"
                            maxlength="255"
                            value="<?= h($client["adresse"]) ?>"
                            placeholder="Votre adresse"
                        >

                    </div>

                    <button type="submit" class="btn-primary" id="saveProfileButton">
                        Enregistrer les modifications
                    </button>

                </form>

            </section>

            <!-- ================= SECURITE ================= -->

            <section class="profile-card">

                <div class="card-title">

                    <div class="title-icon">🔐</div>

                    <div>
                        <h2>Sécurité du compte</h2>
                        <p>Gérez votre mot de passe et votre sécurité.</p>
                    </div>

                </div>

            
<div class="security-item">

    <div>
        <strong>Mot de passe</strong>
        <p>Modifiez votre mot de passe pour sécuriser votre compte.</p>
    </div>

    <button
        type="button"
        class="btn-outline"
        id="passwordButton"
        aria-expanded="false"
        aria-controls="passwordFormContainer"
    >
        Modifier
    </button>

</div>

<!-- Formulaire de modification du mot de passe -->
<div id="passwordFormContainer" hidden>

    <form id="passwordForm">

        <div class="form-group">
            <label for="ancienMotDePasse">
                Mot de passe actuel
            </label>

            <input
                type="password"
                id="ancienMotDePasse"
                name="ancienMotDePasse"
                autocomplete="current-password"
                required
            >
        </div>

        <div class="form-group">
            <label for="nouveauMotDePasse">
                Nouveau mot de passe
            </label>

            <input
                type="password"
                id="nouveauMotDePasse"
                name="nouveauMotDePasse"
                minlength="8"
                maxlength="72"
                autocomplete="new-password"
                required
            >
        </div>

        <div class="form-group">
            <label for="confirmationMotDePasse">
                Confirmer le nouveau mot de passe
            </label>

            <input
                type="password"
                id="confirmationMotDePasse"
                name="confirmationMotDePasse"
                minlength="8"
                maxlength="72"
                autocomplete="new-password"
                required
            >
        </div>

        <div
            id="passwordMessage"
            class="profile-message"
            role="status"
            aria-live="polite"
            hidden
        ></div>

        <button
            type="submit"
            class="btn-primary"
            id="savePasswordButton"
        >
            Enregistrer le nouveau mot de passe
        </button>

        <button
            type="button"
            class="btn-outline"
            id="cancelPasswordButton"
        >
            Annuler
        </button>

    </form>

</div>


                <div class="security-item">

                    <div>
                        <strong>Authentification à deux facteurs</strong>

                        <p>
                            Statut :
                            <?= $client["deux_facteurs"] ? "Activée" : "Désactivée" ?>
                        </p>
                    </div>

                    <button
                        type="button"
                        class="btn-outline"
                        id="twoFactorButton"
                    >
                        <?= $client["deux_facteurs"] ? "Désactiver" : "Activer" ?>
                    </button>

                </div>

                <div class="security-item">

                    <div>
                        <strong>Sessions actives</strong>
                        <p>Consultez les appareils connectés à votre compte.</p>
                    </div>

                    <button
                        type="button"
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

                    <div class="title-icon">⚙️</div>

                    <div>
                        <h2>Préférences</h2>
                        <p>Personnalisez votre expérience.</p>
                    </div>

                </div>

                <div class="preference-item">

                    <div>
                        <strong>Notifications</strong>
                        <p>Recevoir les notifications concernant mes réservations.</p>
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
                        <strong>Notifications par email</strong>
                        <p>Recevoir les confirmations par email.</p>
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

                <p class="preference-note">
                    Les préférences sont affichées ici, mais leur sauvegarde
                    en base de données n'est pas encore configurée.
                </p>

            </section>

            <!-- ================= GESTION DU COMPTE ================= -->

            <section class="profile-card danger-card">

                <div class="card-title">

                    <div class="title-icon danger-icon">⚠️</div>

                    <div>
                        <h2>Gestion du compte</h2>
                        <p>Actions importantes concernant votre compte.</p>
                    </div>

                </div>

                <div class="danger-content">

                    <div>
                        <strong>Supprimer mon compte</strong>

                        <p>
                            Cette action peut avoir des conséquences
                            sur votre compte et vos réservations.
                        </p>
                    </div>

                    <button
                        type="button"
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
                <strong>Burundi Transport</strong>

                <p>
                    Réservez vos trajets interprovinciaux
                    simplement et rapidement.
                </p>
            </div>

            <div class="footer-links">
                <a href="client-home.php">Accueil</a>
                <a href="recherche.php">Rechercher</a>
                <a href="mes-reservations.php">Mes réservations</a>
            </div>

        </div>

        <div class="footer-bottom">
            © <?= date("Y") ?> Burundi Transport. Tous droits réservés.
        </div>

    </footer>

    <script src="profil.js"></script>
	<script src="deconnexion-auto.js"></script>

</body>
</html>
