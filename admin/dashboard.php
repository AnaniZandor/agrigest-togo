<?php
/**
 * admin/dashboard.php
 * Tableau de bord administrateur
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('admin');

// Recuperer les statistiques
$stats = [];

try {
    // Nombre d'agriculteurs
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM AGRICULTEUR");
    $stats['agriculteurs'] = $stmt->fetch()['total'];
    
    // Nombre de coopératives
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM COOPERATIVE");
    $stats['cooperatives'] = $stmt->fetch()['total'];
    
    // Nombre de cultures
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM CULTURE");
    $stats['cultures'] = $stmt->fetch()['total'];
    
    // Nombre de parcelles
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM PARCELLE");
    $stats['parcelles'] = $stmt->fetch()['total'];
    
    // Nombre de plantations
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM PLANTATION");
    $stats['plantations'] = $stmt->fetch()['total'];
    
    // Nombre de récoltes
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM RECOLTE");
    $stats['recoltes'] = $stmt->fetch()['total'];
    
    // Nombre de zones agroécologiques
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM ZONE_AGROECOLOGIQUE");
    $stats['zones'] = $stmt->fetch()['total'];
    
    // Nombre d'intrants
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM INTRANT");
    $stats['intrants'] = $stmt->fetch()['total'];
    
    // Nombre de saisons
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM SAISON");
    $stats['saisons'] = $stmt->fetch()['total'];
    
} catch (PDOException $e) {
    error_log('Erreur SQL dashboard: ' . $e->getMessage());
}

initialiserCsrf();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Admin - AgriGest Togo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🌾 AgriGest Togo - Admin</h1>
            <nav>
                <a href="dashboard.php">Tableau de bord</a>
                <a href="agriculteurs.php">Agriculteurs</a>
                <a href="cultures.php">Cultures</a>
                <a href="cooperatives.php">Coopératives</a>
                <a href="zones.php">Zones</a>
                <a href="intrants.php">Intrants</a>
                <a href="saisons.php">Saisons</a>
                <a href="../auth/logout.php">Déconnexion</a>
            </nav>
        </header>

        <main>
            <div class="dashboard-header">
                <h2>Tableau de bord</h2>
                <p>Bienvenue, <?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></p>
            </div>

            <!-- ========== STATISTIQUES PRINCIPALES ========== -->
            <section class="statistics">
                <h3>Aperçu des données</h3>
                
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="label">Agriculteurs</div>
                        <div class="number"><?php echo $stats['agriculteurs']; ?></div>
                        <a href="agriculteurs.php" class="btn btn-small">Gérer</a>
                    </div>

                    <div class="stat-card">
                        <div class="label">Coopératives</div>
                        <div class="number"><?php echo $stats['cooperatives']; ?></div>
                        <a href="cooperatives.php" class="btn btn-small">Gérer</a>
                    </div>

                    <div class="stat-card">
                        <div class="label">Cultures</div>
                        <div class="number"><?php echo $stats['cultures']; ?></div>
                        <a href="cultures.php" class="btn btn-small">Gérer</a>
                    </div>

                    <div class="stat-card">
                        <div class="label">Parcelles</div>
                        <div class="number"><?php echo $stats['parcelles']; ?></div>
                        <a href="#" class="btn btn-small">Voir</a>
                    </div>

                    <div class="stat-card">
                        <div class="label">Plantations</div>
                        <div class="number"><?php echo $stats['plantations']; ?></div>
                        <a href="#" class="btn btn-small">Voir</a>
                    </div>

                    <div class="stat-card">
                        <div class="label">Récoltes</div>
                        <div class="number"><?php echo $stats['recoltes']; ?></div>
                        <a href="#" class="btn btn-small">Voir</a>
                    </div>

                    <div class="stat-card">
                        <div class="label">Zones agroéco.</div>
                        <div class="number"><?php echo $stats['zones']; ?></div>
                        <a href="zones.php" class="btn btn-small">Gérer</a>
                    </div>

                    <div class="stat-card">
                        <div class="label">Intrants</div>
                        <div class="number"><?php echo $stats['intrants']; ?></div>
                        <a href="intrants.php" class="btn btn-small">Gérer</a>
                    </div>

                    <div class="stat-card">
                        <div class="label">Saisons</div>
                        <div class="number"><?php echo $stats['saisons']; ?></div>
                        <a href="saisons.php" class="btn btn-small">Gérer</a>
                    </div>
                </div>
            </section>

            <!-- ========== ACCES RAPIDES ========== -->
            <section class="management-section">
                <h3>Gestion rapide</h3>
                
                <div class="quick-links">
                    <a href="agriculteurs.php?action=create" class="quick-link">
                        <div class="icon">👨‍🌾</div>
                        <div class="text">Ajouter un agriculteur</div>
                    </a>

                    <a href="cooperatives.php?action=create" class="quick-link">
                        <div class="icon">🤝</div>
                        <div class="text">Ajouter une coopérative</div>
                    </a>

                    <a href="cultures.php?action=create" class="quick-link">
                        <div class="icon">🌱</div>
                        <div class="text">Ajouter une culture</div>
                    </a>

                    <a href="zones.php?action=create" class="quick-link">
                        <div class="icon">🗺️</div>
                        <div class="text">Ajouter une zone</div>
                    </a>

                    <a href="intrants.php?action=create" class="quick-link">
                        <div class="icon">🧪</div>
                        <div class="text">Ajouter un intrant</div>
                    </a>

                    <a href="saisons.php?action=create" class="quick-link">
                        <div class="icon">🌦️</div>
                        <div class="text">Ajouter une saison</div>
                    </a>
                </div>
            </section>

            <!-- ========== INFORMATIONS SYSTEME ========== -->
            <section class="management-section">
                <h3>Informations</h3>
                
                <div class="info-box">
                    <p><strong>Base de données :</strong> agrigest_togo</p>
                    <p><strong>Version :</strong> 1.0</p>
                    <p><strong>Connecté en tant que :</strong> Administrateur (<?php echo htmlspecialchars($_SESSION['email']); ?>)</p>
                    <p><strong>Date actuelle :</strong> <?php echo strftime('%d %B %Y à %H:%M:%S', time()); ?></p>
                </div>
            </section>

        </main>

        <footer>
            <p>&copy; 2024 AgriGest Togo - Gestion des Exploitations Agricoles</p>
        </footer>
    </div>
</body>
</html>