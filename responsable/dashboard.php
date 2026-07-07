<?php
/**
 * responsable/dashboard.php
 * Tableau de bord responsable de coopérative
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('responsable');

$id_responsable = $_SESSION['id_utilisateur'];
$id_coop = '';
$nom_coop = '';
$stats = [];
$erreur = '';

try {
    // ===== RÉCUPÉRER LA COOPÉRATIVE DU RESPONSABLE =====
    // ✅ CORRECTION : D'abord récupérer l'id_coop depuis RESPONSABLE
    $stmt = $pdo->prepare(
        "SELECT id_coop FROM RESPONSABLE WHERE id_responsable = ?"
    );
    $stmt->execute([$id_responsable]);
    $responsable = $stmt->fetch();
    
    if (!$responsable) {
        $erreur = 'Erreur: responsable non trouvé.';
    } else {
        $id_coop = $responsable['id_coop'];
        
        // ✅ Ensuite récupérer le nom de la coopérative
        $stmt = $pdo->prepare("SELECT nom_coop FROM COOPERATIVE WHERE id_coop = ?");
        $stmt->execute([$id_coop]);
        $coop = $stmt->fetch();
        $nom_coop = $coop['nom_coop'] ?? 'Coopérative inconnue';
        
        // ===== STATISTIQUES =====
        
        // Nombre d'agriculteurs
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS total FROM AGRICULTEUR WHERE id_coop = ?"
        );
        $stmt->execute([$id_coop]);
        $stats['agriculteurs'] = $stmt->fetch()['total'];
        
        // Nombre de parcelles
        $stmt = $pdo->prepare(
            "SELECT COUNT(p.id_parcelle) AS total FROM PARCELLE p
             JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
             WHERE a.id_coop = ?"
        );
        $stmt->execute([$id_coop]);
        $stats['parcelles'] = $stmt->fetch()['total'];
        
        // Superficie totale
        $stmt = $pdo->prepare(
            "SELECT SUM(p.superficie) AS total FROM PARCELLE p
             JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
             WHERE a.id_coop = ?"
        );
        $stmt->execute([$id_coop]);
        $result = $stmt->fetch();
        $stats['superficie'] = $result['total'] ?? 0;
        
        // Nombre de plantations
        $stmt = $pdo->prepare(
            "SELECT COUNT(pl.id_plantation) AS total FROM PLANTATION pl
             JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
             JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
             WHERE a.id_coop = ?"
        );
        $stmt->execute([$id_coop]);
        $stats['plantations'] = $stmt->fetch()['total'];
        
        // Nombre de récoltes
        $stmt = $pdo->prepare(
            "SELECT COUNT(r.id_recolte) AS total FROM RECOLTE r
             JOIN PLANTATION pl ON r.id_plantation = pl.id_plantation
             JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
             JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
             WHERE a.id_coop = ?"
        );
        $stmt->execute([$id_coop]);
        $stats['recoltes'] = $stmt->fetch()['total'];
        
        // Nombre de zones exploitées
        $stmt = $pdo->prepare(
            "SELECT COUNT(DISTINCT z.id_zone) AS total FROM ZONE_AGROECOLOGIQUE z
             JOIN PARCELLE p ON z.id_zone = p.id_zone
             JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
             WHERE a.id_coop = ?"
        );
        $stmt->execute([$id_coop]);
        $stats['zones'] = $stmt->fetch()['total'];
        
        // Nombre de cultures
        $stmt = $pdo->prepare(
            "SELECT COUNT(DISTINCT c.id_culture) AS total FROM CULTURE c
             JOIN PLANTATION pl ON c.id_culture = pl.id_culture
             JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
             JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
             WHERE a.id_coop = ?"
        );
        $stmt->execute([$id_coop]);
        $stats['cultures'] = $stmt->fetch()['total'];
        
        // Nombre d'intrants utilisés
        $stmt = $pdo->prepare(
            "SELECT COUNT(DISTINCT u.id_intrant) AS total FROM UTILISER u
             JOIN PLANTATION pl ON u.id_plantation = pl.id_plantation
             JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
             JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
             WHERE a.id_coop = ?"
        );
        $stmt->execute([$id_coop]);
        $stats['intrants'] = $stmt->fetch()['total'];
        
    }
} catch (PDOException $e) {
    error_log('Erreur SQL dashboard responsable: ' . $e->getMessage());
    $erreur = 'Erreur lors du chargement des données: ' . $e->getMessage();
}

initialiserCsrf();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Responsable - AgriGest Togo</title>
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
        <a href="../profil.php"><i class="fas fa-user-circle"></i> Mon profil</a>

    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
</nav>
            </div>
        </header>

        <main>
            <div class="dashboard-header">
                <h2>Tableau de bord</h2>
                <p>Coopérative : <?php echo htmlspecialchars($nom_coop); ?></p>
                <p>Bienvenue, <?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></p>
            </div>

            <?php if ($erreur): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($erreur); ?>
                </div>
            <?php else: ?>

            <!-- ========== STATISTIQUES PRINCIPALES ========== -->
            <section class="statistics">
                <h3>Aperçu de votre coopérative</h3>
                
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="label">Agriculteurs</div>
                        <div class="number"><?php echo $stats['agriculteurs'] ?? 0; ?></div>
                        <a href="agriculteurs.php" class="btn btn-secondary btn-sm">Gérer</a>
                    </div>

                    <div class="stat-card">
                        <div class="label">Parcelles</div>
                        <div class="number"><?php echo $stats['parcelles'] ?? 0; ?></div>
                        <a href="parcelles.php" class="btn btn-secondary btn-sm">Voir</a>
                    </div>

                    <div class="stat-card">
                        <div class="label">Superficie (ha)</div>
                        <div class="number"><?php echo formaterNombre($stats['superficie'] ?? 0, 2); ?></div>
                        <a href="parcelles.php" class="btn btn-secondary btn-sm">Détail</a>
                    </div>

                    <div class="stat-card">
                        <div class="label">Plantations</div>
                        <div class="number"><?php echo $stats['plantations'] ?? 0; ?></div>
                        <a href="plantations.php" class="btn btn-secondary btn-sm">Voir</a>
                    </div>

                    <div class="stat-card">
                        <div class="label">Récoltes</div>
                        <div class="number"><?php echo $stats['recoltes'] ?? 0; ?></div>
                        <a href="recoltes.php" class="btn btn-secondary btn-sm">Voir</a>
                    </div>

                    <div class="stat-card">
                        <div class="label">Zones</div>
                        <div class="number"><?php echo $stats['zones'] ?? 0; ?></div>
                        <a href="parcelles.php" class="btn btn-secondary btn-sm">Voir</a>
                    </div>

                    <div class="stat-card">
                        <div class="label">Cultures</div>
                        <div class="number"><?php echo $stats['cultures'] ?? 0; ?></div>
                        <a href="plantations.php" class="btn btn-secondary btn-sm">Voir</a>
                    </div>

                    <div class="stat-card">
                        <div class="label">Intrants</div>
                        <div class="number"><?php echo $stats['intrants'] ?? 0; ?></div>
                        <a href="intrants.php" class="btn btn-secondary btn-sm">Voir</a>
                    </div>
                </div>
            </section>

            <!-- ========== ACCES RAPIDES ========== -->
           <section class="management-section">
    <h3>Actions rapides</h3>
    
    <div class="quick-links">
        <a href="agriculteurs.php?action=create" class="quick-link">
            <div class="icon"><i class="fas fa-user-plus"></i></div>
            <div class="text">Ajouter un agriculteur</div>
        </a>

        <a href="parcelles.php" class="quick-link">
            <div class="icon"><i class="fas fa-clipboard-list"></i></div>
            <div class="text">Consulter les parcelles</div>
        </a>

        <a href="plantations.php" class="quick-link">
            <div class="icon"><i class="fas fa-seedling"></i></div>
            <div class="text">Consulter les plantations</div>
        </a>

        <a href="recoltes.php" class="quick-link">
            <div class="icon"><i class="fas fa-sun"></i></div>
            <div class="text">Consulter les récoltes</div>
        </a>

        <a href="bilan.php" class="quick-link">
            <div class="icon"><i class="fas fa-chart-bar"></i></div>
            <div class="text">Voir le bilan</div>
        </a>

        <a href="intrants.php" class="quick-link">
            <div class="icon"><i class="fas fa-chart-line"></i></div>
            <div class="text">Consommation d'intrants</div>
        </a>
    </div>
</section>
            <!-- ========== INFORMATIONS ========== -->
            <section class="management-section">
                <h3>Informations</h3>
                
                <div class="info-box">
                    <p><strong>Coopérative :</strong> <?php echo htmlspecialchars($nom_coop); ?></p>
                    <p><strong>Responsable :</strong> <?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></p>
                    <p><strong>Email :</strong> <?php echo htmlspecialchars($_SESSION['email']); ?></p>
                    <p><strong>Date actuelle :</strong> <?php echo strftime('%d %B %Y à %H:%M:%S', time()); ?></p>
                </div>
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