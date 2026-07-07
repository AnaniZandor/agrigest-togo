<?php
/**
 * agriculteur/saisir_intrant.php
 * Enregistrement d'utilisation d'intrants
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('agriculteur');

$id_agri = $_SESSION['id_utilisateur'];
$plantations = [];
$intrants = [];
$message = '';
$erreur = '';

try {
    // Récupérer les plantations de l'agriculteur
    $stmt = $pdo->prepare(
        "SELECT DISTINCT pl.id_plantation, pl.date_semis,
                c.nom_culture, p.nom_parcelle
         FROM PLANTATION pl
         JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
         JOIN CULTURE c ON pl.id_culture = c.id_culture
         WHERE p.id_agri = ?
         ORDER BY pl.date_semis DESC"
    );
    $stmt->execute([$id_agri]);
    $plantations = $stmt->fetchAll();
    
    // Récupérer tous les intrants disponibles
    $stmt = $pdo->query(
        "SELECT id_intrant, nom_intrant, type_intrant, unite_mesure
         FROM INTRANT
         ORDER BY nom_intrant ASC"
    );
    $intrants = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log('Erreur SQL saisir_intrant: ' . $e->getMessage());
    $erreur = 'Erreur lors du chargement des données.';
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) {
        $erreur = 'Token CSRF invalide.';
    } else {
        $id_plantation = nettoyer($_POST['id_plantation'] ?? '');
        $id_intrant = nettoyer($_POST['id_intrant'] ?? '');
        $quantite_utilisee = nettoyer($_POST['quantite_utilisee'] ?? '');
        $date_utilisation = nettoyer($_POST['date_utilisation'] ?? '');
        
        // Validations
        if (empty($id_plantation)) {
            $erreur = 'Sélectionnez une plantation.';
        } elseif (empty($id_intrant)) {
            $erreur = 'Sélectionnez un intrant.';
        } elseif (empty($quantite_utilisee) || !is_numeric($quantite_utilisee) || $quantite_utilisee <= 0) {
            $erreur = 'La quantité doit être un nombre positif.';
        } elseif (empty($date_utilisation)) {
            $erreur = 'Saisissez la date d\'utilisation.';
        } else {
            try {
                // Vérifier que la plantation appartient à l'agriculteur
                $stmt = $pdo->prepare(
                    "SELECT pl.id_plantation FROM PLANTATION pl
                     JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
                     WHERE pl.id_plantation = ? AND p.id_agri = ?"
                );
                $stmt->execute([$id_plantation, $id_agri]);
                
                if (!$stmt->fetch()) {
                    $erreur = 'Plantation invalide.';
                } else {
                    // Insérer l'utilisation d'intrant
                    $stmt = $pdo->prepare(
                        "INSERT INTO UTILISER (id_plantation, id_intrant, quantite_utilisee, date_utilisation)
                         VALUES (?, ?, ?, ?)"
                    );
                    $stmt->execute([$id_plantation, $id_intrant, $quantite_utilisee, $date_utilisation]);
                    
                    $message = 'Intrant enregistré avec succès !';
                    // Réinitialiser le formulaire
                    $_POST = [];
                }
            } catch (PDOException $e) {
                error_log('Erreur insertion intrant: ' . $e->getMessage());
                $erreur = 'Erreur lors de l\'enregistrement.';
            }
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
    <title>Saisir intrant - AgriGest Togo</title>
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
                <h2>Enregistrement d'intrant</h2>
                <p>Renseignez l'utilisation d'un intrant sur une de vos plantations</p>
            </div>

            <!-- ========== MESSAGES ========== -->
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($erreur): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($erreur); ?>
                </div>
            <?php endif; ?>

            <!-- ========== FORMULAIRE ========== -->
            <section class="management-section">
                <div class="form-container">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        
                        <!-- Sélection plantation -->
                        <div class="form-group">
                            <label for="id_plantation">Plantation *</label>
                            <?php if (empty($plantations)): ?>
                                <p class="form-help">Aucune plantation disponible. <a href="saisir_plantation.php">Créer une plantation</a></p>
                            <?php else: ?>
                                <select name="id_plantation" id="id_plantation" required>
                                    <option value="">-- Sélectionner une plantation --</option>
                                    <?php foreach ($plantations as $plt): ?>
                                        <option value="<?php echo htmlspecialchars($plt['id_plantation']); ?>"
                                            <?php echo (isset($_POST['id_plantation']) && $_POST['id_plantation'] === $plt['id_plantation']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($plt['nom_culture'] . ' - ' . $plt['nom_parcelle']); ?>
                                            (<?php echo formaterDate($plt['date_semis']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>

                        <!-- Sélection intrant -->
                        <div class="form-group">
                            <label for="id_intrant">Intrant *</label>
                            <select name="id_intrant" id="id_intrant" required>
                                <option value="">-- Sélectionner un intrant --</option>
                                <?php foreach ($intrants as $intrant): ?>
                                    <option value="<?php echo htmlspecialchars($intrant['id_intrant']); ?>"
                                        <?php echo (isset($_POST['id_intrant']) && $_POST['id_intrant'] === $intrant['id_intrant']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($intrant['nom_intrant'] . ' (' . $intrant['type_intrant'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Quantité utilisée -->
                        <div class="form-group">
                            <label for="quantite_utilisee">Quantité utilisée *</label>
                            <input type="number" name="quantite_utilisee" id="quantite_utilisee" 
                                   step="0.01" min="0" required
                                   value="<?php echo htmlspecialchars($_POST['quantite_utilisee'] ?? ''); ?>"
                                   placeholder="Ex: 10.5">
                        </div>

                        <!-- Date utilisation -->
                        <div class="form-group">
                            <label for="date_utilisation">Date d'utilisation *</label>
                            <input type="date" name="date_utilisation" id="date_utilisation" required
                                   value="<?php echo htmlspecialchars($_POST['date_utilisation'] ?? date('Y-m-d')); ?>">
                        </div>

                        <!-- Boutons -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                            <a href="dashboard.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
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