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
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM AGRICULTEUR");
    $stats['agriculteurs'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM COOPERATIVE");
    $stats['cooperatives'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM CULTURE");
    $stats['cultures'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM PARCELLE");
    $stats['parcelles'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM PLANTATION");
    $stats['plantations'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM RECOLTE");
    $stats['recoltes'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM ZONE_AGROECOLOGIQUE");
    $stats['zones'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM INTRANT");
    $stats['intrants'] = $stmt->fetch()['total'];
    
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
<h1><i class="fas fa-seedling" style="color: var(--color-primary);"></i> AgriGest Togo - Admin</h1>
                <button class="hamburger" id="hamburgerBtn" aria-label="Menu principal" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
               <nav id="navMenu">
    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
    <a href="agriculteurs.php"><i class="fas fa-user-tie"></i> Agriculteurs</a>
    <a href="cultures.php"><i class="fas fa-sprout"></i> Cultures</a>
    <a href="cooperatives.php"><i class="fas fa-handshake"></i> Coopératives</a>
    <a href="zones.php"><i class="fas fa-map"></i> Zones</a>
    <a href="intrants.php"><i class="fas fa-flask"></i> Intrants</a>
    <a href="saisons.php"><i class="fas fa-cloud-sun"></i> Saisons</a>
        <a href="../profil.php"><i class="fas fa-user-circle"></i> Mon profil</a>

    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
</nav>
            </div>
        </header>

        <main>
            <div class="dashboard-header">
                <h2>Tableau de bord</h2>
                <p>Bienvenue, <?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></p>
            </div>

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

            <section class="management-section">
    <h3>Gestion rapide</h3>
    
    <div class="quick-links">
        <a href="agriculteurs.php?action=create" class="quick-link">
            <div class="icon"><i class="fas fa-user-plus"></i></div>
            <div class="text">Ajouter un agriculteur</div>
        </a>

        <a href="cooperatives.php?action=create" class="quick-link">
            <div class="icon"><i class="fas fa-handshake"></i></div>
            <div class="text">Ajouter une coopérative</div>
        </a>

        <a href="cultures.php?action=create" class="quick-link">
            <div class="icon"><i class="fas fa-sprout"></i></div>
            <div class="text">Ajouter une culture</div>
        </a>

        <a href="zones.php?action=create" class="quick-link">
            <div class="icon"><i class="fas fa-map"></i></div>
            <div class="text">Ajouter une zone</div>
        </a>

        <a href="intrants.php?action=create" class="quick-link">
            <div class="icon"><i class="fas fa-flask"></i></div>
            <div class="text">Ajouter un intrant</div>
        </a>

        <a href="saisons.php?action=create" class="quick-link">
            <div class="icon"><i class="fas fa-cloud-sun"></i></div>
            <div class="text">Ajouter une saison</div>
        </a>
    </div>
</section>

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