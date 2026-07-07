<?php
/**
 * admin/responsables.php
 * Gestion des responsables de coopératives
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('admin');

$message = '';
$erreur = '';
$action = $_GET['action'] ?? 'list';
$idResponsable = $_GET['id'] ?? null;

// ==================== TRAITEMENT DES ACTIONS ====================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if (!verifierCsrf()) {
        $erreur = 'Erreur de sécurité (CSRF).';
    } else {
        
        $actionForm = nettoyer($_POST['action']);
        
        // ===== CREATION D'UN RESPONSABLE =====
        if ($actionForm === 'create') {
            
            $nom = nettoyer($_POST['nom'] ?? '');
            $prenom = nettoyer($_POST['prenom'] ?? '');
            $email = nettoyer($_POST['email'] ?? '');
            $motDePasse = $_POST['mot_de_passe'] ?? '';
            $idCoop = nettoyer($_POST['id_coop'] ?? '');
            
            if (empty($nom) || empty($prenom) || empty($email) || empty($motDePasse) || empty($idCoop)) {
                $erreur = 'Tous les champs sont obligatoires.';
            } elseif (mb_strlen($nom) > 50 || mb_strlen($prenom) > 50) {
                $erreur = 'Le nom et le prénom ne doivent pas dépasser 50 caractères.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erreur = 'Adresse email invalide.';
            } elseif (mb_strlen($motDePasse) < 6) {
                $erreur = 'Le mot de passe doit contenir au moins 6 caractères.';
            } else {
                try {
                    // Vérifier que l'email n'existe pas déjà
                    $stmt = $pdo->prepare("SELECT id_utilisateur FROM UTILISATEUR WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $erreur = 'Cet email est déjà utilisé.';
                    } else {
                        // Vérifier que la coopérative existe
                        $stmt = $pdo->prepare("SELECT id_coop FROM COOPERATIVE WHERE id_coop = ?");
                        $stmt->execute([$idCoop]);
                        if (!$stmt->fetch()) {
                            $erreur = 'Coopérative non trouvée.';
                        } else {
                            // Générer l'ID du responsable
                            $idResponsableNouv = genererCodeAvecParent($pdo, 'RESPONSABLE', 'id_responsable', 'RES', $idCoop);
                            $hash = password_hash($motDePasse, PASSWORD_DEFAULT);
                            
                            // Insérer dans UTILISATEUR
                            $stmt = $pdo->prepare(
                                "INSERT INTO UTILISATEUR (id_utilisateur, nom, prenom, email, mot_de_passe, role) 
                                 VALUES (?, ?, ?, ?, ?, 'responsable')"
                            );
                            $stmt->execute([$idResponsableNouv, $nom, $prenom, $email, $hash]);
                            
                            // Insérer dans RESPONSABLE
                            $stmt = $pdo->prepare(
                                "INSERT INTO RESPONSABLE (id_responsable, id_coop) VALUES (?, ?)"
                            );
                            $stmt->execute([$idResponsableNouv, $idCoop]);
                            
                            $message = "Responsable créé avec succès (ID: $idResponsableNouv)";
                            $action = 'list';
                        }
                    }
                } catch (PDOException $e) {
                    $erreur = 'Erreur lors de la création.';
                    error_log('Erreur SQL create responsable: ' . $e->getMessage());
                }
            }
        }
        
        // ===== MODIFICATION D'UN RESPONSABLE =====
        elseif ($actionForm === 'update') {
            
            $idResponsableUpdate = nettoyer($_POST['id_responsable'] ?? '');
            $nom = nettoyer($_POST['nom'] ?? '');
            $prenom = nettoyer($_POST['prenom'] ?? '');
            $email = nettoyer($_POST['email'] ?? '');
            $idCoop = nettoyer($_POST['id_coop'] ?? '');
            
            if (empty($idResponsableUpdate) || empty($nom) || empty($prenom) || empty($email) || empty($idCoop)) {
                $erreur = 'Tous les champs sont obligatoires.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erreur = 'Adresse email invalide.';
            } else {
                try {
                    // Vérifier que l'email n'est pas déjà utilisé par un autre utilisateur
                    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM UTILISATEUR WHERE email = ? AND id_utilisateur != ?");
                    $stmt->execute([$email, $idResponsableUpdate]);
                    if ($stmt->fetch()['total'] > 0) {
                        $erreur = 'Cet email est déjà utilisé par un autre utilisateur.';
                    } else {
                        // Vérifier que la coopérative existe
                        $stmt = $pdo->prepare("SELECT id_coop FROM COOPERATIVE WHERE id_coop = ?");
                        $stmt->execute([$idCoop]);
                        if (!$stmt->fetch()) {
                            $erreur = 'Coopérative non trouvée.';
                        } else {
                            // Mettre à jour UTILISATEUR
                            $stmt = $pdo->prepare(
                                "UPDATE UTILISATEUR SET nom = ?, prenom = ?, email = ? WHERE id_utilisateur = ?"
                            );
                            $stmt->execute([$nom, $prenom, $email, $idResponsableUpdate]);
                            
                            // Mettre à jour RESPONSABLE
                            $stmt = $pdo->prepare(
                                "UPDATE RESPONSABLE SET id_coop = ? WHERE id_responsable = ?"
                            );
                            $stmt->execute([$idCoop, $idResponsableUpdate]);
                            
                            $message = "Responsable modifié avec succès";
                            $action = 'list';
                        }
                    }
                } catch (PDOException $e) {
                    $erreur = 'Erreur lors de la modification.';
                    error_log('Erreur SQL update responsable: ' . $e->getMessage());
                }
            }
        }
        
        // ===== SUPPRESSION D'UN RESPONSABLE =====
        elseif ($actionForm === 'delete') {
            
            $idResponsableDelete = nettoyer($_POST['id_responsable_delete'] ?? '');
            
            try {
                // Vérifier que le responsable n'est pas l'utilisateur connecté
                if ($idResponsableDelete == $_SESSION['id_utilisateur']) {
                    $erreur = 'Vous ne pouvez pas supprimer votre propre compte.';
                } else {
                    // Supprimer d'abord de RESPONSABLE
                    $stmt = $pdo->prepare("DELETE FROM RESPONSABLE WHERE id_responsable = ?");
                    $stmt->execute([$idResponsableDelete]);
                    
                    // Supprimer de UTILISATEUR
                    $stmt = $pdo->prepare("DELETE FROM UTILISATEUR WHERE id_utilisateur = ?");
                    $stmt->execute([$idResponsableDelete]);
                    
                    $message = "Responsable supprimé avec succès";
                    $action = 'list';
                }
            } catch (PDOException $e) {
                $erreur = 'Erreur lors de la suppression.';
                error_log('Erreur SQL delete responsable: ' . $e->getMessage());
            }
        }
    }
}

initialiserCsrf();

// ==================== RECUPERATION DES DONNEES ====================

// Récupérer toutes les coopératives
$coops = $pdo->query("SELECT id_coop, nom_coop FROM COOPERATIVE ORDER BY nom_coop")->fetchAll();

// Récupérer tous les responsables avec leurs coopératives
$responsables = $pdo->query("
    SELECT r.id_responsable, u.nom, u.prenom, u.email, c.nom_coop, c.id_coop
    FROM RESPONSABLE r
    JOIN UTILISATEUR u ON r.id_responsable = u.id_utilisateur
    JOIN COOPERATIVE c ON r.id_coop = c.id_coop
    ORDER BY u.nom, u.prenom
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Responsables - AgriGest Togo</title>
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
                    <a href="responsables.php"><i class="fas fa-user-cog"></i> Responsables</a>
                    <a href="cultures.php"><i class="fas fa-sprout"></i> Cultures</a>
                    <a href="cooperatives.php"><i class="fas fa-handshake"></i> Coopératives</a>
                    <a href="zones.php"><i class="fas fa-map"></i> Zones</a>
                    <a href="intrants.php"><i class="fas fa-flask"></i> Intrants</a>
                    <a href="saisons.php"><i class="fas fa-cloud-sun"></i> Saisons</a>
                    <a href="../profil.php"><i class="fas fa-user-circle"></i> Mon profil</a>
                    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                </nav>
            </div>
        </header>

        <main>
            <h2><i class="fas fa-user-cog"></i> Gestion des Responsables</h2>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erreur); ?></div>
            <?php endif; ?>

            <!-- ========== LISTE DES RESPONSABLES ========== -->
            <?php if ($action === 'list'): ?>
                
                <div class="actions-bar">
                    <a href="?action=create" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter un responsable</a>
                </div>

                <?php if (empty($responsables)): ?>
                    <p class="no-data">Aucun responsable enregistré.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Email</th>
                                <th>Coopérative</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($responsables as $responsable): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($responsable['id_responsable']); ?></td>
                                    <td><?php echo htmlspecialchars($responsable['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($responsable['prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($responsable['email']); ?></td>
                                    <td><?php echo htmlspecialchars($responsable['nom_coop']); ?></td>
                                    <td>
                                        <a href="?action=edit&id=<?php echo urlencode($responsable['id_responsable']); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i> Modifier</a>
                                        <?php if ($responsable['id_responsable'] != $_SESSION['id_utilisateur']): ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce responsable ?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id_responsable_delete" value="<?php echo htmlspecialchars($responsable['id_responsable']); ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Supprimer</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge badge-info"><i class="fas fa-user"></i> Vous</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

            <!-- ========== FORMULAIRE CREATION ========== -->
            <?php elseif ($action === 'create'): ?>
                
                <a href="?action=list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour à la liste</a>

                <div class="card" style="max-width: 600px; margin: 0 auto; margin-top: var(--spacing-md);">
                    <h3><i class="fas fa-user-plus"></i> Créer un nouveau responsable</h3>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="create">

                        <div class="form-group">
                            <label for="nom"><i class="fas fa-user"></i> Nom *</label>
                            <input type="text" id="nom" name="nom" placeholder="Ex: Kouassi" required>
                        </div>

                        <div class="form-group">
                            <label for="prenom"><i class="fas fa-user"></i> Prénom *</label>
                            <input type="text" id="prenom" name="prenom" placeholder="Ex: Jean" required>
                        </div>

                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email *</label>
                            <input type="email" id="email" name="email" placeholder="jean.kouassi@example.com" required>
                        </div>

                        <div class="form-group">
                            <label for="mot_de_passe"><i class="fas fa-lock"></i> Mot de passe *</label>
                            <input type="password" id="mot_de_passe" name="mot_de_passe" placeholder="Minimum 6 caractères" minlength="6" required>
                            <p class="form-help">Minimum 6 caractères</p>
                        </div>

                        <div class="form-group">
                            <label for="id_coop"><i class="fas fa-handshake"></i> Coopérative *</label>
                            <select id="id_coop" name="id_coop" required>
                                <option value="">-- Sélectionner une coopérative --</option>
                                <?php foreach ($coops as $coop): ?>
                                    <option value="<?php echo htmlspecialchars($coop['id_coop']); ?>">
                                        <?php echo htmlspecialchars($coop['nom_coop']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Créer</button>
                            <a href="?action=list" class="btn btn-secondary"><i class="fas fa-times"></i> Annuler</a>
                        </div>
                    </form>
                </div>

            <!-- ========== FORMULAIRE MODIFICATION ========== -->
            <?php elseif ($action === 'edit' && $idResponsable): ?>
                
                <a href="?action=list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour à la liste</a>

                <?php
                try {
                    $stmtEdit = $pdo->prepare("
                        SELECT r.id_responsable, u.nom, u.prenom, u.email, r.id_coop
                        FROM RESPONSABLE r
                        JOIN UTILISATEUR u ON r.id_responsable = u.id_utilisateur
                        WHERE r.id_responsable = ?
                    ");
                    $stmtEdit->execute([$idResponsable]);
                    $responsableEdit = $stmtEdit->fetch();

                    if (!$responsableEdit):
                ?>
                        <div class="alert alert-danger">Responsable non trouvé.</div>
                <?php 
                    else:
                ?>
                        <div class="card" style="max-width: 600px; margin: 0 auto; margin-top: var(--spacing-md);">
                            <h3><i class="fas fa-user-edit"></i> Modifier le responsable</h3>

                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id_responsable" value="<?php echo htmlspecialchars($responsableEdit['id_responsable']); ?>">

                                <div class="form-group">
                                    <label for="id_responsable_display"><i class="fas fa-id-card"></i> ID (lecture seule)</label>
                                    <input type="text" id="id_responsable_display" value="<?php echo htmlspecialchars($responsableEdit['id_responsable']); ?>" disabled>
                                </div>

                                <div class="form-group">
                                    <label for="nom"><i class="fas fa-user"></i> Nom *</label>
                                    <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($responsableEdit['nom']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="prenom"><i class="fas fa-user"></i> Prénom *</label>
                                    <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($responsableEdit['prenom']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="email"><i class="fas fa-envelope"></i> Email *</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($responsableEdit['email']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="id_coop"><i class="fas fa-handshake"></i> Coopérative *</label>
                                    <select id="id_coop" name="id_coop" required>
                                        <?php foreach ($coops as $coop): ?>
                                            <option value="<?php echo htmlspecialchars($coop['id_coop']); ?>"
                                                <?php echo ($coop['id_coop'] === $responsableEdit['id_coop']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($coop['nom_coop']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> 
                                    Pour changer le mot de passe, l'utilisateur doit utiliser la page "Changer mot de passe".
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                                    <a href="?action=list" class="btn btn-secondary"><i class="fas fa-times"></i> Annuler</a>
                                </div>
                            </form>
                        </div>
                <?php 
                    endif;
                } catch (PDOException $e) {
                    echo '<div class="alert alert-danger">Erreur lors du chargement.</div>';
                    error_log('Erreur SQL edit responsable: ' . $e->getMessage());
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