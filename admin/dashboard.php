<?php
session_start();
require_once '../config/connexion.php';

// Verification que l'utilisateur est bien connecte et est admin
if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login_admin.php');
    exit;
}

// Quelques statistiques rapides pour le dashboard
$nbAgriculteurs = $pdo->query("SELECT COUNT(*) AS total FROM AGRICULTEUR")->fetch()['total'];
$nbParcelles = $pdo->query("SELECT COUNT(*) AS total FROM PARCELLE")->fetch()['total'];
$nbCooperatives = $pdo->query("SELECT COUNT(*) AS total FROM COOPERATIVE")->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord Admin - AgriGest Togo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="page-container">
        <h1>Tableau de bord - Administrateur</h1>
        <p>Bienvenue, <?= htmlspecialchars($_SESSION['prenom']) ?> <?= htmlspecialchars($_SESSION['nom']) ?></p>

        <div class="card" style="margin-bottom: 20px;">
            <h2>Apercu</h2>
            <p><?= $nbCooperatives ?> cooperative(s) - <?= $nbAgriculteurs ?> agriculteur(s) - <?= $nbParcelles ?> parcelle(s)</p>
        </div>

        <div class="card">
            <h2>Gestion</h2>
            <p><a href="agriculteurs.php" class="btn-primary">Gerer les agriculteurs et cooperatives</a></p>
            <p><a href="parcelles.php" class="btn-primary">Gerer les parcelles</a></p>
            <p><a href="zones.php" class="btn-secondary">Gerer les zones agro-ecologiques</a></p>
            <p><a href="cultures.php" class="btn-secondary">Gerer le referentiel des cultures</a></p>
            <p><a href="intrants.php" class="btn-secondary">Gerer le referentiel des intrants</a></p>
        </div>

        <p style="margin-top: 20px;">
            <a href="../auth/logout.php">Se deconnecter</a>
        </p>
    </div>
</body>
</html>