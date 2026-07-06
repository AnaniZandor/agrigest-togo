<?php
/**
 * admin/agriculteurs.php
 * Gestion des agriculteurs par l'administrateur
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('admin');

$message = '';
$erreur = '';
$action = $_GET['action'] ?? 'list';
$idAgri = $_GET['id'] ?? null;

// ==================== TRAITEMENT DES ACTIONS ====================

// Ajouter un agriculteur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if (!verifierCsrf()) {
        $erreur = 'Erreur de sécurité (CSRF).';
    } else {
        
        $actionForm = nettoyer($_POST['action']);
        
        if ($actionForm === 'create') {
            
            $nom = nettoyer($_POST['nom'] ?? '');
            $prenom = nettoyer($_POST['prenom'] ?? '');
            $email = nettoyer($_POST['email'] ?? '');
            $contact = nettoyer($_POST['contact_agri'] ?? '');
            $idCoop = nettoyer($_POST['id_coop'] ?? '');
            $motDePasse = $_POST['mot_de_passe'] ?? '';
            
            if (empty($nom) || empty($prenom) || empty($email) || empty($contact) || empty($idCoop) || empty($motDePasse)) {
                $erreur = 'Tous les champs sont obligatoires.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erreur = 'Email invalide.';
            } else {
                try {
                    // Verifier que la cooperative existe
                    $stmtCoop = $pdo->prepare("SELECT id_coop FROM COOPERATIVE WHERE id_coop = ?");
                    $stmtCoop->execute([$idCoop]);
                    if (!$stmtCoop->fetch()) {
                        $erreur = 'Coopérative non trouvée.';
                    } else {
                        // Generer le code agriculteur
                        $idAgriNouv = genererCodeUtilisateur($pdo, 'agriculteur', $idCoop);
                        
                        // Inserer dans UTILISATEUR
                        $stmtUser = $pdo->prepare(
                            "INSERT INTO UTILISATEUR (id_utilisateur, nom, prenom, email, mot_de_passe, role)
                             VALUES (?, ?, ?, ?, ?, ?)"
                        );
                        $passwordHash = password_hash($motDePasse, PASSWORD_DEFAULT);
                        $stmtUser->execute([
                            $idAgriNouv, 
                            $nom, 
                            $prenom, 
                            $email, 
                            $passwordHash, 
                            'agriculteur'
                        ]);
                        
                        // Inserer dans AGRICULTEUR
                        $stmtAgri = $pdo->prepare(
                            "INSERT INTO AGRICULTEUR (id_agri, contact_agri, id_coop)
                             VALUES (?, ?, ?)"
                        );
                        $stmtAgri->execute([$idAgriNouv, $contact, $idCoop]);
                        
                        $message = "Agriculteur créé avec succès (ID: $idAgriNouv)";
                        $action = 'list';
                    }
                    
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $erreur = 'Cet email existe déjà.';
                    } else {
                        $erreur = 'Erreur lors de la création.';
                        error_log('Erreur SQL create agriculteur: ' . $e->getMessage());
                    }
                }
            }
        }
        
        elseif ($actionForm === 'update') {
            
            $idAgriUpdate = nettoyer($_POST['id_agri'] ?? '');
            $nom = nettoyer($_POST['nom'] ?? '');
            $prenom = nettoyer($_POST['prenom'] ?? '');
            $email = nettoyer($_POST['email'] ?? '');
            $contact = nettoyer($_POST['contact_agri'] ?? '');
            $idCoop = nettoyer($_POST['id_coop'] ?? '');
            
            if (empty($idAgriUpdate) || empty($nom) || empty($prenom) || empty($email) || empty($contact) || empty($idCoop)) {
                $erreur = 'Tous les champs sont obligatoires.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erreur = 'Email invalide.';
            } else {
                try {
                    // Mettre a jour UTILISATEUR
                    $stmtUser = $pdo->prepare(
                        "UPDATE UTILISATEUR 
                         SET nom = ?, prenom = ?, email = ? 
                         WHERE id_utilisateur = ?"
                    );
                    $stmtUser->execute([$nom, $prenom, $email, $idAgriUpdate]);
                    
                    // Mettre a jour AGRICULTEUR
                    $stmtAgri = $pdo->prepare(
                        "UPDATE AGRICULTEUR 
                         SET contact_agri = ?, id_coop = ? 
                         WHERE id_agri = ?"
                    );
                    $stmtAgri->execute([$contact, $idCoop, $idAgriUpdate]);
                    
                    $message = "Agriculteur modifié avec succès";
                    $action = 'list';
                    
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $erreur = 'Cet email est déjà utilisé.';
                    } else {
                        $erreur = 'Erreur lors de la modification.';
                        error_log('Erreur SQL update agriculteur: ' . $e->getMessage());
                    }
                }
            }
        }
        
        elseif ($actionForm === 'delete') {
            
            $idAgriDelete = nettoyer($_POST['id_agri_delete'] ?? '');
            
            try {
                // Supprimer d'abord les references dans UTILISER, PLANTATION
                $pdo->prepare("DELETE FROM UTILISER WHERE id_plantation IN (SELECT id_plantation FROM PLANTATION WHERE id_parcelle IN (SELECT id_parcelle FROM PARCELLE WHERE id_agri = ?))")->execute([$idAgriDelete]);
                $pdo->prepare("DELETE FROM RECOLTE WHERE id_plantation IN (SELECT id_plantation FROM PLANTATION WHERE id_parcelle IN (SELECT id_parcelle FROM PARCELLE WHERE id_agri = ?))")->execute([$idAgriDelete]);
                $pdo->prepare("DELETE FROM PLANTATION WHERE id_parcelle IN (SELECT id_parcelle FROM PARCELLE WHERE id_agri = ?)")->execute([$idAgriDelete]);
                $pdo->prepare("DELETE FROM PARCELLE WHERE id_agri = ?")->execute([$idAgriDelete]);
                
                // Supprimer de AGRICULTEUR
                $pdo->prepare("DELETE FROM AGRICULTEUR WHERE id_agri = ?")->execute([$idAgriDelete]);
                
                // Supprimer de UTILISATEUR
                $pdo->prepare("DELETE FROM UTILISATEUR WHERE id_utilisateur = ?")->execute([$idAgriDelete]);
                
                $message = "Agriculteur supprimé avec succès";
                $action = 'list';
                
            } catch (PDOException $e) {
                $erreur = 'Erreur lors de la suppression.';
                error_log('Erreur SQL delete agriculteur: ' . $e->getMessage());
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
    <title>Gestion des Agriculteurs - AgriGest Togo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🌾 AgriGest Togo - Admin</h1>
            <nav>
                <a href="dashboard.php">Tableau de bord</a>
                <a href="agriculteurs.php">Agriculteurs</a>
                <a href="cooperatives.php">Coopératives</a>
                <a href="../auth/logout.php">Déconnexion</a>
            </nav>
        </header>

        <main>
            <h2>Gestion des Agriculteurs</h2>

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

            <!-- ========== LISTE DES AGRICULTEURS ========== -->
            <?php if ($action === 'list'): ?>
                
                <div class="actions-bar">
                    <a href="?action=create" class="btn btn-primary">+ Ajouter un agriculteur</a>
                </div>

                <?php
                try {
                    $stmt = $pdo->query(
                        "SELECT a.id_agri, u.nom, u.prenom, u.email, a.contact_agri, a.id_coop, c.nom_coop
                         FROM AGRICULTEUR a
                         JOIN UTILISATEUR u ON a.id_agri = u.id_utilisateur
                         JOIN COOPERATIVE c ON a.id_coop = c.id_coop
                         ORDER BY u.nom, u.prenom"
                    );
                    $agriculteurs = $stmt->fetchAll();
                    
                    if (!empty($agriculteurs)):
                ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>Coopérative</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agriculteurs as $agri): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($agri['id_agri']); ?></td>
                                        <td><?php echo htmlspecialchars($agri['nom']); ?></td>
                                        <td><?php echo htmlspecialchars($agri['prenom']); ?></td>
                                        <td><?php echo htmlspecialchars($agri['email']); ?></td>
                                        <td><?php echo htmlspecialchars($agri['contact_agri']); ?></td>
                                        <td><?php echo htmlspecialchars($agri['nom_coop']); ?></td>
                                        <td>
                                            <a href="?action=edit&id=<?php echo urlencode($agri['id_agri']); ?>" class="btn btn-small btn-edit">Modifier</a>
                                            <form method="POST" class="form-delete-inline" onsubmit="return confirm('Êtes-vous sûr ?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id_agri_delete" value="<?php echo htmlspecialchars($agri['id_agri']); ?>">
                                                <button type="submit" class="btn btn-small btn-delete">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                <?php 
                    else:
                        echo '<p class="no-data">Aucun agriculteur enregistré.</p>';
                    endif;
                } catch (PDOException $e) {
                    echo '<div class="message-erreur">Erreur lors du chargement des agriculteurs.</div>';
                    error_log('Erreur SQL list agriculteur: ' . $e->getMessage());
                }
                ?>

            <!-- ========== FORMULAIRE CREATION ========== -->
            <?php elseif ($action === 'create'): ?>
                
                <a href="?action=list" class="btn btn-secondary">← Retour à la liste</a>

                <form method="POST" class="form-container">
                    <h3>Créer un nouvel agriculteur</h3>

                    <div class="form-group">
                        <label for="nom">Nom</label>
                        <input type="text" id="nom" name="nom" required>
                    </div>

                    <div class="form-group">
                        <label for="prenom">Prénom</label>
                        <input type="text" id="prenom" name="prenom" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="contact_agri">Contact (téléphone)</label>
                        <input type="text" id="contact_agri" name="contact_agri" placeholder="+228 XX XX XXXX" required>
                    </div>

                    <div class="form-group">
                        <label for="id_coop">Coopérative</label>
                        <select id="id_coop" name="id_coop" required>
                            <option value="">-- Sélectionner une coopérative --</option>
                            <?php
                            try {
                                $stmtCoops = $pdo->query("SELECT id_coop, nom_coop FROM COOPERATIVE ORDER BY nom_coop");
                                foreach ($stmtCoops->fetchAll() as $coop):
                            ?>
                                <option value="<?php echo htmlspecialchars($coop['id_coop']); ?>">
                                    <?php echo htmlspecialchars($coop['nom_coop']); ?>
                                </option>
                            <?php 
                                endforeach;
                            } catch (PDOException $e) {
                                echo '<option disabled>Erreur lors du chargement</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="mot_de_passe">Mot de passe initial</label>
                        <input type="password" id="mot_de_passe" name="mot_de_passe" required>
                    </div>

                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <button type="submit" class="btn btn-primary">Créer</button>
                </form>

            <!-- ========== FORMULAIRE MODIFICATION ========== -->
            <?php elseif ($action === 'edit' && $idAgri): ?>
                
                <a href="?action=list" class="btn btn-secondary">← Retour à la liste</a>

                <?php
                try {
                    $stmtEdit = $pdo->prepare(
                        "SELECT a.id_agri, u.nom, u.prenom, u.email, a.contact_agri, a.id_coop
                         FROM AGRICULTEUR a
                         JOIN UTILISATEUR u ON a.id_agri = u.id_utilisateur
                         WHERE a.id_agri = ?"
                    );
                    $stmtEdit->execute([$idAgri]);
                    $agriEdit = $stmtEdit->fetch();

                    if ($agriEdit):
                ?>
                        <form method="POST" class="form-container">
                            <h3>Modifier l'agriculteur</h3>

                            <div class="form-group">
                                <label for="id_agri_display">ID (lecture seule)</label>
                                <input type="text" id="id_agri_display" value="<?php echo htmlspecialchars($agriEdit['id_agri']); ?>" disabled>
                                <input type="hidden" name="id_agri" value="<?php echo htmlspecialchars($agriEdit['id_agri']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="nom">Nom</label>
                                <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($agriEdit['nom']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="prenom">Prénom</label>
                                <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($agriEdit['prenom']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($agriEdit['email']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="contact_agri">Contact (téléphone)</label>
                                <input type="text" id="contact_agri" name="contact_agri" value="<?php echo htmlspecialchars($agriEdit['contact_agri']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="id_coop">Coopérative</label>
                                <select id="id_coop" name="id_coop" required>
                                    <?php
                                    $stmtCoops = $pdo->query("SELECT id_coop, nom_coop FROM COOPERATIVE ORDER BY nom_coop");
                                    foreach ($stmtCoops->fetchAll() as $coop):
                                        $selected = ($coop['id_coop'] === $agriEdit['id_coop']) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo htmlspecialchars($coop['id_coop']); ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($coop['nom_coop']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <button type="submit" class="btn btn-primary">Modifier</button>
                        </form>
                <?php 
                    else:
                        echo '<div class="message-erreur">Agriculteur non trouvé.</div>';
                    endif;
                } catch (PDOException $e) {
                    echo '<div class="message-erreur">Erreur lors du chargement.</div>';
                    error_log('Erreur SQL edit agriculteur: ' . $e->getMessage());
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