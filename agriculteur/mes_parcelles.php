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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🌾 AgriGest Togo - Agriculteur</h1>
            <nav>
                <a href="dashboard.php">Tableau de bord</a>
                <a href="mes_parcelles.php">Mes parcelles</a>
                <a href="saisir_intrant.php">Saisir intrant</a>
                <a href="saisir_plantation.php">Saisir plantation</a>
                <a href="saisir_recolte.php">Saisir récolte</a>
                <a href="../auth/logout.php">Déconnexion</a>
            </nav>
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
                    <a href="mes_parcelles.php?action=create" class="btn">+ Ajouter une parcelle</a>
                </div>

                <?php if (empty($parcelles)): ?>
                    <div class="empty-state">
                        <p>Vous n'avez pas encore de parcelle enregistrée.</p>
                        <a href="mes_parcelles.php?action=create" class="btn">Créer votre première parcelle</a>
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
                                        <a href="consulter_parcelle.php?id=<?php echo urlencode($parcelle['id_parcelle']); ?>" class="btn btn-small">Voir</a>
                                        <a href="modifier_parcelle.php?id=<?php echo urlencode($parcelle['id_parcelle']); ?>" class="btn btn-small">Modifier</a>
                                        <a href="supprimer_parcelle.php?id=<?php echo urlencode($parcelle['id_parcelle']); ?>" class="btn btn-small btn-danger" onclick="return confirm('Êtes-vous sûr ?')">Supprimer</a>
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
</body>
</html>