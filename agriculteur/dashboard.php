<?php
/**
 * agriculteur/dashboard.php
 * Tableau de bord agriculteur
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('agriculteur');

$id_agri = $_SESSION['id_utilisateur'];
$stats = [];

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM PARCELLE WHERE id_agri = ?");
    $stmt->execute([$id_agri]);
    $stats['parcelles'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT SUM(superficie) AS total FROM PARCELLE WHERE id_agri = ?");
    $stmt->execute([$id_agri]);
    $result = $stmt->fetch();
    $stats['superficie'] = $result['total'] ?? 0;
    
    $stmt = $pdo->prepare(
        "SELECT COUNT(pl.id_plantation) AS total 
         FROM PLANTATION pl
         JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
         WHERE p.id_agri = ?"
    );
    $stmt->execute([$id_agri]);
    $stats['plantations'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare(
        "SELECT COUNT(r.id_recolte) AS total 
         FROM RECOLTE r
         JOIN PLANTATION pl ON r.id_plantation = pl.id_plantation
         JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
         WHERE p.id_agri = ?"
    );
    $stmt->execute([$id_agri]);
    $stats['recoltes'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT z.id_zone) AS total 
         FROM ZONE_AGROECOLOGIQUE z
         JOIN PARCELLE p ON z.id_zone = p.id_zone
         WHERE p.id_agri = ?"
    );
    $stmt->execute([$id_agri]);
    $stats['zones'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT c.id_culture) AS total 
         FROM CULTURE c
         JOIN PLANTATION pl ON c.id_culture = pl.id_culture
         JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
         WHERE p.id_agri = ?"
    );
    $stmt->execute([$id_agri]);
    $stats['cultures'] = $stmt->fetch()['total'];
    
} catch (PDOException $e) {
    error_log('Erreur SQL dashboard agriculteur: ' . $e->getMessage());
}

initialiserCsrf();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Agriculteur - AgriGest Togo</title>
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
                <h1>🌾 AgriGest Togo - Agriculteur</h1>
                <button class="hamburger" id="hamburgerBtn" aria-label="Menu principal" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <nav id="navMenu">
                    <a href="dashboard.php">Tableau de bord</a>
                    <a href="mes_parcelles.php">Mes parcelles</a>
                    <a href="saisir_intrant.php">Saisir intrant</a>
                    <a href="saisir_plantation.php">Saisir plantation</a>
                    <a href="saisir_recolte.php">Saisir récolte</a>
                    <a href="../auth/logout.php">Déconnexion</a>
                </nav>
            </div>
        </header>

        <main>
            <div class="dashboard-header">
                <h2>Tableau de bord</h2>
                <p>Bienvenue, <?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></p>
            </div>

            <section class="statistics">
                <h3>Aperçu de vos exploitations</h3>
                
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="label">Parcelles</div>
                        <div class="number"><?php echo $stats['parcelles']; ?></div>
                        <a href="mes_parcelles.php" class="btn btn-small">Gérer</a>
                    </div>

                    <div class="stat-card">
                        <div class="label">Superficie (ha)</div>
                        <div class="number"><?php echo formaterNombre($stats['superficie'], 2); ?></div>
                        <a href="mes_parcelles.php" class="btn btn-small">Détail</a>
                    </div>

                    <div class="stat-card">
                        <div class="label">Plantations</div>
                        <div class="number"><?php echo $stats['plantations']; ?></div>
                        <a href="saisir_plantation.php" class="btn btn-small">Ajouter</a>
                    </div>

                    <div class="stat-card">
                        <div class="label">Récoltes</div>
                        <div class="number"><?php echo $stats['recoltes']; ?></div>
                        <a href="saisir_recolte.php" class="btn btn-small">Ajouter</a>
                    </div>

                    <div class="stat-card">
                        <div class="label">Zones</div>
                        <div class="number"><?php echo $stats['zones']; ?></div>
                        <a href="mes_parcelles.php" class="btn btn-small">Voir</a>
                    </div>

                    <div class="stat-card">
                        <div class="label">Cultures</div>
                        <div class="number"><?php echo $stats['cultures']; ?></div>
                        <a href="saisir_plantation.php" class="btn btn-small">Voir</a>
                    </div>
                </div>
            </section>

            <section class="management-section">
                <h3>Actions rapides</h3>
                
                <div class="quick-links">
                    <a href="mes_parcelles.php?action=create" class="quick-link">
                        <div class="icon">🌱</div>
                        <div class="text">Ajouter une parcelle</div>
                    </a>

                    <a href="saisir_plantation.php" class="quick-link">
                        <div class="icon">🌾</div>
                        <div class="text">Enregistrer plantation</div>
                    </a>

                    <a href="saisir_recolte.php" class="quick-link">
                        <div class="icon">🌻</div>
                        <div class="text">Enregistrer récolte</div>
                    </a>

                    <a href="saisir_intrant.php" class="quick-link">
                        <div class="icon">🧪</div>
                        <div class="text">Enregistrer intrant</div>
                    </a>

                    <a href="mes_parcelles.php" class="quick-link">
                        <div class="icon">📋</div>
                        <div class="text">Consulter mes parcelles</div>
                    </a>
                </div>
            </section>

            <section class="management-section">
                <h3>Informations</h3>
                
                <div class="info-box">
                    <p><strong>Compte :</strong> <?php echo htmlspecialchars($_SESSION['email']); ?></p>
                    <p><strong>Profil :</strong> Agriculteur</p>
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