<?php
/**
 * responsable/modifier_agriculteur.php
 * Modification d'un agriculteur
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('responsable');

$id_agri = $_GET['id'] ?? null;
$erreur = '';
$message = '';
$agriculteur = null;

if (!$id_agri) {
    header('Location: agriculteurs.php');
    exit;
}

try {
    // Récupérer les informations de l'agriculteur
    $stmt = $pdo->prepare(
        "SELECT a.id_agri, a.contact_agri, u.nom, u.prenom, u.email, a.id_coop
         FROM AGRICULTEUR a
         JOIN UTILISATEUR u ON a.id_agri = u.id_utilisateur
         WHERE a.id_agri = ?"
    );
    $stmt->execute([$id_agri]);
    $agriculteur = $stmt->fetch();

    if (!$agriculteur) {
        $erreur = 'Agriculteur non trouvé.';
    }

} catch (PDOException $e) {
    error_log('Erreur SQL modifier_agriculteur: ' . $e->getMessage());
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
        $contact_agri = nettoyer($_POST['contact_agri'] ?? '');
        
        if (empty($nom) || empty($prenom) || empty($email) || empty($contact_agri)) {
            $erreur = 'Tous les champs sont obligatoires.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreur = 'Email invalide.';
        } else {
            try {
                // Vérifier que l'email n'est pas déjà utilisé
                $stmt = $pdo->prepare("SELECT id_utilisateur FROM UTILISATEUR WHERE email = ? AND id_utilisateur != ?");
                $stmt->execute([$email, $id_agri]);
                if ($stmt->fetch()) {
                    $erreur = 'Cet email est déjà utilisé par un autre utilisateur.';
                } else {
                    // Mettre à jour UTILISATEUR
                    $stmt = $pdo->prepare(
                        "UPDATE UTILISATEUR SET nom = ?, prenom = ?, email = ? WHERE id_utilisateur = ?"
                    );
                    $stmt->execute([$nom, $prenom, $email, $id_agri]);

                    // Mettre à jour AGRICULTEUR
                    $stmt = $pdo->prepare(
                        "UPDATE AGRICULTEUR SET contact_agri = ? WHERE id_agri = ?"
                    );
                    $stmt->execute([$contact_agri, $id_agri]);

                    $message = 'Agriculteur modifié avec succès !';
                    
                    // Recharger les données
                    $stmt = $pdo->prepare(
                        "SELECT a.id_agri, a.contact_agri, u.nom, u.prenom, u.email, a.id_coop
                         FROM AGRICULTEUR a
                         JOIN UTILISATEUR u ON a.id_agri = u.id_utilisateur
                         WHERE a.id_agri = ?"
                    );
                    $stmt->execute([$id_agri]);
                    $agriculteur = $stmt->fetch();
                }
            } catch (PDOException $e) {
                error_log('Erreur modification agriculteur: ' . $e->getMessage());
                $erreur = 'Erreur lors de la modification.';
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
    <title>Modifier Agriculteur - AgriGest Togo</title>
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
                <h2>Modifier un agriculteur</h2>
                <p><a href="agriculteurs.php">&larr; Retour à la liste</a></p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erreur); ?></div>
            <?php endif; ?>

            <?php if ($agriculteur): ?>

            <div class="card">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="id_agri" value="<?php echo htmlspecialchars($agriculteur['id_agri']); ?>">

                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($agriculteur['nom']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="prenom">Prénom *</label>
                        <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($agriculteur['prenom']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($agriculteur['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="contact_agri">Contact (téléphone) *</label>
                        <input type="tel" id="contact_agri" name="contact_agri" value="<?php echo htmlspecialchars($agriculteur['contact_agri']); ?>" required>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                        <a href="agriculteurs.php" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>

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