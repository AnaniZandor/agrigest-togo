<?php
/**
 * admin/zones.php
 * Gestion des zones agroécologiques par l'administrateur
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('admin');

$message = '';
$erreur = '';
$action = $_GET['action'] ?? 'list';
$idZone = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if (!verifierCsrf()) {
        $erreur = 'Erreur de sécurité (CSRF).';
    } else {
        
        $actionForm = nettoyer($_POST['action']);
        
        if ($actionForm === 'create') {
            
            $nomZone = nettoyer($_POST['nom_zone'] ?? '');
            
            if (empty($nomZone)) {
                $erreur = 'Le nom de la zone est obligatoire.';
            } else {
                try {
                    $idZoneNouv = genererCodeSimple($pdo, 'ZONE_AGROECOLOGIQUE', 'ZON');
                    
                    $stmt = $pdo->prepare(
                        "INSERT INTO ZONE_AGROECOLOGIQUE (id_zone, nom_zone)
                         VALUES (?, ?)"
                    );
                    $stmt->execute([$idZoneNouv, $nomZone]);
                    
                    $message = "Zone créée avec succès (ID: $idZoneNouv)";
                    $action = 'list';
                    
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $erreur = 'Cette zone existe déjà.';
                    } else {
                        $erreur = 'Erreur lors de la création.';
                        error_log('Erreur SQL create zone: ' . $e->getMessage());
                    }
                }
            }
        }
        
        elseif ($actionForm === 'update') {
            
            $idZoneUpdate = nettoyer($_POST['id_zone'] ?? '');
            $nomZone = nettoyer($_POST['nom_zone'] ?? '');
            
            if (empty($idZoneUpdate) || empty($nomZone)) {
                $erreur = 'Tous les champs sont obligatoires.';
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "UPDATE ZONE_AGROECOLOGIQUE 
                         SET nom_zone = ? 
                         WHERE id_zone = ?"
                    );
                    $stmt->execute([$nomZone, $idZoneUpdate]);
                    
                    $message = "Zone modifiée avec succès";
                    $action = 'list';
                    
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $erreur = 'Cette zone existe déjà.';
                    } else {
                        $erreur = 'Erreur lors de la modification.';
                        error_log('Erreur SQL update zone: ' . $e->getMessage());
                    }
                }
            }
        }
        
        elseif ($actionForm === 'delete') {
            
            $idZoneDelete = nettoyer($_POST['id_zone_delete'] ?? '');
            
            try {
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) AS total FROM PARCELLE WHERE id_zone = ?");
                $stmtCheck->execute([$idZoneDelete]);
                $result = $stmtCheck->fetch();
                
                if ($result['total'] > 0) {
                    $erreur = 'Cette zone est utilisée dans des parcelles et ne peut pas être supprimée.';
                } else {
                    $stmtDelete = $pdo->prepare("DELETE FROM ZONE_AGROECOLOGIQUE WHERE id_zone = ?");
                    $stmtDelete->execute([$idZoneDelete]);
                    
                    $message = "Zone supprimée avec succès";
                    $action = 'list';
                }
                
            } catch (PDOException $e) {
                $erreur = 'Erreur lors de la suppression.';
                error_log('Erreur SQL delete zone: ' . $e->getMessage());
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
    <title>Gestion des Zones Agroécologiques - AgriGest Togo</title>
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
                <h1>🌾 AgriGest Togo - Admin</h1>
                <button class="hamburger" id="hamburgerBtn" aria-label="Menu principal" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <nav id="navMenu">
                    <a href="dashboard.php">Tableau de bord</a>
                    <a href="agriculteurs.php">Agriculteurs</a>
                    <a href="cultures.php">Cultures</a>
                    <a href="cooperatives.php">Coopératives</a>
                    <a href="zones.php">Zones</a>
                    <a href="intrants.php">Intrants</a>
                    <a href="parcelles.php">Parcelles</a>
                    <a href="../auth/logout.php">Déconnexion</a>
                </nav>
            </div>
        </header>

        <main>
            <h2>Gestion des Zones Agroécologiques</h2>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erreur); ?></div>
            <?php endif; ?>

            <?php if ($action === 'list'): ?>
                
                <div class="actions-bar">
                    <a href="?action=create" class="btn btn-primary">+ Ajouter une zone</a>
                </div>

                <?php
                try {
                    $stmt = $pdo->query(
                        "SELECT id_zone, nom_zone FROM ZONE_AGROECOLOGIQUE ORDER BY nom_zone"
                    );
                    $zones = $stmt->fetchAll();
                    
                    if (!empty($zones)):
                ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom de la zone</th>
                                    <th>Nombre de parcelles</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($zones as $zone): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($zone['id_zone']); ?></td>
                                        <td><?php echo htmlspecialchars($zone['nom_zone']); ?></td>
                                        <td>
                                            <?php
                                            $stmtCount = $pdo->prepare("SELECT COUNT(*) AS total FROM PARCELLE WHERE id_zone = ?");
                                            $stmtCount->execute([$zone['id_zone']]);
                                            $count = $stmtCount->fetch()['total'];
                                            echo $count;
                                            ?>
                                        </td>
                                        <td>
                                            <a href="?action=edit&id=<?php echo urlencode($zone['id_zone']); ?>" class="btn btn-secondary">Modifier</a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id_zone_delete" value="<?php echo htmlspecialchars($zone['id_zone']); ?>">
                                                <button type="submit" class="btn btn-danger">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                <?php 
                    else:
                        echo '<p class="no-data">Aucune zone agroécologique enregistrée.</p>';
                    endif;
                } catch (PDOException $e) {
                    echo '<div class="alert alert-danger">Erreur lors du chargement des zones.</div>';
                    error_log('Erreur SQL list zone: ' . $e->getMessage());
                }
                ?>

            <?php elseif ($action === 'create'): ?>
                
                <a href="?action=list" class="btn btn-secondary">← Retour à la liste</a>

                <form method="POST" class="form-container">
                    <h3>Créer une nouvelle zone agroécologique</h3>

                    <div class="form-group">
                        <label for="nom_zone">Nom de la zone</label>
                        <input type="text" id="nom_zone" name="nom_zone" placeholder="Ex: Région Maritime, Région des Savanes..." required>
                    </div>

                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <button type="submit" class="btn btn-primary">Créer</button>
                </form>

            <?php elseif ($action === 'edit' && $idZone): ?>
                
                <a href="?action=list" class="btn btn-secondary">← Retour à la liste</a>

                <?php
                try {
                    $stmtEdit = $pdo->prepare(
                        "SELECT id_zone, nom_zone FROM ZONE_AGROECOLOGIQUE WHERE id_zone = ?"
                    );
                    $stmtEdit->execute([$idZone]);
                    $zoneEdit = $stmtEdit->fetch();

                    if ($zoneEdit):
                ?>
                        <form method="POST" class="form-container">
                            <h3>Modifier la zone agroécologique</h3>

                            <div class="form-group">
                                <label for="id_zone_display">ID (lecture seule)</label>
                                <input type="text" id="id_zone_display" value="<?php echo htmlspecialchars($zoneEdit['id_zone']); ?>" disabled>
                                <input type="hidden" name="id_zone" value="<?php echo htmlspecialchars($zoneEdit['id_zone']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="nom_zone">Nom de la zone</label>
                                <input type="text" id="nom_zone" name="nom_zone" value="<?php echo htmlspecialchars($zoneEdit['nom_zone']); ?>" required>
                            </div>

                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <button type="submit" class="btn btn-primary">Modifier</button>
                        </form>
                <?php 
                    else:
                        echo '<div class="alert alert-danger">Zone non trouvée.</div>';
                    endif;
                } catch (PDOException $e) {
                    echo '<div class="alert alert-danger">Erreur lors du chargement.</div>';
                    error_log('Erreur SQL edit zone: ' . $e->getMessage());
                }
                ?>

            <?php endif; ?>

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