<?php
/**
 * responsable/modifier_parcelle.php
 * Modification d'une parcelle
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
    // Récupérer les informations de la parcelle
    $stmt = $pdo->prepare(
        "SELECT p.id_parcelle, p.nom_parcelle, p.localisation_parcelle, p.superficie, p.id_zone, p.id_agri
         FROM PARCELLE p
         WHERE p.id_parcelle = ?"
    );
    $stmt->execute([$id_parcelle]);
    $parcelle = $stmt->fetch();

    if (!$parcelle) {
        $erreur = 'Parcelle non trouvée.';
    }

    // Récupérer les zones pour le select
    $zones = $pdo->query("SELECT id_zone, nom_zone FROM ZONE_AGROECOLOGIQUE ORDER BY nom_zone")->fetchAll();
    
    // Récupérer les agriculteurs de la coopérative
    $id_responsable = $_SESSION['id_utilisateur'];
    $stmt = $pdo->prepare(
        "SELECT a.id_agri, u.nom, u.prenom 
         FROM AGRICULTEUR a
         JOIN UTILISATEUR u ON a.id_agri = u.id_utilisateur
         WHERE a.id_coop = (SELECT id_coop FROM RESPONSABLE WHERE id_responsable = ?)"
    );
    $stmt->execute([$id_responsable]);
    $agriculteurs = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Erreur SQL modifier_parcelle: ' . $e->getMessage());
    $erreur = 'Erreur lors du chargement des données.';
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) {
        $erreur = 'Token CSRF invalide.';
    } else {
        $nom_parcelle = nettoyer($_POST['nom_parcelle'] ?? '');
        $localisation = nettoyer($_POST['localisation_parcelle'] ?? '');
        $superficie = $_POST['superficie'] ?? '';
        $id_zone = nettoyer($_POST['id_zone'] ?? '');
        $id_agri = nettoyer($_POST['id_agri'] ?? '');
        
        if (empty($nom_parcelle) || empty($localisation) || empty($superficie) || empty($id_zone) || empty($id_agri)) {
            $erreur = 'Tous les champs sont obligatoires.';
        } elseif (!is_numeric($superficie) || $superficie <= 0) {
            $erreur = 'La superficie doit être un nombre positif.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    "UPDATE PARCELLE 
                     SET nom_parcelle = ?, localisation_parcelle = ?, superficie = ?, id_zone = ?, id_agri = ? 
                     WHERE id_parcelle = ?"
                );
                $stmt->execute([$nom_parcelle, $localisation, $superficie, $id_zone, $id_agri, $id_parcelle]);
                
                $message = 'Parcelle modifiée avec succès !';
                
                // Recharger les données
                $stmt = $pdo->prepare(
                    "SELECT p.id_parcelle, p.nom_parcelle, p.localisation_parcelle, p.superficie, p.id_zone, p.id_agri
                     FROM PARCELLE p
                     WHERE p.id_parcelle = ?"
                );
                $stmt->execute([$id_parcelle]);
                $parcelle = $stmt->fetch();
                
            } catch (PDOException $e) {
                error_log('Erreur modification parcelle: ' . $e->getMessage());
                $erreur = 'Erreur lors de la modification.';
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
    <title>Modifier Parcelle - AgriGest Togo</title>
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
                <h2>Modifier une parcelle</h2>
                <p><a href="parcelles.php">&larr; Retour à la liste</a></p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erreur); ?></div>
            <?php endif; ?>

            <?php if ($parcelle): ?>

            <div class="card">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="id_parcelle" value="<?php echo htmlspecialchars($parcelle['id_parcelle']); ?>">

                    <div class="form-group">
                        <label for="nom_parcelle">Nom de la parcelle *</label>
                        <input type="text" id="nom_parcelle" name="nom_parcelle" value="<?php echo htmlspecialchars($parcelle['nom_parcelle']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="localisation_parcelle">Localisation *</label>
                        <input type="text" id="localisation_parcelle" name="localisation_parcelle" value="<?php echo htmlspecialchars($parcelle['localisation_parcelle']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="superficie">Superficie (hectares) *</label>
                        <input type="number" id="superficie" name="superficie" value="<?php echo htmlspecialchars($parcelle['superficie']); ?>" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="id_zone">Zone agroécologique *</label>
                        <select id="id_zone" name="id_zone" required>
                            <option value="">-- Sélectionner une zone --</option>
                            <?php foreach ($zones as $zone): ?>
                                <option value="<?php echo htmlspecialchars($zone['id_zone']); ?>" <?php echo ($zone['id_zone'] === $parcelle['id_zone']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($zone['nom_zone']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_agri">Agriculteur propriétaire *</label>
                        <select id="id_agri" name="id_agri" required>
                            <option value="">-- Sélectionner un agriculteur --</option>
                            <?php foreach ($agriculteurs as $agri): ?>
                                <option value="<?php echo htmlspecialchars($agri['id_agri']); ?>" <?php echo ($agri['id_agri'] === $parcelle['id_agri']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($agri['prenom'] . ' ' . $agri['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                        <a href="parcelles.php" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
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