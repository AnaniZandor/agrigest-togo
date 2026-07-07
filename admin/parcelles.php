<?php
/**
 * admin/parcelles.php
 * Gestion des parcelles par l'administrateur
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('admin');

$message = '';
$erreur = '';
$action = $_GET['action'] ?? 'list';
$idParcelle = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if (!verifierCsrf()) {
        $erreur = 'Erreur de sécurité (CSRF).';
    } else {
        
        $actionForm = nettoyer($_POST['action']);
        
        if ($actionForm === 'create') {
            
            $nomParcelle = nettoyer($_POST['nom_parcelle'] ?? '');
            $localisationParcelle = nettoyer($_POST['localisation_parcelle'] ?? '');
            $superficie = $_POST['superficie'] ?? '';
            $idZone = nettoyer($_POST['id_zone'] ?? '');
            $idAgri = nettoyer($_POST['id_agri'] ?? '');
            
            if (empty($nomParcelle) || empty($localisationParcelle) || empty($superficie) || empty($idZone) || empty($idAgri)) {
                $erreur = 'Tous les champs sont obligatoires.';
            } elseif (!is_numeric($superficie) || $superficie <= 0) {
                $erreur = 'La superficie doit être un nombre positif.';
            } else {
                try {
                    $stmtZone = $pdo->prepare("SELECT id_zone FROM ZONE_AGROECOLOGIQUE WHERE id_zone = ?");
                    $stmtZone->execute([$idZone]);
                    if (!$stmtZone->fetch()) {
                        $erreur = 'Zone agroécologique non trouvée.';
                    } elseif (!$pdo->prepare("SELECT id_agri FROM AGRICULTEUR WHERE id_agri = ?")->execute([$idAgri]) || !$pdo->prepare("SELECT id_agri FROM AGRICULTEUR WHERE id_agri = ?")->fetch()) {
                        $erreur = 'Agriculteur non trouvé.';
                    } else {
                        $idParcelleNouv = genererCodeAvecParent($pdo, 'PARCELLE', 'id_parcelle', 'PAR', $idZone);
                        
                        $stmt = $pdo->prepare(
                            "INSERT INTO PARCELLE (id_parcelle, nom_parcelle, localisation_parcelle, superficie, id_zone, id_agri)
                             VALUES (?, ?, ?, ?, ?, ?)"
                        );
                        $stmt->execute([$idParcelleNouv, $nomParcelle, $localisationParcelle, $superficie, $idZone, $idAgri]);
                        
                        $message = "Parcelle créée avec succès (ID: $idParcelleNouv)";
                        $action = 'list';
                    }
                    
                } catch (PDOException $e) {
                    $erreur = 'Erreur lors de la création.';
                    error_log('Erreur SQL create parcelle: ' . $e->getMessage());
                }
            }
        }
        
        elseif ($actionForm === 'update') {
            
            $idParcelleUpdate = nettoyer($_POST['id_parcelle'] ?? '');
            $nomParcelle = nettoyer($_POST['nom_parcelle'] ?? '');
            $localisationParcelle = nettoyer($_POST['localisation_parcelle'] ?? '');
            $superficie = $_POST['superficie'] ?? '';
            $idZone = nettoyer($_POST['id_zone'] ?? '');
            $idAgri = nettoyer($_POST['id_agri'] ?? '');
            
            if (empty($idParcelleUpdate) || empty($nomParcelle) || empty($localisationParcelle) || empty($superficie) || empty($idZone) || empty($idAgri)) {
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
                    $stmt->execute([$nomParcelle, $localisationParcelle, $superficie, $idZone, $idAgri, $idParcelleUpdate]);
                    
                    $message = "Parcelle modifiée avec succès";
                    $action = 'list';
                    
                } catch (PDOException $e) {
                    $erreur = 'Erreur lors de la modification.';
                    error_log('Erreur SQL update parcelle: ' . $e->getMessage());
                }
            }
        }
        
        elseif ($actionForm === 'delete') {
            
            $idParcelleDelete = nettoyer($_POST['id_parcelle_delete'] ?? '');
            
            try {
                $pdo->prepare("DELETE FROM UTILISER WHERE id_plantation IN (SELECT id_plantation FROM PLANTATION WHERE id_parcelle = ?)")->execute([$idParcelleDelete]);
                $pdo->prepare("DELETE FROM RECOLTE WHERE id_plantation IN (SELECT id_plantation FROM PLANTATION WHERE id_parcelle = ?)")->execute([$idParcelleDelete]);
                $pdo->prepare("DELETE FROM PLANTATION WHERE id_parcelle = ?")->execute([$idParcelleDelete]);
                
                $pdo->prepare("DELETE FROM PARCELLE WHERE id_parcelle = ?")->execute([$idParcelleDelete]);
                
                $message = "Parcelle supprimée avec succès";
                $action = 'list';
                
            } catch (PDOException $e) {
                $erreur = 'Erreur lors de la suppression.';
                error_log('Erreur SQL delete parcelle: ' . $e->getMessage());
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
    <title>Gestion des Parcelles - AgriGest Togo</title>
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
            <h2>Gestion des Parcelles</h2>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erreur); ?></div>
            <?php endif; ?>

            <?php if ($action === 'list'): ?>
                
                <div class="actions-bar">
                    <a href="?action=create" class="btn btn-primary">+ Ajouter une parcelle</a>
                </div>

                <?php
                try {
                    $stmt = $pdo->query(
                        "SELECT p.id_parcelle, p.nom_parcelle, p.localisation_parcelle, p.superficie, z.nom_zone, u.nom, u.prenom
                         FROM PARCELLE p
                         JOIN ZONE_AGROECOLOGIQUE z ON p.id_zone = z.id_zone
                         JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
                         JOIN UTILISATEUR u ON a.id_agri = u.id_utilisateur
                         ORDER BY p.nom_parcelle"
                    );
                    $parcelles = $stmt->fetchAll();
                    
                    if (!empty($parcelles)):
                ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Localisation</th>
                                    <th>Superficie (ha)</th>
                                    <th>Zone</th>
                                    <th>Agriculteur</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($parcelles as $parcelle): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($parcelle['id_parcelle']); ?></td>
                                        <td><?php echo htmlspecialchars($parcelle['nom_parcelle']); ?></td>
                                        <td><?php echo htmlspecialchars($parcelle['localisation_parcelle']); ?></td>
                                        <td><?php echo formaterNombre($parcelle['superficie'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($parcelle['nom_zone']); ?></td>
                                        <td><?php echo htmlspecialchars($parcelle['nom'] . ' ' . $parcelle['prenom']); ?></td>
                                        <td>
                                            <a href="?action=edit&id=<?php echo urlencode($parcelle['id_parcelle']); ?>" class="btn btn-secondary">Modifier</a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id_parcelle_delete" value="<?php echo htmlspecialchars($parcelle['id_parcelle']); ?>">
                                                <button type="submit" class="btn btn-danger">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                <?php 
                    else:
                        echo '<p class="no-data">Aucune parcelle enregistrée.</p>';
                    endif;
                } catch (PDOException $e) {
                    echo '<div class="alert alert-danger">Erreur lors du chargement des parcelles.</div>';
                    error_log('Erreur SQL list parcelle: ' . $e->getMessage());
                }
                ?>

            <?php elseif ($action === 'create'): ?>
                
                <a href="?action=list" class="btn btn-secondary">← Retour à la liste</a>

                <form method="POST" class="form-container">
                    <h3>Créer une nouvelle parcelle</h3>

                    <div class="form-group">
                        <label for="nom_parcelle">Nom de la parcelle</label>
                        <input type="text" id="nom_parcelle" name="nom_parcelle" required>
                    </div>

                    <div class="form-group">
                        <label for="localisation_parcelle">Localisation</label>
                        <input type="text" id="localisation_parcelle" name="localisation_parcelle" placeholder="Ex: Km 8, Route de Kévé..." required>
                    </div>

                    <div class="form-group">
                        <label for="superficie">Superficie (hectares)</label>
                        <input type="number" id="superficie" name="superficie" placeholder="Ex: 2.5" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="id_zone">Zone agroécologique</label>
                        <select id="id_zone" name="id_zone" required>
                            <option value="">-- Sélectionner une zone --</option>
                            <?php
                            try {
                                $stmtZones = $pdo->query("SELECT id_zone, nom_zone FROM ZONE_AGROECOLOGIQUE ORDER BY nom_zone");
                                foreach ($stmtZones->fetchAll() as $zone):
                            ?>
                                <option value="<?php echo htmlspecialchars($zone['id_zone']); ?>">
                                    <?php echo htmlspecialchars($zone['nom_zone']); ?>
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
                        <label for="id_agri">Agriculteur propriétaire</label>
                        <select id="id_agri" name="id_agri" required>
                            <option value="">-- Sélectionner un agriculteur --</option>
                            <?php
                            try {
                                $stmtAgris = $pdo->query(
                                    "SELECT a.id_agri, u.nom, u.prenom 
                                     FROM AGRICULTEUR a
                                     JOIN UTILISATEUR u ON a.id_agri = u.id_utilisateur
                                     ORDER BY u.nom, u.prenom"
                                );
                                foreach ($stmtAgris->fetchAll() as $agri):
                            ?>
                                <option value="<?php echo htmlspecialchars($agri['id_agri']); ?>">
                                    <?php echo htmlspecialchars($agri['nom'] . ' ' . $agri['prenom']); ?>
                                </option>
                            <?php 
                                endforeach;
                            } catch (PDOException $e) {
                                echo '<option disabled>Erreur lors du chargement</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <button type="submit" class="btn btn-primary">Créer</button>
                </form>

            <?php elseif ($action === 'edit' && $idParcelle): ?>
                
                <a href="?action=list" class="btn btn-secondary">← Retour à la liste</a>

                <?php
                try {
                    $stmtEdit = $pdo->prepare(
                        "SELECT id_parcelle, nom_parcelle, localisation_parcelle, superficie, id_zone, id_agri
                         FROM PARCELLE
                         WHERE id_parcelle = ?"
                    );
                    $stmtEdit->execute([$idParcelle]);
                    $parcelleEdit = $stmtEdit->fetch();

                    if ($parcelleEdit):
                ?>
                        <form method="POST" class="form-container">
                            <h3>Modifier la parcelle</h3>

                            <div class="form-group">
                                <label for="id_parcelle_display">ID (lecture seule)</label>
                                <input type="text" id="id_parcelle_display" value="<?php echo htmlspecialchars($parcelleEdit['id_parcelle']); ?>" disabled>
                                <input type="hidden" name="id_parcelle" value="<?php echo htmlspecialchars($parcelleEdit['id_parcelle']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="nom_parcelle">Nom de la parcelle</label>
                                <input type="text" id="nom_parcelle" name="nom_parcelle" value="<?php echo htmlspecialchars($parcelleEdit['nom_parcelle']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="localisation_parcelle">Localisation</label>
                                <input type="text" id="localisation_parcelle" name="localisation_parcelle" value="<?php echo htmlspecialchars($parcelleEdit['localisation_parcelle']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="superficie">Superficie (hectares)</label>
                                <input type="number" id="superficie" name="superficie" value="<?php echo htmlspecialchars($parcelleEdit['superficie']); ?>" step="0.01" min="0" required>
                            </div>

                            <div class="form-group">
                                <label for="id_zone">Zone agroécologique</label>
                                <select id="id_zone" name="id_zone" required>
                                    <?php
                                    $stmtZones = $pdo->query("SELECT id_zone, nom_zone FROM ZONE_AGROECOLOGIQUE ORDER BY nom_zone");
                                    foreach ($stmtZones->fetchAll() as $zone):
                                        $selected = ($zone['id_zone'] === $parcelleEdit['id_zone']) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo htmlspecialchars($zone['id_zone']); ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($zone['nom_zone']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="id_agri">Agriculteur propriétaire</label>
                                <select id="id_agri" name="id_agri" required>
                                    <?php
                                    $stmtAgris = $pdo->query(
                                        "SELECT a.id_agri, u.nom, u.prenom 
                                         FROM AGRICULTEUR a
                                         JOIN UTILISATEUR u ON a.id_agri = u.id_utilisateur
                                         ORDER BY u.nom, u.prenom"
                                    );
                                    foreach ($stmtAgris->fetchAll() as $agri):
                                        $selected = ($agri['id_agri'] === $parcelleEdit['id_agri']) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo htmlspecialchars($agri['id_agri']); ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($agri['nom'] . ' ' . $agri['prenom']); ?>
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
                        echo '<div class="alert alert-danger">Parcelle non trouvée.</div>';
                    endif;
                } catch (PDOException $e) {
                    echo '<div class="alert alert-danger">Erreur lors du chargement.</div>';
                    error_log('Erreur SQL edit parcelle: ' . $e->getMessage());
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