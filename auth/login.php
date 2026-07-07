<?php
/**
 * auth/login.php
 * Page de connexion AgriGest Togo
 */

session_start();

// Si déjà connecté, rediriger
if (isset($_SESSION['id_utilisateur']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    
    if ($role === 'admin') {
        header('Location: ../admin/dashboard.php');
    } elseif ($role === 'responsable') {
        header('Location: ../responsable/dashboard.php');
    } elseif ($role === 'agriculteur') {
        header('Location: ../agriculteur/dashboard.php');
    } else {
        session_destroy();
    }
    exit;
}

require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) {
        $erreur = 'Session expirée, veuillez recharger la page.';
    } else {
        $email = nettoyer($_POST['email'] ?? '');
        $mot_de_passe = $_POST['mot_de_passe'] ?? '';

        if (empty($email) || empty($mot_de_passe)) {
            $erreur = 'Veuillez remplir tous les champs.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    "SELECT u.id_utilisateur, u.nom, u.prenom, u.email, u.mot_de_passe, u.role,
                            a.id_coop, a.id_agri,
                            r.id_responsable
                     FROM UTILISATEUR u
                     LEFT JOIN AGRICULTEUR a ON u.id_utilisateur = a.id_agri
                     LEFT JOIN RESPONSABLE r ON u.id_utilisateur = r.id_responsable
                     WHERE u.email = ?"
                );
                $stmt->execute([$email]);
                $utilisateur = $stmt->fetch();

                if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
                    $_SESSION['id_utilisateur'] = $utilisateur['id_utilisateur'];
                    $_SESSION['nom'] = $utilisateur['nom'];
                    $_SESSION['prenom'] = $utilisateur['prenom'];
                    $_SESSION['email'] = $utilisateur['email'];
                    $_SESSION['role'] = $utilisateur['role'];

                    if ($utilisateur['role'] === 'agriculteur') {
                        $_SESSION['id_agri'] = $utilisateur['id_agri'];
                        $_SESSION['id_coop'] = $utilisateur['id_coop'];
                        header('Location: ../agriculteur/dashboard.php');
                    } elseif ($utilisateur['role'] === 'responsable') {
                        $_SESSION['id_responsable'] = $utilisateur['id_responsable'];
                        header('Location: ../responsable/dashboard.php');
                    } elseif ($utilisateur['role'] === 'admin') {
                        header('Location: ../admin/dashboard.php');
                    } else {
                        session_destroy();
                        $erreur = 'Rôle inconnu.';
                    }
                    exit;
                } else {
                    $erreur = 'Email ou mot de passe incorrect.';
                }
            } catch (PDOException $e) {
                error_log('Erreur SQL login: ' . $e->getMessage());
                $erreur = 'Erreur lors de la connexion.';
            }
        }
    }
}

initialiserCsrf();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - AgriGest Togo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="header-inner">
                <h1>🌾 AgriGest Togo</h1>
                <button class="hamburger" id="hamburgerBtn" aria-label="Menu principal" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <nav id="navMenu">
                    <a href="login.php">Connexion</a>
                </nav>
            </div>
        </header>

        <main>
            <section class="login-section">
                <h2>Gestion des Exploitations Agricoles</h2>
                
                <?php if ($erreur): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($erreur); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="mot_de_passe">Mot de passe</label>
                        <input type="password" id="mot_de_passe" name="mot_de_passe" required>
                    </div>

                    <button type="submit" class="btn">Se connecter</button>
                </form>

                <p>Contactez l'administrateur pour créer un compte</p>
            </section>
        </main>

        <footer>
            <p>&copy; 2024 AgriGest Togo</p>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hamburgerBtn = document.getElementById('hamburgerBtn');
            const navMenu = document.getElementById('navMenu');

            if (hamburgerBtn && navMenu) {
                hamburgerBtn.addEventListener('click', function() {
                    const isOpen = this.classList.toggle('active');
                    navMenu.classList.toggle('open');
                    this.setAttribute('aria-expanded', isOpen);
                });
            }
        });
    </script>
</body>
</html>