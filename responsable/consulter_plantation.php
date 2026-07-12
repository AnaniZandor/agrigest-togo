<?php
/**
 * responsable/consulter_plantation.php
 * Consultation détaillée d'une plantation
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('responsable');

$id_plantation = $_GET['id'] ?? null;
$erreur = '';
$plantation = null;
$intrants = [];
$recoltes = [];

if (!$id_plantation) {
    header('Location: plantations.php');
    exit;
}

try {
    // Récupérer les informations de la plantation
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
         WHERE pl.id_plantation = ?"
    );
    $stmt->execute([$id_plantation]);
    $plantation = $stmt->fetch();

    if (!$plantation) {
        $erreur = 'Plantation non trouvée.';
    } else {
        // Récupérer les intrants utilisés
        $stmt = $pdo->prepare(
            "SELECT u.id_intrant, i.nom_intrant, i.type_intrant, i.unite_mesure,
                    u.quantite_utilisee, u.date_utilisation
             FROM UTILISER u
             JOIN INTRANT i ON u.id_intrant = i.id_intrant
             WHERE u.id_plantation = ?
             ORDER BY u.date_utilisation DESC"
        );
        $stmt->execute([$id_plantation]);
        $intrants = $stmt->fetchAll();

        // Récupérer les récoltes
        $stmt = $pdo->prepare(
            "SELECT id_recolte, date_recolte, rendement
             FROM RECOLTE
             WHERE id_plantation = ?
             ORDER BY date_recolte DESC"
        );
        $stmt->execute([$id_plantation]);
        $recoltes = $stmt->fetchAll();
    }

} catch (PDOException $e) {
    error_log('Erreur SQL consulter_plantation: ' . $e->getMessage());
    $erreur = 'Erreur lors du chargement des données.';
}

initialiserCsrf();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulter Plantation - AgriGest Togo</title>
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
                <h2>Détails de la plantation</h2>
                <p><a href="plantations.php">&larr; Retour à la liste</a></p>
            </div>

            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erreur); ?></div>
            <?php elseif ($plantation): ?>

            <!-- ===== INFORMATIONS DE LA PLANTATION ===== -->
            <div class="card" style="margin-bottom: 30px;">
                <h3>Informations de la plantation</h3>
                <div class="info-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:var(--spacing-md);">
                    <p><strong>ID :</strong> <?php echo htmlspecialchars($plantation['id_plantation']); ?></p>
                    <p><strong>Culture :</strong> <?php echo htmlspecialchars($plantation['nom_culture']); ?></p>
                    <p><strong>Parcelle :</strong> <?php echo htmlspecialchars($plantation['nom_parcelle']); ?></p>
                    <p><strong>Zone :</strong> <?php echo htmlspecialchars($plantation['nom_zone']); ?></p>
                    <p><strong>Saison :</strong> <?php echo htmlspecialchars($plantation['libelle_saison']); ?></p>
                    <p><strong>Date de semis :</strong> <?php echo formaterDate($plantation['date_semis']); ?></p>
                    <p><strong>Agriculteur :</strong> <?php echo htmlspecialchars($plantation['prenom'] . ' ' . $plantation['nom']); ?></p>
                </div>
            </div>

            <!-- ===== INTRANTS UTILISÉS ===== -->
            <div class="card" style="margin-bottom: 30px;">
                <h3>Intrants utilisés</h3>
                
                <?php if (empty($intrants)): ?>
                    <p class="text-muted">Aucun intrant enregistré pour cette plantation.</p>
                <?php else: ?>
                    <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Intrant</th>
                                <th>Type</th>
                                <th>Unité</th>
                                <th>Quantité</th>
                                <th>Date d'utilisation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($intrants as $intrant): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($intrant['nom_intrant']); ?></td>
                                    <td><?php echo htmlspecialchars($intrant['type_intrant']); ?></td>
                                    <td><?php echo htmlspecialchars($intrant['unite_mesure']); ?></td>
                                    <td><?php echo formaterNombre($intrant['quantite_utilisee'], 2); ?></td>
                                    <td><?php echo formaterDate($intrant['date_utilisation']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ===== RÉCOLTES ===== -->
            <div class="card">
                <h3>Récoltes</h3>
                
                <?php if (empty($recoltes)): ?>
                    <p class="text-muted">Aucune récolte enregistrée pour cette plantation.</p>
                <?php else: ?>
                    <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date de récolte</th>
                                <th>Rendement (kg)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recoltes as $recolte): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($recolte['id_recolte']); ?></td>
                                    <td><?php echo formaterDate($recolte['date_recolte']); ?></td>
                                    <td><?php echo formaterNombre($recolte['rendement'], 2); ?></td>
                                    <td>
                                        <a href="consulter_recolte.php?id=<?php echo urlencode($recolte['id_recolte']); ?>" class="btn btn-secondary btn-sm">Voir</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
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