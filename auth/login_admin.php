<?php
/**
 * auth/login_admin.php
 * Page de connexion pour Administrateur et Responsable de cooperative
 */

session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = nettoyer($_POST['email'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';

    if (empty($email) || empty($motDePasse)) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM UTILISATEUR WHERE email = ?");
        $stmt->execute([$email]);
        $utilisateur = $stmt->fetch();

        if ($utilisateur && password_verify($motDePasse, $utilisateur['mot_de_passe'])) {
            // Connexion reussie : on stocke les infos en session
            $_SESSION['id_utilisateur'] = $utilisateur['id_utilisateur'];
            $_SESSION['nom'] = $utilisateur['nom'];
            $_SESSION['prenom'] = $utilisateur['prenom'];
            $_SESSION['role'] = $utilisateur['role'];

            // Redirection selon le role
 if ($utilisateur['role'] === 'admin') {
    header('Location: ../admin/dashboard.php');
} else {
    header('Location: ../responsable/dashboard.php');
}
exit;

        } else {
            $erreur = "Email ou mot de passe incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - AgriGest Togo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="page-container">
        <div class="card" style="max-width: 400px; margin: 80px auto;">
            <h1>🌱 AgriGest Togo</h1>
            <h2>Connexion Administrateur / Responsable</h2>

            <?php if ($erreur): ?>
                <div class="alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>

            <form method="POST" class="form-card">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="mot_de_passe">Mot de passe</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" required>
                </div>

                <button type="submit" class="btn-primary">Se connecter</button>
            </form>

            <p style="margin-top: 20px;">
                <a href="../index.php">&larr; Retour a l'accueil</a>
            </p>
        </div>
    </div>
</body>
</html>