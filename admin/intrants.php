<?php
/**
 * admin/intrants.php
 * Gestion des intrants par l'administrateur
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('admin');

$message = '';
$erreur = '';
$action = $_GET['action'] ?? 'list';
$idIntrant = $_GET['id'] ?? null;

// ==================== TRAITEMENT DES ACTIONS ====================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if (!verifierCsrf()) {
        $erreur = 'Erreur de sécurité (CSRF).';
    } else {
        
        $actionForm = nettoyer($_POST['action']);
        
        if ($actionForm === 'create') {
            
            $nomIntrant = nettoyer($_POST['nom_intrant'] ?? '');
            $typeIntrant = nettoyer($_POST['type_intrant'] ?? '');
            $uniteMesure = nettoyer($_POST['unite_mesure'] ?? '');
            
            if (empty($nomIntrant) || empty($typeIntrant) || empty($uniteMesure)) {
                $erreur = 'Tous les champs sont obligatoires.';
            } elseif (!in_array($typeIntrant, ['engrais', 'semence', 'eau'])) {
                $erreur = 'Type d\'intrant invalide.';
            } else {
                try {
                    $idIntrantNouv = genererCodeSimple($pdo, 'INTRANT', 'INT');
                    
                    $stmt = $pdo->prepare(
                        "INSERT INTO INTRANT (id_intrant, nom_intrant, type_intrant, unite_mesure)
                         VALUES (?, ?, ?, ?)"
                    );
                    $stmt->execute([$idIntrantNouv, $nomIntrant, $typeIntrant, $uniteMesure]);
                    
                    $message = "Intrant créé avec succès (ID: $idIntrantNouv)";
                    $action = 'list';
                    
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $erreur = 'Cet intrant existe déjà.';
                    } else {
                        $erreur = 'Erreur lors de la création.';
                        error_log('Erreur SQL create intrant: ' . $e->getMessage());
                    }
                }
            }
        }
        
        elseif ($actionForm === 'update') {
            
            $idIntrantUpdate = nettoyer($_POST['id_intrant'] ?? '');
            $nomIntrant = nettoyer($_POST['nom_intrant'] ?? '');
            $typeIntrant = nettoyer($_POST['type_intrant'] ?? '');
            $uniteMesure = nettoyer($_POST['unite_mesure'] ?? '');
            
            if (empty($idIntrantUpdate) || empty($nomIntrant) || empty($typeIntrant) || empty($uniteMesure)) {
                $erreur = 'Tous les champs sont obligatoires.';
            } elseif (!in_array($typeIntrant, ['engrais', 'semence', 'eau'])) {
                $erreur = 'Type d\'intrant invalide.';
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "UPDATE INTRANT 
                         SET nom_intrant = ?, type_intrant = ?, unite_mesure = ? 
                         WHERE id_intrant = ?"
                    );
                    $stmt->execute([$nomIntrant, $typeIntrant, $uniteMesure, $idIntrantUpdate]);
                    
                    $message = "Intrant modifié avec succès";
                    $action = 'list';
                    
                } catch (PDOException $e) {
                    $erreur = 'Erreur lors de la modification.';
                    error_log('Erreur SQL update intrant: ' . $e->getMessage());
                }
            }
        }
        
        elseif ($actionForm === 'delete') {
            
            $idIntrantDelete = nettoyer($_POST['id_intrant_delete'] ?? '');
            
            try {
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) AS total FROM UTILISER WHERE id_intrant = ?");
                $stmtCheck->execute([$idIntrantDelete]);
                $result = $stmtCheck->fetch();
                
                if ($result['total'] > 0) {
                    $erreur = 'Cet intrant est utilisé dans des plantations et ne peut pas être supprimé.';
                } else {
                    $stmtDelete = $pdo->prepare("DELETE FROM INTRANT WHERE id_intrant = ?");
                    $stmtDelete->execute([$idIntrantDelete]);
                    
                    $message = "Intrant supprimé avec succès";
                    $action = 'list';
                }
                
            } catch (PDOException $e) {
                $erreur = 'Erreur lors de la suppression.';
                error_log('Erreur SQL delete intrant: ' . $e->getMessage());
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
    <title>Gestion des Intrants - AgriGest Togo</title>
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
            <h2>Gestion des Intrants</h2>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erreur); ?></div>
            <?php endif; ?>

            <!-- ========== LISTE DES INTRANTS ========== -->
            <?php if ($action === 'list'): ?>
                
                <div class="actions-bar">
                    <a href="?action=create" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter un intrant</a>
                </div>

                <?php
                try {
                    $stmt = $pdo->query(
                        "SELECT id_intrant, nom_intrant, type_intrant, unite_mesure
                         FROM INTRANT
                         ORDER BY nom_intrant"
                    );
                    $intrants = $stmt->fetchAll();
                    
                    if (!empty($intrants)):
                ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Type</th>
                                    <th>Unité de mesure</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($intrants as $intrant): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($intrant['id_intrant']); ?></td>
                                        <td><?php echo htmlspecialchars($intrant['nom_intrant']); ?></td>
                                        <td>
                                            <?php 
                                            $types = [
                                                'engrais' => '<i class="fas fa-flask"></i> Engrais',
                                                'semence' => '<i class="fas fa-seedling"></i> Semence',
                                                'eau' => '<i class="fas fa-tint"></i> Eau'
                                            ];
                                            echo $types[$intrant['type_intrant']] ?? $intrant['type_intrant'];
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($intrant['unite_mesure']); ?></td>
                                        <td>
                                            <a href="?action=edit&id=<?php echo urlencode($intrant['id_intrant']); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i> Modifier</a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id_intrant_delete" value="<?php echo htmlspecialchars($intrant['id_intrant']); ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                <?php 
                    else:
                        echo '<p class="no-data">Aucun intrant enregistré.</p>';
                    endif;
                } catch (PDOException $e) {
                    echo '<div class="alert alert-danger">Erreur lors du chargement des intrants.</div>';
                    error_log('Erreur SQL list intrant: ' . $e->getMessage());
                }
                ?>

            <!-- ========== FORMULAIRE CREATION ========== -->
            <?php elseif ($action === 'create'): ?>
                
                <a href="?action=list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour à la liste</a>

                <form method="POST" class="form-container">
                    <h3>Créer un nouvel intrant</h3>

                    <div class="form-group">
                        <label for="nom_intrant">Nom de l'intrant</label>
                        <input type="text" id="nom_intrant" name="nom_intrant" placeholder="Ex: Urée, Maïs hybrid, Irrigation..." required>
                    </div>

                    <div class="form-group">
                        <label for="type_intrant">Type d'intrant</label>
                        <select id="type_intrant" name="type_intrant" required>
                            <option value="">-- Sélectionner un type --</option>
                            <option value="engrais">Engrais</option>
                            <option value="semence">Semence</option>
                            <option value="eau">Eau</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="unite_mesure">Unité de mesure</label>
                        <input type="text" id="unite_mesure" name="unite_mesure" placeholder="Ex: kg, litre, m³..." required>
                    </div>

                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Créer</button>
                </form>

            <!-- ========== FORMULAIRE MODIFICATION ========== -->
            <?php elseif ($action === 'edit' && $idIntrant): ?>
                
                <a href="?action=list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour à la liste</a>

                <?php
                try {
                    $stmtEdit = $pdo->prepare(
                        "SELECT id_intrant, nom_intrant, type_intrant, unite_mesure
                         FROM INTRANT
                         WHERE id_intrant = ?"
                    );
                    $stmtEdit->execute([$idIntrant]);
                    $intrantEdit = $stmtEdit->fetch();

                    if ($intrantEdit):
                ?>
                        <form method="POST" class="form-container">
                            <h3>Modifier l'intrant</h3>

                            <div class="form-group">
                                <label for="id_intrant_display">ID (lecture seule)</label>
                                <input type="text" id="id_intrant_display" value="<?php echo htmlspecialchars($intrantEdit['id_intrant']); ?>" disabled>
                                <input type="hidden" name="id_intrant" value="<?php echo htmlspecialchars($intrantEdit['id_intrant']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="nom_intrant">Nom de l'intrant</label>
                                <input type="text" id="nom_intrant" name="nom_intrant" value="<?php echo htmlspecialchars($intrantEdit['nom_intrant']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="type_intrant">Type d'intrant</label>
                                <select id="type_intrant" name="type_intrant" required>
                                    <option value="engrais" <?php echo ($intrantEdit['type_intrant'] === 'engrais') ? 'selected' : ''; ?>>Engrais</option>
                                    <option value="semence" <?php echo ($intrantEdit['type_intrant'] === 'semence') ? 'selected' : ''; ?>>Semence</option>
                                    <option value="eau" <?php echo ($intrantEdit['type_intrant'] === 'eau') ? 'selected' : ''; ?>>Eau</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="unite_mesure">Unité de mesure</label>
                                <input type="text" id="unite_mesure" name="unite_mesure" value="<?php echo htmlspecialchars($intrantEdit['unite_mesure']); ?>" required>
                            </div>

                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Modifier</button>
                        </form>
                <?php 
                    else:
                        echo '<div class="alert alert-danger">Intrant non trouvé.</div>';
                    endif;
                } catch (PDOException $e) {
                    echo '<div class="alert alert-danger">Erreur lors du chargement.</div>';
                    error_log('Erreur SQL edit intrant: ' . $e->getMessage());
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
</body>
</html>