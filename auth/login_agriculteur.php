<?php
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
        $stmt = $pdo->prepare("SELECT * FROM AGRICULTEUR WHERE email_agri = ?");
        $stmt->execute([$email]);
        $agriculteur = $stmt->fetch();

        if ($agriculteur && password_verify($motDePasse, $agriculteur['mot_de_passe_agri'])) {
            $_SESSION['id_agri'] = $agriculteur['id_agri'];
            $_SESSION['nom_agri'] = $agriculteur['nom_agri'];
            $_SESSION['prenom_agri'] = $agriculteur['prenom_agri'];
            $_SESSION['id_coop_agri'] = $agriculteur['id_coop'];
            $_SESSION['role'] = 'agriculteur';

            header('Location: ../agriculteur/dashboard.php');
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
    <title>Connexion Agriculteur - AgriGest Togo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="page-container">
        <div class="card" style="max-width: 400px; margin: 80px auto;">
            <h1>AgriGest Togo</h1>
            <h2>Connexion Agriculteur</h2>

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
                <a href="../index.php">Retour a l'accueil</a>
            </p>
        </div>
    </div>
</body>
</html>