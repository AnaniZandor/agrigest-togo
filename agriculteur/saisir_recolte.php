<?php
/**
 * agriculteur/saisir_recolte.php
 * Enregistrement d'une nouvelle récolte
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('agriculteur');

$id_agri = $_SESSION['id_utilisateur'];
$plantations = [];
$message = '';
$erreur = '';

try {
    // Récupérer les plantations de l'agriculteur
    $stmt = $pdo->prepare(
        "SELECT DISTINCT pl.id_plantation, pl.date_semis,
                c.nom_culture, p.nom_parcelle, s.libelle_saison
         FROM PLANTATION pl
         JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
         JOIN CULTURE c ON pl.id_culture = c.id_culture
         JOIN SAISON s ON pl.id_saison = s.id_saison
         WHERE p.id_agri = ?
         ORDER BY pl.date_semis DESC"
    );
    $stmt->execute([$id_agri]);
    $plantations = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log('Erreur SQL saisir_recolte: ' . $e->getMessage());
    $erreur = 'Erreur lors du chargement des données.';
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) {
        $erreur = 'Token CSRF invalide.';
    } else {
        $id_plantation = nettoyer($_POST['id_plantation'] ?? '');
        $date_recolte = nettoyer($_POST['date_recolte'] ?? '');
        $rendement = nettoyer($_POST['rendement'] ?? '');
        
        // Validations
        if (empty($id_plantation)) {
            $erreur = 'Sélectionnez une plantation.';
        } elseif (empty($date_recolte)) {
            $erreur = 'Saisissez la date de récolte.';
        } elseif (empty($rendement) || !is_numeric($rendement) || $rendement <= 0) {
            $erreur = 'Le rendement doit être un nombre positif.';
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
                    // Générer l'ID récolte
                    $id_recolte = genererCodeSimple($pdo, 'RECOLTE', 'REC');
                    
                    // Insérer la récolte
                    $stmt = $pdo->prepare(
                        "INSERT INTO RECOLTE (id_recolte, date_recolte, rendement, id_plantation)
                         VALUES (?, ?, ?, ?)"
                    );
                    $stmt->execute([$id_recolte, $date_recolte, $rendement, $id_plantation]);
                    
                    $message = 'Récolte enregistrée avec succès ! (ID: ' . $id_recolte . ')';
                    // Réinitialiser le formulaire
                    $_POST = [];
                }
            } catch (PDOException $e) {
                error_log('Erreur insertion récolte: ' . $e->getMessage());
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
    <title>Saisir récolte - AgriGest Togo</title>
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
                <h2>Enregistrement de récolte</h2>
                <p>Saisissez les résultats de récolte d'une de vos plantations</p>
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
                                            <?php echo htmlspecialchars($plt['nom_culture'] . ' - ' . $plt['nom_parcelle'] . ' (' . $plt['libelle_saison'] . ')'); ?>
                                            (Semis: <?php echo formaterDate($plt['date_semis']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>

                        <!-- Date récolte -->
                        <div class="form-group">
                            <label for="date_recolte">Date de récolte *</label>
                            <input type="date" name="date_recolte" id="date_recolte" required
                                   value="<?php echo htmlspecialchars($_POST['date_recolte'] ?? date('Y-m-d')); ?>">
                        </div>

                        <!-- Rendement -->
                        <div class="form-group">
                            <label for="rendement">Rendement (kg ou unité) *</label>
                            <input type="number" name="rendement" id="rendement" 
                                   step="0.01" min="0" required
                                   value="<?php echo htmlspecialchars($_POST['rendement'] ?? ''); ?>"
                                   placeholder="Ex: 150.5">
                            <p class="form-help">Exprimé en kg ou selon l'unité de votre culture</p>
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