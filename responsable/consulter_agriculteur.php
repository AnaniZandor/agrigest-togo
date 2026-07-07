<?php
/**
 * responsable/consulter_agriculteur.php
 * Consultation détaillée d'un agriculteur
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('responsable');

$id_agri = $_GET['id'] ?? null;
$erreur = '';
$agriculteur = null;
$parcelles = [];
$statistiques = [];

if (!$id_agri) {
    header('Location: agriculteurs.php');
    exit;
}

try {
    // Récupérer les informations de l'agriculteur
    $stmt = $pdo->prepare(
        "SELECT a.id_agri, a.contact_agri, u.nom, u.prenom, u.email, c.nom_coop, a.id_coop
         FROM AGRICULTEUR a
         JOIN UTILISATEUR u ON a.id_agri = u.id_utilisateur
         JOIN COOPERATIVE c ON a.id_coop = c.id_coop
         WHERE a.id_agri = ?"
    );
    $stmt->execute([$id_agri]);
    $agriculteur = $stmt->fetch();

    if (!$agriculteur) {
        $erreur = 'Agriculteur non trouvé.';
    } else {
        // Récupérer les parcelles de l'agriculteur
        $stmt = $pdo->prepare(
            "SELECT p.id_parcelle, p.nom_parcelle, p.localisation_parcelle, p.superficie, z.nom_zone
             FROM PARCELLE p
             JOIN ZONE_AGROECOLOGIQUE z ON p.id_zone = z.id_zone
             WHERE p.id_agri = ?
             ORDER BY p.nom_parcelle"
        );
        $stmt->execute([$id_agri]);
        $parcelles = $stmt->fetchAll();

        // Statistiques
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS total_parcelles, SUM(superficie) AS superficie_totale
             FROM PARCELLE WHERE id_agri = ?"
        );
        $stmt->execute([$id_agri]);
        $statistiques = $stmt->fetch();

        // Nombre de plantations
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS total_plantations FROM PLANTATION pl
             JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
             WHERE p.id_agri = ?"
        );
        $stmt->execute([$id_agri]);
        $plantations = $stmt->fetch();
        $statistiques['total_plantations'] = $plantations['total_plantations'] ?? 0;

        // Nombre de récoltes
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS total_recoltes FROM RECOLTE r
             JOIN PLANTATION pl ON r.id_plantation = pl.id_plantation
             JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
             WHERE p.id_agri = ?"
        );
        $stmt->execute([$id_agri]);
        $recoltes = $stmt->fetch();
        $statistiques['total_recoltes'] = $recoltes['total_recoltes'] ?? 0;
    }

} catch (PDOException $e) {
    error_log('Erreur SQL consulter_agriculteur: ' . $e->getMessage());
    $erreur = 'Erreur lors du chargement des données.';
}

initialiserCsrf();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulter Agriculteur - AgriGest Togo</title>
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
                <h2>Détails de l'agriculteur</h2>
                <p><a href="agriculteurs.php">&larr; Retour à la liste</a></p>
            </div>

            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erreur); ?></div>
            <?php elseif ($agriculteur): ?>

            <!-- ===== INFORMATIONS DE L'AGRICULTEUR ===== -->
            <div class="card" style="margin-bottom: 30px;">
                <h3>Informations personnelles</h3>
                <div class="info-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:var(--spacing-md);">
                    <p><strong>ID :</strong> <?php echo htmlspecialchars($agriculteur['id_agri']); ?></p>
                    <p><strong>Nom :</strong> <?php echo htmlspecialchars($agriculteur['nom']); ?></p>
                    <p><strong>Prénom :</strong> <?php echo htmlspecialchars($agriculteur['prenom']); ?></p>
                    <p><strong>Email :</strong> <?php echo htmlspecialchars($agriculteur['email']); ?></p>
                    <p><strong>Contact :</strong> <?php echo htmlspecialchars($agriculteur['contact_agri']); ?></p>
                    <p><strong>Coopérative :</strong> <?php echo htmlspecialchars($agriculteur['nom_coop']); ?></p>
                </div>
            </div>

            <!-- ===== STATISTIQUES ===== -->
            <div class="card" style="margin-bottom: 30px;">
                <h3>Statistiques</h3>
                <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
                    <div class="stat-card">
                        <div class="label">Parcelles</div>
                        <div class="number"><?php echo $statistiques['total_parcelles'] ?? 0; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="label">Superficie totale (ha)</div>
                        <div class="number"><?php echo formaterNombre($statistiques['superficie_totale'] ?? 0, 2); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="label">Plantations</div>
                        <div class="number"><?php echo $statistiques['total_plantations'] ?? 0; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="label">Récoltes</div>
                        <div class="number"><?php echo $statistiques['total_recoltes'] ?? 0; ?></div>
                    </div>
                </div>
            </div>

            <!-- ===== LISTE DES PARCELLES ===== -->
            <div class="card">
                <h3>Parcelles de l'agriculteur</h3>
                
                <?php if (empty($parcelles)): ?>
                    <p class="text-muted">Aucune parcelle enregistrée pour cet agriculteur.</p>
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
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <?php endif; ?>

        </main>

        <footer>
            <p>&copy; 2024 AgriGest Togo - Gestion des Exploitations Agricoles</p>
        </footer>
    </div>


            <script src="../assets/js/script.js"></script>

</body>
</html>