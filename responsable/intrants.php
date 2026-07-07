<?php
/**
 * responsable/intrants.php
 * Consultation des intrants utilisés par la coopérative
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('responsable');

$id_responsable = $_SESSION['id_utilisateur'];
$id_coop = '';
$intrants = [];
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
        
        // Récupérer les intrants utilisés de la coopérative
        $stmt = $pdo->prepare(
            "SELECT u.id_intrant, i.nom_intrant, i.type_intrant, i.unite_mesure,
                    COUNT(u.id_intrant) AS nb_utilisations,
                    SUM(u.quantite_utilisee) AS quantite_totale,
                    AVG(u.quantite_utilisee) AS quantite_moyenne,
                    MIN(u.date_utilisation) AS date_premiere,
                    MAX(u.date_utilisation) AS date_derniere
             FROM UTILISER u
             JOIN INTRANT i ON u.id_intrant = i.id_intrant
             JOIN PLANTATION pl ON u.id_plantation = pl.id_plantation
             JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
             JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
             WHERE a.id_coop = ?
             GROUP BY u.id_intrant, i.nom_intrant, i.type_intrant, i.unite_mesure
             ORDER BY SUM(u.quantite_utilisee) DESC"
        );
        $stmt->execute([$id_coop]);
        $intrants = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log('Erreur SQL intrants: ' . $e->getMessage());
    $erreur = 'Erreur lors du chargement des données.';
}

initialiserCsrf();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intrants utilisés - AgriGest Togo</title>
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
                <h2>Intrants utilisés</h2>
                <p>Consommation d'intrants de votre coopérative</p>
            </div>

            <?php if ($erreur): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($erreur); ?>
                </div>
            <?php else: ?>

            <!-- ========== STATISTIQUES RAPIDES ========== -->
            <section class="statistics">
                <h3>Aperçu de la consommation</h3>
                
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="label">Intrants différents</div>
                        <div class="number"><?php echo count($intrants); ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="label">Total utilisations</div>
                        <div class="number">
                            <?php 
                            $total_utilisations = array_sum(array_column($intrants, 'nb_utilisations'));
                            echo $total_utilisations;
                            ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="label">Types d'intrants</div>
                        <div class="number">
                            <?php 
                            $types = count(array_unique(array_column($intrants, 'type_intrant')));
                            echo $types;
                            ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="label">Intrants par type</div>
                        <div class="number">
                            <?php 
                            echo count($intrants) > 0 ? round(count($intrants) / $types, 1) : 0;
                            ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ========== STATISTIQUES PAR TYPE ========== -->
            <section class="management-section">
                <h3>Consommation par type d'intrant</h3>
                
                <?php 
                // Grouper par type
                $par_type = [];
                foreach ($intrants as $intrant) {
                    $type = $intrant['type_intrant'];
                    if (!isset($par_type[$type])) {
                        $par_type[$type] = [
                            'total_quantite' => 0,
                            'total_utilisations' => 0,
                            'count' => 0
                        ];
                    }
                    $par_type[$type]['total_quantite'] += $intrant['quantite_totale'];
                    $par_type[$type]['total_utilisations'] += $intrant['nb_utilisations'];
                    $par_type[$type]['count']++;
                }
                ?>

                <?php if (empty($par_type)): ?>
                    <div class="empty-state">
                        <p>Aucun intrant utilisé dans votre coopérative.</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Type d'intrant</th>
                                <th>Nombre d'intrants</th>
                                <th>Total utilisations</th>
                                <th>Quantité totale</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($par_type as $type => $data): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($type); ?></td>
                                    <td><?php echo $data['count']; ?></td>
                                    <td><?php echo $data['total_utilisations']; ?></td>
                                    <td><?php echo formaterNombre($data['total_quantite'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

            <!-- ========== LISTE DETAILLEE DES INTRANTS ========== -->
            <section class="management-section">
                <div class="section-header">
                    <h3>Détail des intrants utilisés</h3>
                </div>

                <?php if (empty($intrants)): ?>
                    <div class="empty-state">
                        <p>Aucun intrant utilisé dans votre coopérative.</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Type</th>
                                <th>Unité</th>
                                <th>Utilisations</th>
                                <th>Quantité totale</th>
                                <th>Quantité moyenne</th>
                                <th>Première utilisation</th>
                                <th>Dernière utilisation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($intrants as $intrant): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($intrant['id_intrant']); ?></td>
                                    <td><?php echo htmlspecialchars($intrant['nom_intrant']); ?></td>
                                    <td><?php echo htmlspecialchars($intrant['type_intrant']); ?></td>
                                    <td><?php echo htmlspecialchars($intrant['unite_mesure']); ?></td>
                                    <td><?php echo $intrant['nb_utilisations']; ?></td>
                                    <td><?php echo formaterNombre($intrant['quantite_totale'], 2); ?></td>
                                    <td><?php echo formaterNombre($intrant['quantite_moyenne'], 2); ?></td>
                                    <td><?php echo formaterDate($intrant['date_premiere']); ?></td>
                                    <td><?php echo formaterDate($intrant['date_derniere']); ?></td>
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