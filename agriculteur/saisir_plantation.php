<?php
/**
 * agriculteur/saisir_plantation.php
 * Enregistrement d'une nouvelle plantation
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('agriculteur');

$id_agri = $_SESSION['id_utilisateur'];
$parcelles = [];
$cultures = [];
$saisons = [];
$message = '';
$erreur = '';

try {
    // Récupérer les parcelles de l'agriculteur
    $stmt = $pdo->prepare(
        "SELECT id_parcelle, nom_parcelle, localisation_parcelle, superficie
         FROM PARCELLE
         WHERE id_agri = ?
         ORDER BY nom_parcelle ASC"
    );
    $stmt->execute([$id_agri]);
    $parcelles = $stmt->fetchAll();
    
    // Récupérer toutes les cultures
    $stmt = $pdo->query(
        "SELECT id_culture, nom_culture, duree_cycle
         FROM CULTURE
         ORDER BY nom_culture ASC"
    );
    $cultures = $stmt->fetchAll();
    
    // Récupérer toutes les saisons
    $stmt = $pdo->query(
        "SELECT id_saison, libelle_saison, date_debut_saison, date_fin_saison
         FROM SAISON
         ORDER BY date_debut_saison DESC"
    );
    $saisons = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log('Erreur SQL saisir_plantation: ' . $e->getMessage());
    $erreur = 'Erreur lors du chargement des données.';
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) {
        $erreur = 'Token CSRF invalide.';
    } else {
        $id_parcelle = nettoyer($_POST['id_parcelle'] ?? '');
        $id_culture = nettoyer($_POST['id_culture'] ?? '');
        $id_saison = nettoyer($_POST['id_saison'] ?? '');
        $date_semis = nettoyer($_POST['date_semis'] ?? '');
        
        // Validations
        if (empty($id_parcelle)) {
            $erreur = 'Sélectionnez une parcelle.';
        } elseif (empty($id_culture)) {
            $erreur = 'Sélectionnez une culture.';
        } elseif (empty($id_saison)) {
            $erreur = 'Sélectionnez une saison.';
        } elseif (empty($date_semis)) {
            $erreur = 'Saisissez la date de semis.';
        } else {
            try {
                // Vérifier que la parcelle appartient à l'agriculteur
                $stmt = $pdo->prepare(
                    "SELECT id_parcelle FROM PARCELLE WHERE id_parcelle = ? AND id_agri = ?"
                );
                $stmt->execute([$id_parcelle, $id_agri]);
                
                if (!$stmt->fetch()) {
                    $erreur = 'Parcelle invalide.';
                } else {
                    // Générer l'ID plantation
                    $id_plantation = genererCodeSimple($pdo, 'PLANTATION', 'PLT');
                    
                    // Insérer la plantation
                    $stmt = $pdo->prepare(
                        "INSERT INTO PLANTATION (id_plantation, date_semis, id_parcelle, id_culture, id_saison)
                         VALUES (?, ?, ?, ?, ?)"
                    );
                    $stmt->execute([$id_plantation, $date_semis, $id_parcelle, $id_culture, $id_saison]);
                    
                    $message = 'Plantation enregistrée avec succès ! (ID: ' . $id_plantation . ')';
                    // Réinitialiser le formulaire
                    $_POST = [];
                }
            } catch (PDOException $e) {
                error_log('Erreur insertion plantation: ' . $e->getMessage());
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
    <title>Saisir plantation - AgriGest Togo</title>
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
                <h2>Enregistrement de plantation</h2>
                <p>Créez une nouvelle plantation sur l'une de vos parcelles</p>
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
                        
                        <!-- Sélection parcelle -->
                        <div class="form-group">
                            <label for="id_parcelle">Parcelle *</label>
                            <?php if (empty($parcelles)): ?>
                                <p class="form-help">Aucune parcelle disponible. <a href="mes_parcelles.php">Créer une parcelle</a></p>
                            <?php else: ?>
                                <select name="id_parcelle" id="id_parcelle" required>
                                    <option value="">-- Sélectionner une parcelle --</option>
                                    <?php foreach ($parcelles as $parc): ?>
                                        <option value="<?php echo htmlspecialchars($parc['id_parcelle']); ?>"
                                            <?php echo (isset($_POST['id_parcelle']) && $_POST['id_parcelle'] === $parc['id_parcelle']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($parc['nom_parcelle'] . ' - ' . $parc['localisation_parcelle'] . ' (' . formaterNombre($parc['superficie'], 2) . ' ha)'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>

                        <!-- Sélection culture -->
                        <div class="form-group">
                            <label for="id_culture">Culture *</label>
                            <select name="id_culture" id="id_culture" required>
                                <option value="">-- Sélectionner une culture --</option>
                                <?php foreach ($cultures as $culture): ?>
                                    <option value="<?php echo htmlspecialchars($culture['id_culture']); ?>"
                                        <?php echo (isset($_POST['id_culture']) && $_POST['id_culture'] === $culture['id_culture']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($culture['nom_culture'] . ' (cycle: ' . formaterNombre($culture['duree_cycle'], 0) . ' jours)'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Sélection saison -->
                        <div class="form-group">
                            <label for="id_saison">Saison *</label>
                            <select name="id_saison" id="id_saison" required>
                                <option value="">-- Sélectionner une saison --</option>
                                <?php foreach ($saisons as $saison): ?>
                                    <option value="<?php echo htmlspecialchars($saison['id_saison']); ?>"
                                        <?php echo (isset($_POST['id_saison']) && $_POST['id_saison'] === $saison['id_saison']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($saison['libelle_saison'] . ' (' . formaterDate($saison['date_debut_saison']) . ' - ' . formaterDate($saison['date_fin_saison']) . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date semis -->
                        <div class="form-group">
                            <label for="date_semis">Date de semis *</label>
                            <input type="date" name="date_semis" id="date_semis" required
                                   value="<?php echo htmlspecialchars($_POST['date_semis'] ?? date('Y-m-d')); ?>">
                        </div>

                        <!-- Boutons -->
                        <div class="form-actions">
                            <button type="submit" class="btn">Enregistrer</button>
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
</body>
</html>