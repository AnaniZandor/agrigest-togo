<?php
/**
 * responsable/consulter_recolte.php
 * Consultation détaillée d'une récolte
 */

session_start();
require_once('../config/connexion.php');
require_once('../includes/fonctions.php');

exigerRole('responsable');

$id_recolte = $_GET['id'] ?? null;
$erreur = '';
$recolte = null;

if (!$id_recolte) {
    header('Location: recoltes.php');
    exit;
}

try {
    // Récupérer les informations de la récolte
    $stmt = $pdo->prepare(
        "SELECT r.id_recolte, r.date_recolte, r.rendement,
                c.nom_culture, p.nom_parcelle, s.libelle_saison,
                u.nom, u.prenom, a.id_agri, pl.date_semis
         FROM RECOLTE r
         JOIN PLANTATION pl ON r.id_plantation = pl.id_plantation
         JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
         JOIN CULTURE c ON pl.id_culture = c.id_culture
         JOIN SAISON s ON pl.id_saison = s.id_saison
         JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
         JOIN UTILISATEUR u ON a.id_agri = u.id_utilisateur
         WHERE r.id_recolte = ?"
    );
    $stmt->execute([$id_recolte]);
    $recolte = $stmt->fetch();

    if (!$recolte) {
        $erreur = 'Récolte non trouvée.';
    }

} catch (PDOException $e) {
    error_log('Erreur SQL consulter_recolte: ' . $e->getMessage());
    $erreur = 'Erreur lors du chargement des données.';
}

initialiserCsrf();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulter Récolte - AgriGest Togo</title>
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
                <h2>Détails de la récolte</h2>
                <p><a href="recoltes.php">&larr; Retour à la liste</a></p>
            </div>

            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erreur); ?></div>
            <?php elseif ($recolte): ?>

            <!-- ===== INFORMATIONS DE LA RÉCOLTE ===== -->
            <div class="card" style="margin-bottom: 30px;">
                <h3>Informations de la récolte</h3>
                <div class="info-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:var(--spacing-md);">
                    <p><strong>ID :</strong> <?php echo htmlspecialchars($recolte['id_recolte']); ?></p>
                    <p><strong>Culture :</strong> <?php echo htmlspecialchars($recolte['nom_culture']); ?></p>
                    <p><strong>Parcelle :</strong> <?php echo htmlspecialchars($recolte['nom_parcelle']); ?></p>
                    <p><strong>Saison :</strong> <?php echo htmlspecialchars($recolte['libelle_saison']); ?></p>
                    <p><strong>Date de semis :</strong> <?php echo formaterDate($recolte['date_semis']); ?></p>
                    <p><strong>Date de récolte :</strong> <?php echo formaterDate($recolte['date_recolte']); ?></p>
                    <p><strong>Rendement :</strong> <?php echo formaterNombre($recolte['rendement'], 2); ?> kg</p>
                    <p><strong>Agriculteur :</strong> <?php echo htmlspecialchars($recolte['prenom'] . ' ' . $recolte['nom']); ?></p>
                </div>
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