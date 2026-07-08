<?php
/**
 * responsable/parcelles.php
 * Consultation des parcelles de la coopérative
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('responsable');

$id_responsable = $_SESSION['id_utilisateur'];
$id_coop = '';
$parcelles = [];
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
        
        // Récupérer les parcelles de la coopérative
        $stmt = $pdo->prepare(
            "SELECT p.id_parcelle, p.nom_parcelle, p.localisation_parcelle, p.superficie,
                    z.nom_zone, u.nom, u.prenom, a.id_agri
             FROM PARCELLE p
             JOIN ZONE_AGROECOLOGIQUE z ON p.id_zone = z.id_zone
             JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
             JOIN UTILISATEUR u ON a.id_agri = u.id_utilisateur
             WHERE a.id_coop = ?
             ORDER BY p.nom_parcelle ASC"
        );
        $stmt->execute([$id_coop]);
        $parcelles = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log('Erreur SQL parcelles: ' . $e->getMessage());
    $erreur = 'Erreur lors du chargement des données.';
}

initialiserCsrf();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parcelles - AgriGest Togo</title>
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
            <!-- Dropdown pour overflow -->
            <div class="nav-dropdown">
                <button class="nav-dropdown-btn" aria-label="Plus d'options">
                    Plus <i class="fas fa-chevron-down"></i>
                </button>
                <div class="nav-dropdown-menu">
                    <!-- Les liens en excès vont ici dynamiquement -->
                </div>
            </div>
        </nav>
    </div>
        </header>

        <main>
            <div class="dashboard-header">
                <h2>Parcelles de la coopérative</h2>
                <p>Consultez toutes les parcelles de vos agriculteurs</p>
            </div>

            <?php if ($erreur): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($erreur); ?>
                </div>
            <?php else: ?>

            <!-- ========== STATISTIQUES RAPIDES ========== -->
            <section class="statistics">
                <h3>Aperçu des parcelles</h3>
                
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="label">Total parcelles</div>
                        <div class="number"><?php echo count($parcelles); ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="label">Superficie totale (ha)</div>
                        <div class="number">
                            <?php 
                            $superficie_total = array_sum(array_column($parcelles, 'superficie'));
                            echo formaterNombre($superficie_total, 2);
                            ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="label">Superficie moyenne (ha)</div>
                        <div class="number">
                            <?php 
                            $moyenne = count($parcelles) > 0 ? $superficie_total / count($parcelles) : 0;
                            echo formaterNombre($moyenne, 2);
                            ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="label">Zones exploitées</div>
                        <div class="number">
                            <?php 
                            $zones = count(array_unique(array_column($parcelles, 'nom_zone')));
                            echo $zones;
                            ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ========== LISTE DES PARCELLES ========== -->
            <section class="management-section">
                <div class="section-header">
                    <h3>Liste des parcelles</h3>
                </div>

                <?php if (empty($parcelles)): ?>
                    <div class="empty-state">
                        <p>Aucune parcelle enregistrée dans votre coopérative.</p>
                        <a href="agriculteurs.php" class="btn btn-primary">Ajouter un agriculteur</a>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Localisation</th>
                                <th>Superficie (ha)</th>
                                <th>Zone</th>
                                <th>Agriculteur</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parcelles as $parcelle): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($parcelle['id_parcelle']); ?></td>
                                    <td><?php echo htmlspecialchars($parcelle['nom_parcelle']); ?></td>
                                    <td><?php echo htmlspecialchars($parcelle['localisation_parcelle']); ?></td>
                                    <td><?php echo formaterNombre($parcelle['superficie'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($parcelle['nom_zone']); ?></td>
                                    <td><?php echo htmlspecialchars($parcelle['prenom'] . ' ' . $parcelle['nom']); ?></td>
                                    <td>
                                        <a href="consulter_parcelle.php?id=<?php echo urlencode($parcelle['id_parcelle']); ?>" class="btn btn-secondary btn-sm">Voir</a>
                                        <a href="consulter_agriculteur.php?id=<?php echo urlencode($parcelle['id_agri']); ?>" class="btn btn-secondary btn-sm">Agriculteur</a>
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

    
            <script src="../assets/js/script.js"></script>

</body>
</html>