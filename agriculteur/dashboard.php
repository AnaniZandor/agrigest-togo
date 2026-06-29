<?php
session_start();
require_once '../config/connexion.php';

if (!isset($_SESSION['id_agri']) || $_SESSION['role'] !== 'agriculteur') {
    header('Location: ../auth/login_agriculteur.php');
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM PARCELLE WHERE id_agri = ?");
$stmt->execute([$_SESSION['id_agri']]);
$nbParcelles = $stmt->fetch()['total'];

$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total FROM PLANTATION pl
    JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
    WHERE p.id_agri = ?
");
$stmt->execute([$_SESSION['id_agri']]);
$nbPlantations = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon espace - AgriGest Togo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="page-container">
        <h1>Mon espace agriculteur</h1>
        <p>Bienvenue, <?= htmlspecialchars($_SESSION['prenom_agri']) ?> <?= htmlspecialchars($_SESSION['nom_agri']) ?></p>

        <div class="card" style="margin-bottom: 20px;">
            <h2>Apercu</h2>
            <p><?= $nbParcelles ?> parcelle(s) - <?= $nbPlantations ?> plantation(s) enregistree(s)</p>
        </div>

        <div class="card">
            <h2>Mes activites</h2>
            <p><a href="mes_parcelles.php" class="btn-primary">Consulter mes parcelles</a></p>
            <p><a href="saisir_plantation.php" class="btn-primary">Declarer une mise en culture</a></p>
            <p><a href="saisir_intrant.php" class="btn-secondary">Declarer l'utilisation d'un intrant</a></p>
            <p><a href="saisir_recolte.php" class="btn-secondary">Enregistrer une recolte</a></p>
        </div>

        <p style="margin-top: 20px;">
            <a href="../auth/logout.php">Se deconnecter</a>
        </p>
    </div>
</body>
</html>