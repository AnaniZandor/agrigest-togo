<?php
/**
 * profil.php
 * Modification du profil utilisateur
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
$utilisateur = null;

try {
    // Récupérer les informations de l'utilisateur
    $stmt = $pdo->prepare("SELECT id_utilisateur, nom, prenom, email, role FROM UTILISATEUR WHERE id_utilisateur = ?");
    $stmt->execute([$id_utilisateur]);
    $utilisateur = $stmt->fetch();

    if (!$utilisateur) {
        header('Location: auth/login.php');
        exit;
    }

} catch (PDOException $e) {
    error_log('Erreur SQL profil: ' . $e->getMessage());
    $erreur = 'Erreur lors du chargement du profil.';
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) {
        $erreur = 'Token CSRF invalide.';
    } else {
        $nom = nettoyer($_POST['nom'] ?? '');
        $prenom = nettoyer($_POST['prenom'] ?? '');
        $email = nettoyer($_POST['email'] ?? '');
        
        if (empty($nom) || empty($prenom) || empty($email)) {
            $erreur = 'Tous les champs sont obligatoires.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreur = 'Adresse email invalide.';
        } else {
            try {
                // Vérifier que l'email n'est pas déjà utilisé par un autre utilisateur
                $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM UTILISATEUR WHERE email = ? AND id_utilisateur != ?");
                $stmt->execute([$email, $id_utilisateur]);
                $result = $stmt->fetch();
                
                if ($result['total'] > 0) {
                    $erreur = 'Cet email est déjà utilisé par un autre utilisateur.';
                } else {
                    // Mettre à jour le profil
                    $stmt = $pdo->prepare(
                        "UPDATE UTILISATEUR SET nom = ?, prenom = ?, email = ? WHERE id_utilisateur = ?"
                    );
                    $stmt->execute([$nom, $prenom, $email, $id_utilisateur]);
                    
                    // Mettre à jour la session
                    $_SESSION['nom'] = $nom;
                    $_SESSION['prenom'] = $prenom;
                    $_SESSION['email'] = $email;
                    
                    $message = 'Profil mis à jour avec succès !';
                    
                    // Recharger les données
                    $stmt = $pdo->prepare("SELECT id_utilisateur, nom, prenom, email, role FROM UTILISATEUR WHERE id_utilisateur = ?");
                    $stmt->execute([$id_utilisateur]);
                    $utilisateur = $stmt->fetch();
                }
            } catch (PDOException $e) {
                error_log('Erreur mise à jour profil: ' . $e->getMessage());
                $erreur = 'Erreur lors de la mise à jour du profil.';
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
    <title>Mon profil - AgriGest Togo</title>
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
                <h2><i class="fas fa-user-circle"></i> Mon profil</h2>
                <p>Modifiez vos informations personnelles</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erreur); ?></div>
            <?php endif; ?>

            <?php if ($utilisateur): ?>

            <div class="card" style="max-width: 600px; margin: 0 auto;">
                <div style="text-align: center; margin-bottom: var(--spacing-lg);">
                    <div style="font-size: 4rem; color: var(--color-primary);">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']); ?></h3>
                    <p class="text-muted"><?php echo htmlspecialchars($utilisateur['role']); ?></p>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <div class="form-group">
                        <label for="nom"><i class="fas fa-user"></i> Nom *</label>
                        <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($utilisateur['nom']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="prenom"><i class="fas fa-user"></i> Prénom *</label>
                        <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($utilisateur['prenom']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($utilisateur['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Rôle</label>
                        <input type="text" value="<?php echo htmlspecialchars(ucfirst($utilisateur['role'])); ?>" disabled style="background: var(--color-bg);">
                        <p class="form-help">Le rôle ne peut pas être modifié.</p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                        <a href="<?php echo $retour; ?>" class="btn btn-secondary"><i class="fas fa-times"></i> Annuler</a>
                    </div>
                </form>

                <div style="margin-top: var(--spacing-lg); padding-top: var(--spacing-md); border-top: 1px solid var(--color-border); text-align: center;">
                    <a href="changer_mot_de_passe.php" class="btn btn-secondary"><i class="fas fa-key"></i> Changer mon mot de passe</a>
                </div>
            </div>

            <?php endif; ?>

        </main>

        <footer>
            <p>&copy; 2024 AgriGest Togo - Gestion des Exploitations Agricoles</p>
        </footer>
    </div>

    <script src="assets/js/main.js"></script>

</body>
</html>