<?php
/**
 * changer_mot_de_passe.php
 * Changement de mot de passe
 */

session_start();
require_once('config/connexion.php');
require_once('includes/fonctions.php');

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: auth/login.php');
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];
$message = '';
$erreur = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) {
        $erreur = 'Token CSRF invalide.';
    } else {
        $ancien_mot_de_passe = $_POST['ancien_mot_de_passe'] ?? '';
        $nouveau_mot_de_passe = $_POST['nouveau_mot_de_passe'] ?? '';
        $confirmation_mot_de_passe = $_POST['confirmation_mot_de_passe'] ?? '';
        
        if (empty($ancien_mot_de_passe) || empty($nouveau_mot_de_passe) || empty($confirmation_mot_de_passe)) {
            $erreur = 'Tous les champs sont obligatoires.';
        } elseif (strlen($nouveau_mot_de_passe) < 6) {
            $erreur = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
        } elseif ($nouveau_mot_de_passe !== $confirmation_mot_de_passe) {
            $erreur = 'Les mots de passe ne correspondent pas.';
        } else {
            try {
                // Récupérer le mot de passe actuel
                $stmt = $pdo->prepare("SELECT mot_de_passe FROM UTILISATEUR WHERE id_utilisateur = ?");
                $stmt->execute([$id_utilisateur]);
                $utilisateur = $stmt->fetch();
                
                if (!$utilisateur) {
                    $erreur = 'Utilisateur non trouvé.';
                } elseif (!password_verify($ancien_mot_de_passe, $utilisateur['mot_de_passe'])) {
                    $erreur = 'Le mot de passe actuel est incorrect.';
                } else {
                    // Mettre à jour le mot de passe
                    $hash = password_hash($nouveau_mot_de_passe, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare(
                        "UPDATE UTILISATEUR SET mot_de_passe = ? WHERE id_utilisateur = ?"
                    );
                    $stmt->execute([$hash, $id_utilisateur]);
                    
                    $message = 'Mot de passe modifié avec succès !';
                    
                    // Réinitialiser le formulaire
                    $_POST = [];
                }
            } catch (PDOException $e) {
                error_log('Erreur changement mot de passe: ' . $e->getMessage());
                $erreur = 'Erreur lors du changement de mot de passe.';
            }
        }
    }
}

initialiserCsrf();

// Déterminer le lien de retour selon le rôle
$role = $_SESSION['role'] ?? '';
$retour = 'index.php';
if ($role === 'admin') {
    $retour = 'admin/dashboard.php';
} elseif ($role === 'responsable') {
    $retour = 'responsable/dashboard.php';
} elseif ($role === 'agriculteur') {
    $retour = 'agriculteur/dashboard.php';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changer mot de passe - AgriGest Togo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="header-inner">
                <h1><i class="fas fa-seedling" style="color: var(--color-primary);"></i> AgriGest Togo - <?php echo ucfirst($role); ?></h1>
                <button class="hamburger" id="hamburgerBtn" aria-label="Menu principal" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <nav id="navMenu">
                    <a href="<?php echo $retour; ?>"><i class="fas fa-arrow-left"></i> Retour</a>
                    <a href="profil.php"><i class="fas fa-user-circle"></i> Mon profil</a>
                    <a href="changer_mot_de_passe.php"><i class="fas fa-key"></i> Changer mot de passe</a>
                    <a href="auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                </nav>
            </div>
        </header>

        <main>
            <div class="dashboard-header">
                <h2><i class="fas fa-key"></i> Changer mon mot de passe</h2>
                <p>Modifiez votre mot de passe en toute sécurité</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erreur); ?></div>
            <?php endif; ?>

            <div class="card" style="max-width: 500px; margin: 0 auto;">
                <div style="text-align: center; margin-bottom: var(--spacing-lg);">
                    <div style="font-size: 3rem; color: var(--color-primary);">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3>Sécurité du compte</h3>
                    <p class="text-muted">Choisissez un mot de passe fort et unique</p>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <div class="form-group">
                        <label for="ancien_mot_de_passe"><i class="fas fa-lock"></i> Mot de passe actuel *</label>
                        <input type="password" id="ancien_mot_de_passe" name="ancien_mot_de_passe" required placeholder="Votre mot de passe actuel">
                    </div>

                    <div class="form-group">
                        <label for="nouveau_mot_de_passe"><i class="fas fa-key"></i> Nouveau mot de passe *</label>
                        <input type="password" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe" required placeholder="Minimum 6 caractères" minlength="6">
                        <p class="form-help">Minimum 6 caractères</p>
                    </div>

                    <div class="form-group">
                        <label for="confirmation_mot_de_passe"><i class="fas fa-check-circle"></i> Confirmer le mot de passe *</label>
                        <input type="password" id="confirmation_mot_de_passe" name="confirmation_mot_de_passe" required placeholder="Confirmez votre nouveau mot de passe" minlength="6">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Changer le mot de passe</button>
                        <a href="<?php echo $retour; ?>" class="btn btn-secondary"><i class="fas fa-times"></i> Annuler</a>
                    </div>
                </form>

                <div style="margin-top: var(--spacing-lg); padding-top: var(--spacing-md); border-top: 1px solid var(--color-border); text-align: center;">
                    <a href="profil.php" class="btn btn-secondary"><i class="fas fa-user-circle"></i> Modifier mon profil</a>
                </div>
            </div>

        </main>

        <footer>
            <p>&copy; 2024 AgriGest Togo - Gestion des Exploitations Agricoles</p>
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

            // Afficher/masquer les mots de passe (optionnel)
            const togglePassword = document.createElement('button');
            // ... code pour afficher/masquer les mots de passe
        });
    </script>
</body>
</html>