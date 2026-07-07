<?php
/**
 * responsable/plantations.php
 * Consultation des plantations de la coopérative
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('responsable');

$id_responsable = $_SESSION['id_utilisateur'];
$id_coop = '';
$plantations = [];
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
        
        // Récupérer les plantations de la coopérative
        $stmt = $pdo->prepare(
            "SELECT pl.id_plantation, pl.date_semis,
                    c.nom_culture, s.libelle_saison, p.nom_parcelle,
                    z.nom_zone, u.nom, u.prenom, a.id_agri
             FROM PLANTATION pl
             JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
             JOIN CULTURE c ON pl.id_culture = c.id_culture
             JOIN SAISON s ON pl.id_saison = s.id_saison
             JOIN ZONE_AGROECOLOGIQUE z ON p.id_zone = z.id_zone
             JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
             JOIN UTILISATEUR u ON a.id_agri = u.id_utilisateur
             WHERE a.id_coop = ?
             ORDER BY pl.date_semis DESC"
        );
        $stmt->execute([$id_coop]);
        $plantations = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log('Erreur SQL plantations: ' . $e->getMessage());
    $erreur = 'Erreur lors du chargement des données.';
}

initialiserCsrf();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plantations - AgriGest Togo</title>
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
                <h1>🌾 AgriGest Togo - Responsable</h1>
                <button class="hamburger" id="hamburgerBtn" aria-label="Menu principal" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <nav id="navMenu">
                    <a href="dashboard.php">Tableau de bord</a>
                    <a href="agriculteurs.php">Agriculteurs</a>
                    <a href="parcelles.php">Parcelles</a>
                    <a href="plantations.php">Plantations</a>
                    <a href="recoltes.php">Récoltes</a>
                    <a href="intrants.php">Intrants</a>
                    <a href="bilan.php">Bilan</a>
                    <a href="../auth/logout.php">Déconnexion</a>
                </nav>
            </div>
        </header>

        <main>
            <div class="dashboard-header">
                <h2>Plantations de la coopérative</h2>
                <p>Consultez toutes les plantations actives de vos agriculteurs</p>
            </div>

            <?php if ($erreur): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($erreur); ?>
                </div>
            <?php else: ?>

            <!-- ========== STATISTIQUES RAPIDES ========== -->
            <section class="statistics">
                <h3>Aperçu des plantations</h3>
                
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="label">Total plantations</div>
                        <div class="number"><?php echo count($plantations); ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="label">Cultures différentes</div>
                        <div class="number">
                            <?php 
                            $cultures = count(array_unique(array_column($plantations, 'nom_culture')));
                            echo $cultures;
                            ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="label">Saisons différentes</div>
                        <div class="number">
                            <?php 
                            $saisons = count(array_unique(array_column($plantations, 'libelle_saison')));
                            echo $saisons;
                            ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="label">Zones exploitées</div>
                        <div class="number">
                            <?php 
                            $zones = count(array_unique(array_column($plantations, 'nom_zone')));
                            echo $zones;
                            ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ========== LISTE DES PLANTATIONS ========== -->
            <section class="management-section">
                <div class="section-header">
                    <h3>Liste des plantations</h3>
                </div>

                <?php if (empty($plantations)): ?>
                    <div class="empty-state">
                        <p>Aucune plantation enregistrée dans votre coopérative.</p>
                        <a href="agriculteurs.php" class="btn btn-primary">Ajouter un agriculteur</a>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Culture</th>
                                <th>Parcelle</th>
                                <th>Zone</th>
                                <th>Saison</th>
                                <th>Date semis</th>
                                <th>Agriculteur</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($plantations as $plantation): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($plantation['id_plantation']); ?></td>
                                    <td><?php echo htmlspecialchars($plantation['nom_culture']); ?></td>
                                    <td><?php echo htmlspecialchars($plantation['nom_parcelle']); ?></td>
                                    <td><?php echo htmlspecialchars($plantation['nom_zone']); ?></td>
                                    <td><?php echo htmlspecialchars($plantation['libelle_saison']); ?></td>
                                    <td><?php echo formaterDate($plantation['date_semis']); ?></td>
                                    <td><?php echo htmlspecialchars($plantation['prenom'] . ' ' . $plantation['nom']); ?></td>
                                    <td>
                                        <a href="consulter_plantation.php?id=<?php echo urlencode($plantation['id_plantation']); ?>" class="btn btn-secondary btn-sm">Voir</a>
                                        <a href="consulter_agriculteur.php?id=<?php echo urlencode($plantation['id_agri']); ?>" class="btn btn-secondary btn-sm">Agriculteur</a>
                                    </td>
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
</body>
</html>