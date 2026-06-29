<?php
session_start();
require_once '../config/connexion.php';

if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'responsable') {
    header('Location: ../auth/login.php');
    exit;
}

// Recuperer la cooperative du responsable connecte
$stmt = $pdo->prepare("SELECT c.nom_coop FROM UTILISATEUR u JOIN COOPERATIVE c ON u.id_coop = c.id_coop WHERE u.id_utilisateur = ?");
$stmt->execute([$_SESSION['id_utilisateur']]);
$coop = $stmt->fetch();
$nomCoop = $coop ? $coop['nom_coop'] : 'Aucune cooperative associee';
$idCoop = $_SESSION['id_coop'] ?? null;

// Statistiques filtrees sur SA cooperative uniquement
$nbAgriculteurs = 0;
$nbParcelles = 0;
$nbRecoltes = 0;

if (isset($_SESSION['id_coop'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM AGRICULTEUR WHERE id_coop = ?");
    $stmt->execute([$_SESSION['id_coop']]);
    $nbAgriculteurs = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total FROM PARCELLE p 
        JOIN AGRICULTEUR a ON p.id_agri = a.id_agri 
        WHERE a.id_coop = ?
    ");
    $stmt->execute([$_SESSION['id_coop']]);
    $nbParcelles = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total FROM RECOLTE r
        JOIN PLANTATION pl ON r.id_plantation = pl.id_plantation
        JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
        JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
        WHERE a.id_coop = ?
    ");
    $stmt->execute([$_SESSION['id_coop']]);
    $nbRecoltes = $stmt->fetch()['total'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord Responsable - AgriGest Togo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="page-container">
        <h1>Tableau de bord - Responsable de cooperative</h1>
        <p>Bienvenue, <?= htmlspecialchars($_SESSION['prenom']) ?> <?= htmlspecialchars($_SESSION['nom']) ?></p>
        <p>Cooperative : <strong><?= htmlspecialchars($nomCoop) ?></strong></p>

        <div class="card" style="margin-bottom: 20px;">
            <h2>Apercu de votre cooperative</h2>
            <p><?= $nbAgriculteurs ?> agriculteur(s) - <?= $nbParcelles ?> parcelle(s) - <?= $nbRecoltes ?> recolte(s) enregistree(s)</p>
        </div>

        <div class="card">
            <h2>Consultation</h2>
            <p><a href="bilan.php" class="btn-primary">Voir le bilan de production detaille</a></p>
        </div>

        <p style="margin-top: 20px;">
            <a href="../auth/logout.php">Se deconnecter</a>
        </p>
    </div>
</body>
</html>
