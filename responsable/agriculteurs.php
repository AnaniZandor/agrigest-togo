<?php
/**
 * responsable/agriculteurs.php
 * Gestion des agriculteurs de la coopérative
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('responsable');

$id_responsable = $_SESSION['id_utilisateur'];
$id_coop = '';
$agriculteurs = [];
$action = $_GET['action'] ?? '';
$message = '';
$erreur = '';

try {
    // Récupérer la coopérative du responsable
    $stmt = $pdo->prepare(
        "SELECT id_coop FROM RESPONSABLE WHERE id_responsable = ?"
    );
    $stmt->execute([$id_responsable]);
    $responsable = $stmt->fetch();
    
    if (!$responsable) {
        $erreur = 'Erreur: coopérative non trouvée.';
    } else {
        $id_coop = $responsable['id_coop'];
        
        // Récupérer les agriculteurs de la coopérative
        $stmt = $pdo->prepare(
            "SELECT a.id_agri, u.nom, u.prenom, u.email, a.contact_agri
             FROM AGRICULTEUR a
             JOIN UTILISATEUR u ON a.id_agri = u.id_utilisateur
             WHERE a.id_coop = ?
             ORDER BY u.nom ASC, u.prenom ASC"
        );
        $stmt->execute([$id_coop]);
        $agriculteurs = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log('Erreur SQL agriculteurs: ' . $e->getMessage());
    $erreur = 'Erreur lors du chargement des données.';
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) {
        $erreur = 'Token CSRF invalide.';
    } else {
        $nom = nettoyer($_POST['nom'] ?? '');
        $prenom = nettoyer($_POST['prenom'] ?? '');
        $email = nettoyer($_POST['email'] ?? '');
        $mot_de_passe = $_POST['mot_de_passe'] ?? '';
        $contact_agri = nettoyer($_POST['contact_agri'] ?? '');
        
        // Validations
        if (empty($nom)) {
            $erreur = 'Le nom est obligatoire.';
        } elseif (empty($prenom)) {
            $erreur = 'Le prénom est obligatoire.';
        } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreur = 'Email invalide.';
        } elseif (empty($mot_de_passe) || strlen($mot_de_passe) < 6) {
            $erreur = 'Le mot de passe doit contenir au moins 6 caractères.';
        } elseif (empty($contact_agri)) {
            $erreur = 'Le contact est obligatoire.';
        } else {
            try {
                // Vérifier que l'email n'existe pas déjà
                $stmt = $pdo->prepare("SELECT id_utilisateur FROM UTILISATEUR WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $erreur = 'Cet email est déjà utilisé.';
                } else {
                    // Générer les IDs
                    $id_agri = genererCodeUtilisateur($pdo, 'agriculteur', $id_coop);
                    $hash_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                    
                    // Insérer dans UTILISATEUR
                    $stmt = $pdo->prepare(
                        "INSERT INTO UTILISATEUR (id_utilisateur, nom, prenom, email, mot_de_passe, role)
                         VALUES (?, ?, ?, ?, ?, 'agriculteur')"
                    );
                    $stmt->execute([$id_agri, $nom, $prenom, $email, $hash_password]);
                    
                    // Insérer dans AGRICULTEUR
                    $stmt = $pdo->prepare(
                        "INSERT INTO AGRICULTEUR (id_agri, contact_agri, id_coop)
                         VALUES (?, ?, ?)"
                    );
                    $stmt->execute([$id_agri, $contact_agri, $id_coop]);
                    
                    $message = 'Agriculteur ajouté avec succès ! (ID: ' . $id_agri . ')';
                    $_POST = [];
                    
                    // Recharger la liste
                    $stmt = $pdo->prepare(
                        "SELECT a.id_agri, u.nom, u.prenom, u.email, a.contact_agri
                         FROM AGRICULTEUR a
                         JOIN UTILISATEUR u ON a.id_agri = u.id_utilisateur
                         WHERE a.id_coop = ?
                         ORDER BY u.nom ASC, u.prenom ASC"
                    );
                    $stmt->execute([$id_coop]);
                    $agriculteurs = $stmt->fetchAll();
                }
            } catch (PDOException $e) {
                error_log('Erreur insertion agriculteur: ' . $e->getMessage());
                $erreur = 'Erreur lors de l\'enregistrement.';
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
    <title>Agriculteurs - AgriGest Togo</title>
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
                <h1>🌾 AgriGest Togo - Responsable</h1>
                <button class="hamburger" id="hamburgerBtn" aria-label="Menu principal" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <nav id="navMenu">
                    <a href="dashboard.php">Tableau de bord</a>
                    <a href="agriculteurs.php">Agriculteurs</a>
                    <a href="parcelles.php">Parcelles</a>
                    <a href="plantations.php">Plantations</a>
                    <a href="recoltes.php">Récoltes</a>
                    <a href="intrants.php">Intrants</a>
                    <a href="bilan.php">Bilan</a>
                    <a href="../auth/logout.php">Déconnexion</a>
                </nav>
            </div>
        </header>

        <main>
            <div class="dashboard-header">
                <h2>Gestion des agriculteurs</h2>
                <p>Agriculteurs de votre coopérative</p>
            </div>

            <!-- ========== MESSAGES ========== -->
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($erreur): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($erreur); ?>
                </div>
            <?php endif; ?>

            <!-- ========== FORMULAIRE AJOUT ========== -->
            <?php if ($action === 'create'): ?>
                <section class="management-section">
                    <h3>Ajouter un agriculteur</h3>
                    
                    <div class="form-container">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            
                            <div class="form-group">
                                <label for="nom">Nom *</label>
                                <input type="text" name="nom" id="nom" required
                                       value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>"
                                       placeholder="Ex: Kouassi">
                            </div>

                            <div class="form-group">
                                <label for="prenom">Prénom *</label>
                                <input type="text" name="prenom" id="prenom" required
                                       value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>"
                                       placeholder="Ex: Jean">
                            </div>

                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" name="email" id="email" required
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       placeholder="jean.kouassi@example.com">
                            </div>

                            <div class="form-group">
                                <label for="mot_de_passe">Mot de passe *</label>
                                <input type="password" name="mot_de_passe" id="mot_de_passe" required
                                       placeholder="Minimum 6 caractères">
                                <p class="form-help">Minimum 6 caractères</p>
                            </div>

                            <div class="form-group">
                                <label for="contact_agri">Contact (téléphone) *</label>
                                <input type="tel" name="contact_agri" id="contact_agri" required
                                       value="<?php echo htmlspecialchars($_POST['contact_agri'] ?? ''); ?>"
                                       placeholder="Ex: +228 91234567">
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                                <a href="agriculteurs.php" class="btn btn-secondary">Annuler</a>
                            </div>
                        </form>
                    </div>
                </section>
            <?php endif; ?>

            <!-- ========== LISTE DES AGRICULTEURS ========== -->
            <section class="management-section">
                <div class="section-header">
                    <h3>Liste des agriculteurs</h3>
                    <a href="agriculteurs.php?action=create" class="btn btn-primary">+ Ajouter un agriculteur</a>
                </div>

                <?php if (empty($agriculteurs)): ?>
                    <div class="empty-state">
                        <p>Aucun agriculteur enregistré dans votre coopérative.</p>
                        <a href="agriculteurs.php?action=create" class="btn btn-primary">Ajouter le premier agriculteur</a>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Email</th>
                                <th>Contact</th>
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
                                    <td>
                                        <a href="consulter_agriculteur.php?id=<?php echo urlencode($agri['id_agri']); ?>" class="btn btn-secondary btn-sm">Voir</a>
                                        <a href="modifier_agriculteur.php?id=<?php echo urlencode($agri['id_agri']); ?>" class="btn btn-secondary btn-sm">Modifier</a>
                                        <a href="supprimer_agriculteur.php?id=<?php echo urlencode($agri['id_agri']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr ?')">Supprimer</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

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