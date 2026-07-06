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
                // Verifier si l'intrant est utilisé dans des plantations
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) AS total FROM UTILISER WHERE id_intrant = ?");
                $stmtCheck->execute([$idIntrantDelete]);
                $result = $stmtCheck->fetch();
                
                if ($result['total'] > 0) {
                    $erreur = 'Cet intrant est utilisé dans des plantations et ne peut pas être supprimé.';
                } else {
                    // Supprimer l'intrant
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

// ==================== AFFICHAGE ====================
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Intrants - AgriGest Togo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🌾 AgriGest Togo - Admin</h1>
            <nav>
                <a href="dashboard.php">Tableau de bord</a>
                <a href="agriculteurs.php">Agriculteurs</a>
                <a href="cultures.php">Cultures</a>
                <a href="cooperatives.php">Coopératives</a>
                <a href="zones.php">Zones</a>
                <a href="intrants.php">Intrants</a>
                <a href="saisons.php">Saisons</a>
                <a href="../auth/logout.php">Déconnexion</a>
            </nav>
        </header>

        <main>
            <h2>Gestion des Intrants</h2>

            <?php if ($message): ?>
                <div class="message-success">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($erreur): ?>
                <div class="message-erreur">
                    <?php echo htmlspecialchars($erreur); ?>
                </div>
            <?php endif; ?>

            <!-- ========== LISTE DES INTRANTS ========== -->
            <?php if ($action === 'list'): ?>
                
                <div class="actions-bar">
                    <a href="?action=create" class="btn btn-primary">+ Ajouter un intrant</a>
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
                        <table class="table">
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
                                                'engrais' => '🧪 Engrais',
                                                'semence' => '🌱 Semence',
                                                'eau' => '💧 Eau'
                                            ];
                                            echo htmlspecialchars($types[$intrant['type_intrant']] ?? $intrant['type_intrant']);
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($intrant['unite_mesure']); ?></td>
                                        <td>
                                            <a href="?action=edit&id=<?php echo urlencode($intrant['id_intrant']); ?>" class="btn btn-small btn-edit">Modifier</a>
                                            <form method="POST" class="form-delete-inline" onsubmit="return confirm('Êtes-vous sûr ?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id_intrant_delete" value="<?php echo htmlspecialchars($intrant['id_intrant']); ?>">
                                                <button type="submit" class="btn btn-small btn-delete">Supprimer</button>
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
                    echo '<div class="message-erreur">Erreur lors du chargement des intrants.</div>';
                    error_log('Erreur SQL list intrant: ' . $e->getMessage());
                }
                ?>

            <!-- ========== FORMULAIRE CREATION ========== -->
            <?php elseif ($action === 'create'): ?>
                
                <a href="?action=list" class="btn btn-secondary">← Retour à la liste</a>

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

                    <button type="submit" class="btn btn-primary">Créer</button>
                </form>

            <!-- ========== FORMULAIRE MODIFICATION ========== -->
            <?php elseif ($action === 'edit' && $idIntrant): ?>
                
                <a href="?action=list" class="btn btn-secondary">← Retour à la liste</a>

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

                            <button type="submit" class="btn btn-primary">Modifier</button>
                        </form>
                <?php 
                    else:
                        echo '<div class="message-erreur">Intrant non trouvé.</div>';
                    endif;
                } catch (PDOException $e) {
                    echo '<div class="message-erreur">Erreur lors du chargement.</div>';
                    error_log('Erreur SQL edit intrant: ' . $e->getMessage());
                }
                ?>

            <?php endif; ?>

        </main>

        <footer>
            <p>&copy; 2024 AgriGest Togo - Gestion des Exploitations Agricoles</p>
        </footer>
    </div>
</body>
</html>