<?php
/**
 * responsable/consulter_parcelle.php
 * Consultation détaillée d'une parcelle
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('responsable');

$id_parcelle = $_GET['id'] ?? null;
$erreur = '';
$parcelle = null;
$plantations = [];

if (!$id_parcelle) {
    header('Location: parcelles.php');
    exit;
}

try {
    // Récupérer les informations de la parcelle
    $stmt = $pdo->prepare(
        "SELECT p.id_parcelle, p.nom_parcelle, p.localisation_parcelle, p.superficie,
                z.nom_zone, u.nom, u.prenom, a.id_agri
         FROM PARCELLE p
         JOIN ZONE_AGROECOLOGIQUE z ON p.id_zone = z.id_zone
         JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
         JOIN UTILISATEUR u ON a.id_agri = u.id_utilisateur
         WHERE p.id_parcelle = ?"
    );
    $stmt->execute([$id_parcelle]);
    $parcelle = $stmt->fetch();

    if (!$parcelle) {
        $erreur = 'Parcelle non trouvée.';
    } else {
        // Récupérer les plantations de la parcelle
        $stmt = $pdo->prepare(
            "SELECT pl.id_plantation, pl.date_semis, c.nom_culture, s.libelle_saison
             FROM PLANTATION pl
             JOIN CULTURE c ON pl.id_culture = c.id_culture
             JOIN SAISON s ON pl.id_saison = s.id_saison
             WHERE pl.id_parcelle = ?
             ORDER BY pl.date_semis DESC"
        );
        $stmt->execute([$id_parcelle]);
        $plantations = $stmt->fetchAll();
    }

} catch (PDOException $e) {
    error_log('Erreur SQL consulter_parcelle: ' . $e->getMessage());
    $erreur = 'Erreur lors du chargement des données.';
}

initialiserCsrf();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulter Parcelle - AgriGest Togo</title>
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
<h1><i class="fas fa-seedling" style="color: var(--color-primary);"></i> AgriGest Togo - Responsable</h1>
                <button class="hamburger" id="hamburgerBtn" aria-label="Menu principal" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <nav id="navMenu">
    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
    <a href="agriculteurs.php"><i class="fas fa-user-tie"></i> Agriculteurs</a>
    <a href="parcelles.php"><i class="fas fa-map"></i> Parcelles</a>
    <a href="plantations.php"><i class="fas fa-seedling"></i> Plantations</a>
    <a href="recoltes.php"><i class="fas fa-sun"></i> Récoltes</a>
    <a href="intrants.php"><i class="fas fa-flask"></i> Intrants</a>
    <a href="bilan.php"><i class="fas fa-chart-bar"></i> Bilan</a>
    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
</nav>
            </div>
        </header>

        <main>
            <div class="dashboard-header">
                <h2>Détails de la parcelle</h2>
                <p><a href="parcelles.php">&larr; Retour à la liste</a></p>
            </div>

            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erreur); ?></div>
            <?php elseif ($parcelle): ?>

            <!-- ===== INFORMATIONS DE LA PARCELLE ===== -->
            <div class="card" style="margin-bottom: 30px;">
                <h3>Informations de la parcelle</h3>
                <div class="info-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:var(--spacing-md);">
                    <p><strong>ID :</strong> <?php echo htmlspecialchars($parcelle['id_parcelle']); ?></p>
                    <p><strong>Nom :</strong> <?php echo htmlspecialchars($parcelle['nom_parcelle']); ?></p>
                    <p><strong>Localisation :</strong> <?php echo htmlspecialchars($parcelle['localisation_parcelle']); ?></p>
                    <p><strong>Superficie :</strong> <?php echo formaterNombre($parcelle['superficie'], 2); ?> ha</p>
                    <p><strong>Zone :</strong> <?php echo htmlspecialchars($parcelle['nom_zone']); ?></p>
                    <p><strong>Propriétaire :</strong> <?php echo htmlspecialchars($parcelle['prenom'] . ' ' . $parcelle['nom']); ?></p>
                </div>
            </div>

            <!-- ===== LISTE DES PLANTATIONS ===== -->
            <div class="card">
                <h3>Plantations sur cette parcelle</h3>
                
                <?php if (empty($plantations)): ?>
                    <p class="text-muted">Aucune plantation enregistrée sur cette parcelle.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Culture</th>
                                <th>Saison</th>
                                <th>Date de semis</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($plantations as $plantation): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($plantation['id_plantation']); ?></td>
                                    <td><?php echo htmlspecialchars($plantation['nom_culture']); ?></td>
                                    <td><?php echo htmlspecialchars($plantation['libelle_saison']); ?></td>
                                    <td><?php echo formaterDate($plantation['date_semis']); ?></td>
                                    <td>
                                        <a href="consulter_plantation.php?id=<?php echo urlencode($plantation['id_plantation']); ?>" class="btn btn-secondary btn-sm">Voir</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <?php endif; ?>

        </main>

        <footer>
            <p>&copy; 2024 AgriGest Togo - Gestion des Exploitations Agricoles</p>
        </footer>
    </div>


            <script src="../assets/js/script.js"></script>

</body>
</html>