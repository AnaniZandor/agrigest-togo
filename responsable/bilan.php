<?php
/**
 * responsable/bilan.php
 * Bilan détaillé et statistiques de la coopérative
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('responsable');

$id_responsable = $_SESSION['id_utilisateur'];
$id_coop = '';
$nom_coop = '';
$bilan = [];
$rendements_culture = [];
$rendements_zone = [];
$intrants_top = [];
$erreur = '';

try {
    // Récupérer la coopérative du responsable
    $stmt = $pdo->prepare(
        "SELECT id_coop FROM RESPONSABLE WHERE id_responsable = ?"
    );
    $stmt->execute([$id_responsable]);
    $responsable = $stmt->fetch();
    
    if (!$responsable) {
        $erreur = 'Erreur: coopérative non trouvée.';
    } else {
        $id_coop = $responsable['id_coop'];
        
        // Récupérer le nom de la coopérative
        $stmt = $pdo->prepare("SELECT nom_coop FROM COOPERATIVE WHERE id_coop = ?");
        $stmt->execute([$id_coop]);
        $coop = $stmt->fetch();
        $nom_coop = $coop['nom_coop'] ?? '';
        
        // ===== STATISTIQUES GLOBALES =====
        
        // Total rendement récoltes
        $stmt = $pdo->prepare(
            "SELECT SUM(r.rendement) AS total_rendement FROM RECOLTE r
             JOIN PLANTATION pl ON r.id_plantation = pl.id_plantation
             JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
             JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
             WHERE a.id_coop = ?"
        );
        $stmt->execute([$id_coop]);
        $result = $stmt->fetch();
        $bilan['rendement_total'] = $result['total_rendement'] ?? 0;
        
        // Rendement moyen par récolte
        $stmt = $pdo->prepare(
            "SELECT AVG(r.rendement) AS rendement_moyen FROM RECOLTE r
             JOIN PLANTATION pl ON r.id_plantation = pl.id_plantation
             JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
             JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
             WHERE a.id_coop = ?"
        );
        $stmt->execute([$id_coop]);
        $result = $stmt->fetch();
        $bilan['rendement_moyen'] = $result['rendement_moyen'] ?? 0;
        
        // Superficie moyenne par parcelle
        $stmt = $pdo->prepare(
            "SELECT AVG(p.superficie) AS superficie_moyenne FROM PARCELLE p
             JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
             WHERE a.id_coop = ?"
        );
        $stmt->execute([$id_coop]);
        $result = $stmt->fetch();
        $bilan['superficie_moyenne'] = $result['superficie_moyenne'] ?? 0;
        
        // Nombre de plantations cette saison (dernière saison)
        $stmt = $pdo->prepare(
            "SELECT COUNT(DISTINCT pl.id_plantation) AS plantations FROM PLANTATION pl
             JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
             JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
             WHERE a.id_coop = ?"
        );
        $stmt->execute([$id_coop]);
        $result = $stmt->fetch();
        $bilan['plantations_total'] = $result['plantations'] ?? 0;
        
        // ===== RENDEMENTS PAR CULTURE =====
        $stmt = $pdo->prepare(
            "SELECT c.nom_culture, 
                    COUNT(r.id_recolte) AS nb_recoltes,
                    SUM(r.rendement) AS rendement_total,
                    AVG(r.rendement) AS rendement_moyen
             FROM CULTURE c
             LEFT JOIN PLANTATION pl ON c.id_culture = pl.id_culture
             LEFT JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
             LEFT JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
             LEFT JOIN RECOLTE r ON pl.id_plantation = r.id_plantation
             WHERE a.id_coop = ? OR a.id_coop IS NULL
             GROUP BY c.id_culture, c.nom_culture
             ORDER BY SUM(r.rendement) DESC"
        );
        $stmt->execute([$id_coop]);
        $rendements_culture = $stmt->fetchAll();
        
        // ===== RENDEMENTS PAR ZONE =====
        $stmt = $pdo->prepare(
            "SELECT z.nom_zone,
                    COUNT(p.id_parcelle) AS nb_parcelles,
                    SUM(p.superficie) AS superficie_totale,
                    COUNT(DISTINCT pl.id_plantation) AS nb_plantations,
                    SUM(r.rendement) AS rendement_total,
                    AVG(r.rendement) AS rendement_moyen
             FROM ZONE_AGROECOLOGIQUE z
             LEFT JOIN PARCELLE p ON z.id_zone = p.id_zone
             LEFT JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
             LEFT JOIN PLANTATION pl ON p.id_parcelle = pl.id_parcelle
             LEFT JOIN RECOLTE r ON pl.id_plantation = r.id_plantation
             WHERE a.id_coop = ? OR a.id_coop IS NULL
             GROUP BY z.id_zone, z.nom_zone
             ORDER BY SUM(r.rendement) DESC"
        );
        $stmt->execute([$id_coop]);
        $rendements_zone = $stmt->fetchAll();
        
        // ===== INTRANTS LES PLUS UTILISES =====
        $stmt = $pdo->prepare(
            "SELECT i.nom_intrant, i.type_intrant, i.unite_mesure,
                    COUNT(u.id_intrant) AS nb_utilisations,
                    SUM(u.quantite_utilisee) AS quantite_totale,
                    AVG(u.quantite_utilisee) AS quantite_moyenne
             FROM INTRANT i
             LEFT JOIN UTILISER u ON i.id_intrant = u.id_intrant
             LEFT JOIN PLANTATION pl ON u.id_plantation = pl.id_plantation
             LEFT JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
             LEFT JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
             WHERE a.id_coop = ? OR a.id_coop IS NULL
             GROUP BY i.id_intrant, i.nom_intrant, i.type_intrant, i.unite_mesure
             HAVING COUNT(u.id_intrant) > 0
             ORDER BY SUM(u.quantite_utilisee) DESC
             LIMIT 10"
        );
        $stmt->execute([$id_coop]);
        $intrants_top = $stmt->fetchAll();
        
    }
} catch (PDOException $e) {
    error_log('Erreur SQL bilan: ' . $e->getMessage());
    $erreur = 'Erreur lors du chargement des données.';
}

initialiserCsrf();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilan - AgriGest Togo</title>
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
                <h2>Bilan détaillé</h2>
                <p>Coopérative : <?php echo htmlspecialchars($nom_coop); ?></p>
            </div>

            <?php if ($erreur): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($erreur); ?>
                </div>
            <?php else: ?>

            <!-- ========== STATISTIQUES GLOBALES ========== -->
            <section class="statistics">
                <h3>Résumé des performances</h3>
                
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="label">Rendement total (kg)</div>
                        <div class="number"><?php echo formaterNombre($bilan['rendement_total'], 2); ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="label">Rendement moyen (kg)</div>
                        <div class="number"><?php echo formaterNombre($bilan['rendement_moyen'], 2); ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="label">Superficie moyenne (ha)</div>
                        <div class="number"><?php echo formaterNombre($bilan['superficie_moyenne'], 2); ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="label">Plantations</div>
                        <div class="number"><?php echo $bilan['plantations_total']; ?></div>
                    </div>
                </div>
            </section>

            <!-- ========== RENDEMENTS PAR CULTURE ========== -->
            <section class="management-section">
                <h3>Rendements par culture</h3>
                
                <?php if (empty($rendements_culture)): ?>
                    <div class="empty-state">
                        <p>Aucune donnée de récolte disponible.</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Culture</th>
                                <th>Récoltes</th>
                                <th>Rendement total (kg)</th>
                                <th>Rendement moyen (kg)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rendements_culture as $culture): ?>
                                <?php if ($culture['nb_recoltes'] > 0): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($culture['nom_culture']); ?></td>
                                        <td><?php echo $culture['nb_recoltes']; ?></td>
                                        <td><?php echo formaterNombre($culture['rendement_total'], 2); ?></td>
                                        <td><?php echo formaterNombre($culture['rendement_moyen'], 2); ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

            <!-- ========== RENDEMENTS PAR ZONE ========== -->
            <section class="management-section">
                <h3>Rendements par zone agroécologique</h3>
                
                <?php if (empty($rendements_zone)): ?>
                    <div class="empty-state">
                        <p>Aucune donnée de zone disponible.</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Zone</th>
                                <th>Parcelles</th>
                                <th>Superficie (ha)</th>
                                <th>Plantations</th>
                                <th>Rendement total (kg)</th>
                                <th>Rendement moyen (kg)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rendements_zone as $zone): ?>
                                <?php if ($zone['nb_parcelles'] > 0): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($zone['nom_zone']); ?></td>
                                        <td><?php echo $zone['nb_parcelles']; ?></td>
                                        <td><?php echo formaterNombre($zone['superficie_totale'], 2); ?></td>
                                        <td><?php echo $zone['nb_plantations']; ?></td>
                                        <td><?php echo formaterNombre($zone['rendement_total'], 2); ?></td>
                                        <td><?php echo formaterNombre($zone['rendement_moyen'], 2); ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

            <!-- ========== INTRANTS UTILISES ========== -->
            <section class="management-section">
                <h3>Intrants les plus utilisés</h3>
                
                <?php if (empty($intrants_top)): ?>
                    <div class="empty-state">
                        <p>Aucune utilisation d'intrant enregistrée.</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Intrant</th>
                                <th>Type</th>
                                <th>Unité</th>
                                <th>Utilisations</th>
                                <th>Quantité totale</th>
                                <th>Quantité moyenne</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($intrants_top as $intrant): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($intrant['nom_intrant']); ?></td>
                                    <td><?php echo htmlspecialchars($intrant['type_intrant']); ?></td>
                                    <td><?php echo htmlspecialchars($intrant['unite_mesure']); ?></td>
                                    <td><?php echo $intrant['nb_utilisations']; ?></td>
                                    <td><?php echo formaterNombre($intrant['quantite_totale'], 2); ?></td>
                                    <td><?php echo formaterNombre($intrant['quantite_moyenne'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

            <?php endif; ?>

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
        });
    </script>
            <script src="../assets/js/script.js"></script>

</body>
</html>