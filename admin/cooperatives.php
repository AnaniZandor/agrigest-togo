<?php
/**
 * admin/cooperatives.php
 * Gestion des coopératives par l'administrateur
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('admin');

$message = '';
$erreur = '';
$action = $_GET['action'] ?? 'list';
$idCoop = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if (!verifierCsrf()) {
        $erreur = 'Erreur de sécurité (CSRF).';
    } else {
        
        $actionForm = nettoyer($_POST['action']);
        
        if ($actionForm === 'create') {
            
            $nomCoop = nettoyer($_POST['nom_coop'] ?? '');
            $localisationCoop = nettoyer($_POST['localisation_coop'] ?? '');
            
            if (empty($nomCoop) || empty($localisationCoop)) {
                $erreur = 'Tous les champs sont obligatoires.';
            } else {
                try {
                    $idCoopNouv = genererCodeSimple($pdo, 'COOPERATIVE', 'COP');
                    
                    $stmt = $pdo->prepare(
                        "INSERT INTO COOPERATIVE (id_coop, nom_coop, localisation_coop)
                         VALUES (?, ?, ?)"
                    );
                    $stmt->execute([$idCoopNouv, $nomCoop, $localisationCoop]);
                    
                    $message = "Coopérative créée avec succès (ID: $idCoopNouv)";
                    $action = 'list';
                    
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $erreur = 'Cette coopérative existe déjà.';
                    } else {
                        $erreur = 'Erreur lors de la création.';
                        error_log('Erreur SQL create cooperative: ' . $e->getMessage());
                    }
                }
            }
        }
        
        elseif ($actionForm === 'update') {
            
            $idCoopUpdate = nettoyer($_POST['id_coop'] ?? '');
            $nomCoop = nettoyer($_POST['nom_coop'] ?? '');
            $localisationCoop = nettoyer($_POST['localisation_coop'] ?? '');
            
            if (empty($idCoopUpdate) || empty($nomCoop) || empty($localisationCoop)) {
                $erreur = 'Tous les champs sont obligatoires.';
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "UPDATE COOPERATIVE 
                         SET nom_coop = ?, localisation_coop = ? 
                         WHERE id_coop = ?"
                    );
                    $stmt->execute([$nomCoop, $localisationCoop, $idCoopUpdate]);
                    
                    $message = "Coopérative modifiée avec succès";
                    $action = 'list';
                    
                } catch (PDOException $e) {
                    $erreur = 'Erreur lors de la modification.';
                    error_log('Erreur SQL update cooperative: ' . $e->getMessage());
                }
            }
        }
        
        elseif ($actionForm === 'delete') {
            
            $idCoopDelete = nettoyer($_POST['id_coop_delete'] ?? '');
            
            try {
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) AS total FROM RESPONSABLE WHERE id_coop = ?");
                $stmtCheck->execute([$idCoopDelete]);
                $countResp = $stmtCheck->fetch()['total'];
                
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) AS total FROM AGRICULTEUR WHERE id_coop = ?");
                $stmtCheck->execute([$idCoopDelete]);
                $countAgri = $stmtCheck->fetch()['total'];
                
                if ($countResp > 0 || $countAgri > 0) {
                    $erreur = 'Cette coopérative a des responsables ou agriculteurs et ne peut pas être supprimée.';
                } else {
                    $stmtDelete = $pdo->prepare("DELETE FROM COOPERATIVE WHERE id_coop = ?");
                    $stmtDelete->execute([$idCoopDelete]);
                    
                    $message = "Coopérative supprimée avec succès";
                    $action = 'list';
                }
                
            } catch (PDOException $e) {
                $erreur = 'Erreur lors de la suppression.';
                error_log('Erreur SQL delete cooperative: ' . $e->getMessage());
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
    <title>Gestion des Coopératives - AgriGest Togo</title>
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
            <h2>Gestion des Coopératives</h2>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erreur); ?></div>
            <?php endif; ?>

            <?php if ($action === 'list'): ?>
                
                <div class="actions-bar">
                    <a href="?action=create" class="btn btn-primary">+ Ajouter une coopérative</a>
                </div>

                <?php
                try {
                    $stmt = $pdo->query(
                        "SELECT id_coop, nom_coop, localisation_coop FROM COOPERATIVE ORDER BY nom_coop"
                    );
                    $cooperatives = $stmt->fetchAll();
                    
                    if (!empty($cooperatives)):
                ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Localisation</th>
                                    <th>Nombre d'agriculteurs</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cooperatives as $coop): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($coop['id_coop']); ?></td>
                                        <td><?php echo htmlspecialchars($coop['nom_coop']); ?></td>
                                        <td><?php echo htmlspecialchars($coop['localisation_coop']); ?></td>
                                        <td>
                                            <?php
                                            $stmtCount = $pdo->prepare("SELECT COUNT(*) AS total FROM AGRICULTEUR WHERE id_coop = ?");
                                            $stmtCount->execute([$coop['id_coop']]);
                                            $count = $stmtCount->fetch()['total'];
                                            echo $count;
                                            ?>
                                        </td>
                                        <td>
                                            <a href="?action=edit&id=<?php echo urlencode($coop['id_coop']); ?>" class="btn btn-secondary">Modifier</a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id_coop_delete" value="<?php echo htmlspecialchars($coop['id_coop']); ?>">
                                                <button type="submit" class="btn btn-danger">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                <?php 
                    else:
                        echo '<p class="no-data">Aucune coopérative enregistrée.</p>';
                    endif;
                } catch (PDOException $e) {
                    echo '<div class="alert alert-danger">Erreur lors du chargement des coopératives.</div>';
                    error_log('Erreur SQL list cooperative: ' . $e->getMessage());
                }
                ?>

            <?php elseif ($action === 'create'): ?>
                
                <a href="?action=list" class="btn btn-secondary">← Retour à la liste</a>

                <form method="POST" class="form-container">
                    <h3>Créer une nouvelle coopérative</h3>

                    <div class="form-group">
                        <label for="nom_coop">Nom de la coopérative</label>
                        <input type="text" id="nom_coop" name="nom_coop" required>
                    </div>

                    <div class="form-group">
                        <label for="localisation_coop">Localisation</label>
                        <input type="text" id="localisation_coop" name="localisation_coop" placeholder="Ex: Lomé, Région Maritime..." required>
                    </div>

                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <button type="submit" class="btn btn-primary">Créer</button>
                </form>

            <?php elseif ($action === 'edit' && $idCoop): ?>
                
                <a href="?action=list" class="btn btn-secondary">← Retour à la liste</a>

                <?php
                try {
                    $stmtEdit = $pdo->prepare(
                        "SELECT id_coop, nom_coop, localisation_coop FROM COOPERATIVE WHERE id_coop = ?"
                    );
                    $stmtEdit->execute([$idCoop]);
                    $coopEdit = $stmtEdit->fetch();

                    if ($coopEdit):
                ?>
                        <form method="POST" class="form-container">
                            <h3>Modifier la coopérative</h3>

                            <div class="form-group">
                                <label for="id_coop_display">ID (lecture seule)</label>
                                <input type="text" id="id_coop_display" value="<?php echo htmlspecialchars($coopEdit['id_coop']); ?>" disabled>
                                <input type="hidden" name="id_coop" value="<?php echo htmlspecialchars($coopEdit['id_coop']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="nom_coop">Nom de la coopérative</label>
                                <input type="text" id="nom_coop" name="nom_coop" value="<?php echo htmlspecialchars($coopEdit['nom_coop']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="localisation_coop">Localisation</label>
                                <input type="text" id="localisation_coop" name="localisation_coop" value="<?php echo htmlspecialchars($coopEdit['localisation_coop']); ?>" required>
                            </div>

                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <button type="submit" class="btn btn-primary">Modifier</button>
                        </form>
                <?php 
                    else:
                        echo '<div class="alert alert-danger">Coopérative non trouvée.</div>';
                    endif;
                } catch (PDOException $e) {
                    echo '<div class="alert alert-danger">Erreur lors du chargement.</div>';
                    error_log('Erreur SQL edit cooperative: ' . $e->getMessage());
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