<?php
/**
 * admin/saisons.php
 * Gestion des saisons par l'administrateur
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('admin');

$message = '';
$erreur = '';
$action = $_GET['action'] ?? 'list';
$idSaison = $_GET['id'] ?? null;

// ==================== TRAITEMENT DES ACTIONS ====================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if (!verifierCsrf()) {
        $erreur = 'Erreur de sécurité (CSRF).';
    } else {
        
        $actionForm = nettoyer($_POST['action']);
        
        if ($actionForm === 'create') {
            
            $libelleSaison = nettoyer($_POST['libelle_saison'] ?? '');
            $dateDebut = nettoyer($_POST['date_debut_saison'] ?? '');
            $dateFin = nettoyer($_POST['date_fin_saison'] ?? '');
            
            if (empty($libelleSaison) || empty($dateDebut) || empty($dateFin)) {
                $erreur = 'Tous les champs sont obligatoires.';
            } elseif (strtotime($dateFin) < strtotime($dateDebut)) {
                $erreur = 'La date de fin doit être postérieure à la date de début.';
            } else {
                try {
                    $idSaisonNouv = genererCodeSimple($pdo, 'SAISON', 'SAI');
                    
                    $stmt = $pdo->prepare(
                        "INSERT INTO SAISON (id_saison, libelle_saison, date_debut_saison, date_fin_saison)
                         VALUES (?, ?, ?, ?)"
                    );
                    $stmt->execute([$idSaisonNouv, $libelleSaison, $dateDebut, $dateFin]);
                    
                    $message = "Saison créée avec succès (ID: $idSaisonNouv)";
                    $action = 'list';
                    
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $erreur = 'Cette saison existe déjà.';
                    } else {
                        $erreur = 'Erreur lors de la création.';
                        error_log('Erreur SQL create saison: ' . $e->getMessage());
                    }
                }
            }
        }
        
        elseif ($actionForm === 'update') {
            
            $idSaisonUpdate = nettoyer($_POST['id_saison'] ?? '');
            $libelleSaison = nettoyer($_POST['libelle_saison'] ?? '');
            $dateDebut = nettoyer($_POST['date_debut_saison'] ?? '');
            $dateFin = nettoyer($_POST['date_fin_saison'] ?? '');
            
            if (empty($idSaisonUpdate) || empty($libelleSaison) || empty($dateDebut) || empty($dateFin)) {
                $erreur = 'Tous les champs sont obligatoires.';
            } elseif (strtotime($dateFin) < strtotime($dateDebut)) {
                $erreur = 'La date de fin doit être postérieure à la date de début.';
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "UPDATE SAISON 
                         SET libelle_saison = ?, date_debut_saison = ?, date_fin_saison = ? 
                         WHERE id_saison = ?"
                    );
                    $stmt->execute([$libelleSaison, $dateDebut, $dateFin, $idSaisonUpdate]);
                    
                    $message = "Saison modifiée avec succès";
                    $action = 'list';
                    
                } catch (PDOException $e) {
                    $erreur = 'Erreur lors de la modification.';
                    error_log('Erreur SQL update saison: ' . $e->getMessage());
                }
            }
        }
        
        elseif ($actionForm === 'delete') {
            
            $idSaisonDelete = nettoyer($_POST['id_saison_delete'] ?? '');
            
            try {
                // Vérifier si la saison est utilisée dans des plantations
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) AS total FROM PLANTATION WHERE id_saison = ?");
                $stmtCheck->execute([$idSaisonDelete]);
                $result = $stmtCheck->fetch();
                
                if ($result['total'] > 0) {
                    $erreur = 'Cette saison est utilisée dans des plantations et ne peut pas être supprimée.';
                } else {
                    // Supprimer la saison
                    $stmtDelete = $pdo->prepare("DELETE FROM SAISON WHERE id_saison = ?");
                    $stmtDelete->execute([$idSaisonDelete]);
                    
                    $message = "Saison supprimée avec succès";
                    $action = 'list';
                }
                
            } catch (PDOException $e) {
                $erreur = 'Erreur lors de la suppression.';
                error_log('Erreur SQL delete saison: ' . $e->getMessage());
            }
        }
    }
}

initialiserCsrf();

// ==================== AFFICHAGE ====================
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Saisons - AgriGest Togo</title>
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
            <h2>Gestion des Saisons</h2>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erreur); ?></div>
            <?php endif; ?>

            <!-- ========== LISTE DES SAISONS ========== -->
            <?php if ($action === 'list'): ?>
                
                <div class="actions-bar">
                    <a href="?action=create" class="btn btn-primary">+ Ajouter une saison</a>
                </div>

                <?php
                try {
                    $stmt = $pdo->query(
                        "SELECT id_saison, libelle_saison, date_debut_saison, date_fin_saison
                         FROM SAISON
                         ORDER BY date_debut_saison DESC"
                    );
                    $saisons = $stmt->fetchAll();
                    
                    if (!empty($saisons)):
                ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Libellé</th>
                                    <th>Date début</th>
                                    <th>Date fin</th>
                                    <th>Plantations</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($saisons as $saison): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($saison['id_saison']); ?></td>
                                        <td><?php echo htmlspecialchars($saison['libelle_saison']); ?></td>
                                        <td><?php echo formaterDate($saison['date_debut_saison']); ?></td>
                                        <td><?php echo formaterDate($saison['date_fin_saison']); ?></td>
                                        <td>
                                            <?php
                                            $stmtCount = $pdo->prepare("SELECT COUNT(*) AS total FROM PLANTATION WHERE id_saison = ?");
                                            $stmtCount->execute([$saison['id_saison']]);
                                            $count = $stmtCount->fetch()['total'];
                                            echo $count;
                                            ?>
                                        </td>
                                        <td>
                                            <a href="?action=edit&id=<?php echo urlencode($saison['id_saison']); ?>" class="btn btn-secondary">Modifier</a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id_saison_delete" value="<?php echo htmlspecialchars($saison['id_saison']); ?>">
                                                <button type="submit" class="btn btn-danger">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                <?php 
                    else:
                        echo '<p class="no-data">Aucune saison enregistrée.</p>';
                    endif;
                } catch (PDOException $e) {
                    echo '<div class="alert alert-danger">Erreur lors du chargement des saisons.</div>';
                    error_log('Erreur SQL list saison: ' . $e->getMessage());
                }
                ?>

            <!-- ========== FORMULAIRE CREATION ========== -->
            <?php elseif ($action === 'create'): ?>
                
                <a href="?action=list" class="btn btn-secondary">← Retour à la liste</a>

                <form method="POST" class="form-container">
                    <h3>Créer une nouvelle saison</h3>

                    <div class="form-group">
                        <label for="libelle_saison">Libellé de la saison</label>
                        <input type="text" id="libelle_saison" name="libelle_saison" placeholder="Ex: Saison des pluies 2024" required>
                    </div>

                    <div class="form-group">
                        <label for="date_debut_saison">Date de début</label>
                        <input type="date" id="date_debut_saison" name="date_debut_saison" required>
                    </div>

                    <div class="form-group">
                        <label for="date_fin_saison">Date de fin</label>
                        <input type="date" id="date_fin_saison" name="date_fin_saison" required>
                    </div>

                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <button type="submit" class="btn btn-primary">Créer</button>
                </form>

            <!-- ========== FORMULAIRE MODIFICATION ========== -->
            <?php elseif ($action === 'edit' && $idSaison): ?>
                
                <a href="?action=list" class="btn btn-secondary">← Retour à la liste</a>

                <?php
                try {
                    $stmtEdit = $pdo->prepare(
                        "SELECT id_saison, libelle_saison, date_debut_saison, date_fin_saison
                         FROM SAISON
                         WHERE id_saison = ?"
                    );
                    $stmtEdit->execute([$idSaison]);
                    $saisonEdit = $stmtEdit->fetch();

                    if ($saisonEdit):
                ?>
                        <form method="POST" class="form-container">
                            <h3>Modifier la saison</h3>

                            <div class="form-group">
                                <label for="id_saison_display">ID (lecture seule)</label>
                                <input type="text" id="id_saison_display" value="<?php echo htmlspecialchars($saisonEdit['id_saison']); ?>" disabled>
                                <input type="hidden" name="id_saison" value="<?php echo htmlspecialchars($saisonEdit['id_saison']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="libelle_saison">Libellé de la saison</label>
                                <input type="text" id="libelle_saison" name="libelle_saison" value="<?php echo htmlspecialchars($saisonEdit['libelle_saison']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="date_debut_saison">Date de début</label>
                                <input type="date" id="date_debut_saison" name="date_debut_saison" value="<?php echo htmlspecialchars($saisonEdit['date_debut_saison']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="date_fin_saison">Date de fin</label>
                                <input type="date" id="date_fin_saison" name="date_fin_saison" value="<?php echo htmlspecialchars($saisonEdit['date_fin_saison']); ?>" required>
                            </div>

                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <button type="submit" class="btn btn-primary">Modifier</button>
                        </form>
                <?php 
                    else:
                        echo '<div class="alert alert-danger">Saison non trouvée.</div>';
                    endif;
                } catch (PDOException $e) {
                    echo '<div class="alert alert-danger">Erreur lors du chargement.</div>';
                    error_log('Erreur SQL edit saison: ' . $e->getMessage());
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