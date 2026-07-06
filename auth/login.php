<?php
/**
 * auth/login.php
 * Authentification des utilisateurs (admin, responsable, agriculteur)
 * Table UTILISATEUR centralisée avec roles distincts
 */

session_start();

// Si deja connecte, rediriger vers le dashboard approprie
if (isset($_SESSION['id_utilisateur']) && isset($_SESSION['role'])) {
    $redirection = [
        'admin' => '../admin/dashboard.php',
        'responsable' => '../responsable/dashboard.php',
        'agriculteur' => '../agriculteur/dashboard.php'
    ];
    header('Location: ' . ($redirection[$_SESSION['role']] ?? '../'));
    exit;
}

// Inclure les fichiers necessaires
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

$erreur = '';
$email = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Verifier le token CSRF
    if (!verifierCsrf()) {
        $erreur = 'Erreur de sécurité (CSRF). Veuillez réessayer.';
    } else {
        
        // Recuperer et nettoyer les donnees
        $email = nettoyer($_POST['email'] ?? '');
        $motDePasse = $_POST['password'] ?? '';
        
        // Validations basiques
        if (empty($email) || empty($motDePasse)) {
            $erreur = 'Email et mot de passe requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreur = 'Format d\'email invalide.';
        } else {
            
            // Rechercher l'utilisateur par email
            try {
                $stmt = $pdo->prepare(
                    "SELECT id_utilisateur, nom, prenom, email, mot_de_passe, role 
                     FROM UTILISATEUR 
                     WHERE email = ?"
                );
                $stmt->execute([$email]);
                $utilisateur = $stmt->fetch();
                
                if ($utilisateur && password_verify($motDePasse, $utilisateur['mot_de_passe'])) {
                    
                    // Authentification reussie
                    $_SESSION['id_utilisateur'] = $utilisateur['id_utilisateur'];
                    $_SESSION['role'] = $utilisateur['role'];
                    $_SESSION['nom'] = $utilisateur['nom'];
                    $_SESSION['prenom'] = $utilisateur['prenom'];
                    $_SESSION['email'] = $utilisateur['email'];
                    
                    // Redirection selon le role
                    $redirection = [
                        'admin' => '../admin/dashboard.php',
                        'responsable' => '../responsable/dashboard.php',
                        'agriculteur' => '../agriculteur/dashboard.php'
                    ];
                    header('Location: ' . $redirection[$utilisateur['role']]);
                    exit;
                    
                } else {
                    $erreur = 'Email ou mot de passe incorrect.';
                }
                
            } catch (PDOException $e) {
                $erreur = 'Erreur lors de la connexion à la base de données.';
                error_log('Erreur SQL login: ' . $e->getMessage());
            }
        }
    }
}

// Initialiser le token CSRF pour le formulaire
initialiserCsrf();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriGest Togo - Connexion</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🌾 AgriGest Togo</h1>
            <p>Gestion des Exploitations Agricoles</p>
        </div>
        
        <?php if ($erreur): ?>
            <div class="erreur">
                <?php echo htmlspecialchars($erreur); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?php echo htmlspecialchars($email); ?>" 
                    required 
                    autofocus
                >
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                >
            </div>
            
            <!-- Token CSRF -->
            <input 
                type="hidden" 
                name="csrf_token" 
                value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
            >
            
            <button type="submit">Se connecter</button>
        </form>
        
        <div class="info-text">
            Contactez l'administrateur pour créer un compte
        </div>
    </div>
</body>
</html>