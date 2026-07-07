<?php
/**
 * responsable/recoltes.php
 * Consultation des récoltes de la coopérative
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('responsable');

$id_responsable = $_SESSION['id_utilisateur'];
$id_coop = '';
$recoltes = [];
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
        
        // Récupérer les récoltes de la coopérative
        $stmt = $pdo->prepare(
            "SELECT r.id_recolte, r.date_recolte, r.rendement,
                    c.nom_culture, p.nom_parcelle, s.libelle_saison,
                    u.nom, u.prenom, a.id_agri, pl.date_semis
             FROM RECOLTE r
             JOIN PLANTATION pl ON r.id_plantation = pl.id_plantation
             JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
             JOIN CULTURE c ON pl.id_culture = c.id_culture
             JOIN SAISON s ON pl.id_saison = s.id_saison
             JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
             JOIN UTILISATEUR u ON a.id_agri = u.id_utilisateur
             WHERE a.id_coop = ?
             ORDER BY r.date_recolte DESC"
        );
        $stmt->execute([$id_coop]);
        $recoltes = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log('Erreur SQL recoltes: ' . $e->getMessage());
    $erreur = 'Erreur lors du chargement des données.';
}

initialiserCsrf();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récoltes - AgriGest Togo</title>
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
                <h2>Récoltes de la coopérative</h2>
                <p>Consultez toutes les récoltes enregistrées de vos agriculteurs</p>
            </div>

            <?php if ($erreur): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($erreur); ?>
                </div>
            <?php else: ?>

            <!-- ========== STATISTIQUES RAPIDES ========== -->
            <section class="statistics">
                <h3>Aperçu des récoltes</h3>
                
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="label">Total récoltes</div>
                        <div class="number"><?php echo count($recoltes); ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="label">Rendement total (kg)</div>
                        <div class="number">
                            <?php 
                            $rendement_total = array_sum(array_column($recoltes, 'rendement'));
                            echo formaterNombre($rendement_total, 2);
                            ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="label">Rendement moyen (kg)</div>
                        <div class="number">
                            <?php 
                            $rendement_moyen = count($recoltes) > 0 ? $rendement_total / count($recoltes) : 0;
                            echo formaterNombre($rendement_moyen, 2);
                            ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="label">Cultures récoltées</div>
                        <div class="number">
                            <?php 
                            $cultures = count(array_unique(array_column($recoltes, 'nom_culture')));
                            echo $cultures;
                            ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ========== LISTE DES RECOLTES ========== -->
            <section class="management-section">
                <div class="section-header">
                    <h3>Liste des récoltes</h3>
                </div>

                <?php if (empty($recoltes)): ?>
                    <div class="empty-state">
                        <p>Aucune récolte enregistrée dans votre coopérative.</p>
                        <a href="agriculteurs.php" class="btn btn-primary">Ajouter un agriculteur</a>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Culture</th>
                                <th>Parcelle</th>
                                <th>Date semis</th>
                                <th>Date récolte</th>
                                <th>Rendement (kg)</th>
                                <th>Saison</th>
                                <th>Agriculteur</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recoltes as $recolte): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($recolte['id_recolte']); ?></td>
                                    <td><?php echo htmlspecialchars($recolte['nom_culture']); ?></td>
                                    <td><?php echo htmlspecialchars($recolte['nom_parcelle']); ?></td>
                                    <td><?php echo formaterDate($recolte['date_semis']); ?></td>
                                    <td><?php echo formaterDate($recolte['date_recolte']); ?></td>
                                    <td><?php echo formaterNombre($recolte['rendement'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($recolte['libelle_saison']); ?></td>
                                    <td><?php echo htmlspecialchars($recolte['prenom'] . ' ' . $recolte['nom']); ?></td>
                                    <td>
                                        <a href="consulter_recolte.php?id=<?php echo urlencode($recolte['id_recolte']); ?>" class="btn btn-secondary btn-sm">Voir</a>
                                        <a href="consulter_agriculteur.php?id=<?php echo urlencode($recolte['id_agri']); ?>" class="btn btn-secondary btn-sm">Agriculteur</a>
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
            <script src="../assets/js/script.js"></script>

</body>
</html>