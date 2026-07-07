<?php
/**
 * agriculteur/mes_parcelles.php
 * Gestion des parcelles de l'agriculteur
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('agriculteur');

$id_agri = $_SESSION['id_utilisateur'];
$parcelles = [];

try {
    $stmt = $pdo->prepare(
        "SELECT p.id_parcelle, p.nom_parcelle, p.localisation_parcelle, p.superficie,
                z.id_zone, z.nom_zone
         FROM PARCELLE p
         JOIN ZONE_AGROECOLOGIQUE z ON p.id_zone = z.id_zone
         WHERE p.id_agri = ?
         ORDER BY p.nom_parcelle ASC"
    );
    $stmt->execute([$id_agri]);
    $parcelles = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log('Erreur SQL mes_parcelles: ' . $e->getMessage());
}

initialiserCsrf();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes parcelles - AgriGest Togo</title>
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
<h1><i class="fas fa-seedling" style="color: var(--color-primary);"></i> AgriGest Togo - Agriculteur</h1>
                <button class="hamburger" id="hamburgerBtn" aria-label="Menu principal" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <nav id="navMenu">
    <a href="dashboard.php"><i class="fas fa-home"></i> Tableau de bord</a>
    <a href="mes_parcelles.php"><i class="fas fa-map-marked-alt"></i> Mes parcelles</a>
    <a href="saisir_intrant.php"><i class="fas fa-vial"></i> Saisir intrant</a>
    <a href="saisir_plantation.php"><i class="fas fa-sprout"></i> Saisir plantation</a>
    <a href="saisir_recolte.php"><i class="fas fa-apple-alt"></i> Saisir récolte</a>
    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
</nav>
            </div>
        </header>

        <main>
            <div class="dashboard-header">
                <h2>Mes parcelles</h2>
                <p>Gestion de vos parcelles agricoles</p>
            </div>

            <!-- ========== BOUTON AJOUTER ========== -->
            <section class="management-section">
                <div class="section-header">
                    <h3>Liste des parcelles</h3>
                    <a href="mes_parcelles.php?action=create" class="btn btn-primary">+ Ajouter une parcelle</a>
                </div>

                <?php if (empty($parcelles)): ?>
                    <div class="empty-state">
                        <p>Vous n'avez pas encore de parcelle enregistrée.</p>
                        <a href="mes_parcelles.php?action=create" class="btn btn-primary">Créer votre première parcelle</a>
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
                                    <td>
                                        <a href="consulter_parcelle.php?id=<?php echo urlencode($parcelle['id_parcelle']); ?>" class="btn btn-secondary btn-sm">Voir</a>
                                        <a href="modifier_parcelle.php?id=<?php echo urlencode($parcelle['id_parcelle']); ?>" class="btn btn-secondary btn-sm">Modifier</a>
                                        <a href="supprimer_parcelle.php?id=<?php echo urlencode($parcelle['id_parcelle']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr ?')">Supprimer</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
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