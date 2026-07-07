<?php
/**
 * responsable/supprimer_parcelle.php
 * Suppression d'une parcelle
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('responsable');

$id_parcelle = $_GET['id'] ?? null;
$erreur = '';
$message = '';
$parcelle = null;

if (!$id_parcelle) {
    header('Location: parcelles.php');
    exit;
}

try {
    // Récupérer les informations de la parcelle pour confirmation
    $stmt = $pdo->prepare(
        "SELECT p.id_parcelle, p.nom_parcelle, u.nom, u.prenom
         FROM PARCELLE p
         JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
         JOIN UTILISATEUR u ON a.id_agri = u.id_utilisateur
         WHERE p.id_parcelle = ?"
    );
    $stmt->execute([$id_parcelle]);
    $parcelle = $stmt->fetch();

    if (!$parcelle) {
        $erreur = 'Parcelle non trouvée.';
    }

} catch (PDOException $e) {
    error_log('Erreur SQL supprimer_parcelle: ' . $e->getMessage());
    $erreur = 'Erreur lors du chargement des données.';
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer'])) {
    if (!verifierCsrf()) {
        $erreur = 'Token CSRF invalide.';
    } else {
        try {
            // Vérifier si la parcelle a des plantations
            $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM PLANTATION WHERE id_parcelle = ?");
            $stmt->execute([$id_parcelle]);
            $result = $stmt->fetch();
            
            if ($result['total'] > 0) {
                $erreur = 'Cette parcelle contient des plantations. Supprimez d\'abord les plantations associées.';
            } else {
                // Supprimer la parcelle
                $stmt = $pdo->prepare("DELETE FROM PARCELLE WHERE id_parcelle = ?");
                $stmt->execute([$id_parcelle]);
                
                $message = 'Parcelle supprimée avec succès !';
                header('Location: parcelles.php?message=' . urlencode($message));
                exit;
            }
        } catch (PDOException $e) {
            error_log('Erreur suppression parcelle: ' . $e->getMessage());
            $erreur = 'Erreur lors de la suppression.';
        }
    }
}

initialiserCsrf();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer Parcelle - AgriGest Togo</title>
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
                <h2>Supprimer une parcelle</h2>
                <p><a href="parcelles.php">&larr; Retour à la liste</a></p>
            </div>

            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erreur); ?></div>
            <?php endif; ?>

            <?php if ($parcelle): ?>

            <div class="card" style="border-color: var(--color-danger); border-width: 2px;">
                <div style="text-align: center; padding: var(--spacing-lg);">
                    <i class="fas fa-exclamation-triangle" style="font-size: 4rem; color: var(--color-danger);"></i>
                    <h3 style="margin-top: var(--spacing-md);">Confirmer la suppression</h3>
                    <p>Êtes-vous sûr de vouloir supprimer la parcelle suivante ?</p>
                    
                    <div class="info-box" style="text-align: left; margin: var(--spacing-lg) 0;">
                        <p><strong>ID :</strong> <?php echo htmlspecialchars($parcelle['id_parcelle']); ?></p>
                        <p><strong>Nom :</strong> <?php echo htmlspecialchars($parcelle['nom_parcelle']); ?></p>
                        <p><strong>Propriétaire :</strong> <?php echo htmlspecialchars($parcelle['prenom'] . ' ' . $parcelle['nom']); ?></p>
                    </div>

                    <div style="display: flex; gap: var(--spacing-md); justify-content: center; flex-wrap: wrap;">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="id_parcelle" value="<?php echo htmlspecialchars($parcelle['id_parcelle']); ?>">
                            <button type="submit" name="confirmer" class="btn btn-danger" style="min-width: 150px;">
                                <i class="fas fa-trash"></i> Confirmer la suppression
                            </button>
                        </form>
                        <a href="parcelles.php" class="btn btn-secondary" style="min-width: 150px;">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                    </div>
                </div>
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