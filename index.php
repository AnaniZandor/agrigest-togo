<?php
/**
 * index.php
 * Page d'accueil d'AgriGest Togo
 */

// Démarrer la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirection automatique si l'utilisateur est déjà connecté
if (isset($_SESSION['role']) && isset($_SESSION['id_utilisateur'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        case 'responsable':
            header('Location: responsable/dashboard.php');
            break;
        case 'agriculteur':
            header('Location: agriculteur/dashboard.php');
            break;
        default:
            break;
    }
    exit;
}

// Récupérer les messages de session (ex: après déconnexion)
$logout_message = isset($_SESSION['logout_message']) ? $_SESSION['logout_message'] : null;
if ($logout_message) {
    unset($_SESSION['logout_message']);
}

$error_message = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : null;
if ($error_message) {
    unset($_SESSION['login_error']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriGest Togo - Gestion des Exploitations Agricoles</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="page-container">
        <div class="card" style="max-width: 500px; margin: 80px auto; text-align: center;">
            
            <!-- Messages de notification -->
            <?php if ($logout_message): ?>
                <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: var(--radius); margin-bottom: 15px; border: 1px solid #c3e6cb;">
                    <?= htmlspecialchars($logout_message) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: var(--radius); margin-bottom: 15px; border: 1px solid #f5c6cb;">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            
            <h1 style="color: var(--color-primary);">🌱 AgriGest Togo</h1>
            <p style="color: var(--color-text-muted); margin-bottom: 20px;">
                Système de gestion des exploitations agricoles au Togo
            </p>
            
            <!-- Présentation des rôles -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 20px 0; text-align: center; font-size: 0.9rem;">
                <div style="background: var(--color-bg); padding: 10px; border-radius: var(--radius);">
                    <strong style="color: var(--color-primary);">👤 Admin</strong>
                    <p style="margin: 5px 0 0; font-size: 0.8rem; color: var(--color-text-muted);">Gestion complète</p>
                </div>
                <div style="background: var(--color-bg); padding: 10px; border-radius: var(--radius);">
                    <strong style="color: var(--color-primary);">🤝 Responsable</strong>
                    <p style="margin: 5px 0 0; font-size: 0.8rem; color: var(--color-text-muted);">Suivi coopérative</p>
                </div>
                <div style="background: var(--color-bg); padding: 10px; border-radius: var(--radius);">
                    <strong style="color: var(--color-primary);">🚜 Agriculteur</strong>
                    <p style="margin: 5px 0 0; font-size: 0.8rem; color: var(--color-text-muted);">Gestion parcelles</p>
                </div>
            </div>
            
            <!-- Bouton de connexion -->
            <div style="margin-top: 25px;">
                <a href="auth/login.php" class="btn-primary" style="display: inline-block; padding: 12px 40px; background: var(--color-primary); color: white; border-radius: var(--radius); text-decoration: none; font-weight: 600; transition: background 0.3s;">
                    Se connecter
                </a>
            </div>
            
            <!-- Pied de page -->
            <div style="margin-top: 30px; padding-top: 15px; border-top: 1px solid #e9ecef; font-size: 0.85rem; color: var(--color-text-muted);">
                <p style="margin: 0;">
                    &copy; 2026 AgriGest Togo - Mini-projet Merise
                </p>
                <p style="margin: 5px 0 0;">
                    <a href="auth/login.php" style="color: var(--color-primary); text-decoration: none;">Zone sécurisée</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>