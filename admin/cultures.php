<?php
/**
 * admin/cultures.php
 * Gestion des cultures par l'administrateur
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('admin');

$message = '';
$erreur = '';
$action = $_GET['action'] ?? 'list';
$idCulture = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if (!verifierCsrf()) {
        $erreur = 'Erreur de sécurité (CSRF).';
    } else {
        
        $actionForm = nettoyer($_POST['action']);
        
        if ($actionForm === 'create') {
            
            $nomCulture = nettoyer($_POST['nom_culture'] ?? '');
            $dureeCycle = $_POST['duree_cycle'] ?? '';
            
            if (empty($nomCulture) || empty($dureeCycle)) {
                $erreur = 'Tous les champs sont obligatoires.';
            } elseif (!is_numeric($dureeCycle) || $dureeCycle <= 0) {
                $erreur = 'La durée du cycle doit être un nombre positif.';
            } else {
                try {
                    $idCultureNouv = genererCodeSimple($pdo, 'CULTURE', 'CUL');
                    
                    $stmt = $pdo->prepare(
                        "INSERT INTO CULTURE (id_culture, nom_culture, duree_cycle)
                         VALUES (?, ?, ?)"
                    );
                    $stmt->execute([$idCultureNouv, $nomCulture, $dureeCycle]);
                    
                    $message = "Culture créée avec succès (ID: $idCultureNouv)";
                    $action = 'list';
                    
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $erreur = 'Cette culture existe déjà.';
                    } else {
                        $erreur = 'Erreur lors de la création.';
                        error_log('Erreur SQL create culture: ' . $e->getMessage());
                    }
                }
            }
        }
        
        elseif ($actionForm === 'update') {
            
            $idCultureUpdate = nettoyer($_POST['id_culture'] ?? '');
            $nomCulture = nettoyer($_POST['nom_culture'] ?? '');
            $dureeCycle = $_POST['duree_cycle'] ?? '';
            
            if (empty($idCultureUpdate) || empty($nomCulture) || empty($dureeCycle)) {
                $erreur = 'Tous les champs sont obligatoires.';
            } elseif (!is_numeric($dureeCycle) || $dureeCycle <= 0) {
                $erreur = 'La durée du cycle doit être un nombre positif.';
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "UPDATE CULTURE 
                         SET nom_culture = ?, duree_cycle = ? 
                         WHERE id_culture = ?"
                    );
                    $stmt->execute([$nomCulture, $dureeCycle, $idCultureUpdate]);
                    
                    $message = "Culture modifiée avec succès";
                    $action = 'list';
                    
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $erreur = 'Cette culture existe déjà.';
                    } else {
                        $erreur = 'Erreur lors de la modification.';
                        error_log('Erreur SQL update culture: ' . $e->getMessage());
                    }
                }
            }
        }
        
        elseif ($actionForm === 'delete') {
            
            $idCultureDelete = nettoyer($_POST['id_culture_delete'] ?? '');
            
            try {
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) AS total FROM PLANTATION WHERE id_culture = ?");
                $stmtCheck->execute([$idCultureDelete]);
                $result = $stmtCheck->fetch();
                
                if ($result['total'] > 0) {
                    $erreur = 'Cette culture est utilisée dans des plantations et ne peut pas être supprimée.';
                } else {
                    $stmtDelete = $pdo->prepare("DELETE FROM CULTURE WHERE id_culture = ?");
                    $stmtDelete->execute([$idCultureDelete]);
                    
                    $message = "Culture supprimée avec succès";
                    $action = 'list';
                }
                
            } catch (PDOException $e) {
                $erreur = 'Erreur lors de la suppression.';
                error_log('Erreur SQL delete culture: ' . $e->getMessage());
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
    <title>Gestion des Cultures - AgriGest Togo</title>
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
<h1><i class="fas fa-seedling" style="color: var(--color-primary);"></i> AgriGest Togo - Admin</h1>
                <button class="hamburger" id="hamburgerBtn" aria-label="Menu principal" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <nav id="navMenu">
    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
    <a href="agriculteurs.php"><i class="fas fa-user-tie"></i> Agriculteurs</a>
    <a href="cultures.php"><i class="fas fa-sprout"></i> Cultures</a>
    <a href="cooperatives.php"><i class="fas fa-handshake"></i> Coopératives</a>
    <a href="zones.php"><i class="fas fa-map"></i> Zones</a>
    <a href="intrants.php"><i class="fas fa-flask"></i> Intrants</a>
    <a href="saisons.php"><i class="fas fa-cloud-sun"></i> Saisons</a>
    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
</nav>
            </div>
        </header>

        <main>
            <h2>Gestion des Cultures</h2>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erreur); ?></div>
            <?php endif; ?>

            <?php if ($action === 'list'): ?>
                
                <div class="actions-bar">
                    <a href="?action=create" class="btn btn-primary">+ Ajouter une culture</a>
                </div>

                <?php
                try {
                    $stmt = $pdo->query(
                        "SELECT id_culture, nom_culture, duree_cycle
                         FROM CULTURE
                         ORDER BY nom_culture"
                    );
                    $cultures = $stmt->fetchAll();
                    
                    if (!empty($cultures)):
                ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Durée du cycle (jours)</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cultures as $culture): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($culture['id_culture']); ?></td>
                                        <td><?php echo htmlspecialchars($culture['nom_culture']); ?></td>
                                        <td><?php echo formaterNombre($culture['duree_cycle'], 2); ?></td>
                                        <td>
                                            <a href="?action=edit&id=<?php echo urlencode($culture['id_culture']); ?>" class="btn btn-secondary">Modifier</a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id_culture_delete" value="<?php echo htmlspecialchars($culture['id_culture']); ?>">
                                                <button type="submit" class="btn btn-danger">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                <?php 
                    else:
                        echo '<p class="no-data">Aucune culture enregistrée.</p>';
                    endif;
                } catch (PDOException $e) {
                    echo '<div class="alert alert-danger">Erreur lors du chargement des cultures.</div>';
                    error_log('Erreur SQL list culture: ' . $e->getMessage());
                }
                ?>

            <?php elseif ($action === 'create'): ?>
                
                <a href="?action=list" class="btn btn-secondary">← Retour à la liste</a>

                <form method="POST" class="form-container">
                    <h3>Créer une nouvelle culture</h3>

                    <div class="form-group">
                        <label for="nom_culture">Nom de la culture</label>
                        <input type="text" id="nom_culture" name="nom_culture" placeholder="Ex: Maïs, Riz, Haricot..." required>
                    </div>

                    <div class="form-group">
                        <label for="duree_cycle">Durée du cycle (en jours)</label>
                        <input type="number" id="duree_cycle" name="duree_cycle" placeholder="Ex: 120" step="0.01" min="0" required>
                    </div>

                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <button type="submit" class="btn btn-primary">Créer</button>
                </form>

            <?php elseif ($action === 'edit' && $idCulture): ?>
                
                <a href="?action=list" class="btn btn-secondary">← Retour à la liste</a>

                <?php
                try {
                    $stmtEdit = $pdo->prepare(
                        "SELECT id_culture, nom_culture, duree_cycle
                         FROM CULTURE
                         WHERE id_culture = ?"
                    );
                    $stmtEdit->execute([$idCulture]);
                    $cultureEdit = $stmtEdit->fetch();

                    if ($cultureEdit):
                ?>
                        <form method="POST" class="form-container">
                            <h3>Modifier la culture</h3>

                            <div class="form-group">
                                <label for="id_culture_display">ID (lecture seule)</label>
                                <input type="text" id="id_culture_display" value="<?php echo htmlspecialchars($cultureEdit['id_culture']); ?>" disabled>
                                <input type="hidden" name="id_culture" value="<?php echo htmlspecialchars($cultureEdit['id_culture']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="nom_culture">Nom de la culture</label>
                                <input type="text" id="nom_culture" name="nom_culture" value="<?php echo htmlspecialchars($cultureEdit['nom_culture']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="duree_cycle">Durée du cycle (en jours)</label>
                                <input type="number" id="duree_cycle" name="duree_cycle" value="<?php echo htmlspecialchars($cultureEdit['duree_cycle']); ?>" step="0.01" min="0" required>
                            </div>

                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <button type="submit" class="btn btn-primary">Modifier</button>
                        </form>
                <?php 
                    else:
                        echo '<div class="alert alert-danger">Culture non trouvée.</div>';
                    endif;
                } catch (PDOException $e) {
                    echo '<div class="alert alert-danger">Erreur lors du chargement.</div>';
                    error_log('Erreur SQL edit culture: ' . $e->getMessage());
                }
                ?>

            <?php endif; ?>

        </main>

        <footer>
            <p>&copy; 2024 AgriGest Togo - Gestion des Exploitations Agricoles</p>
        </footer>
    </div>

    
            <script src="../assets/js/script.js"></script>

</body>
</html>