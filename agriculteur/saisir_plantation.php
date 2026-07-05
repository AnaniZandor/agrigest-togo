<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['id_agri']) || $_SESSION['role'] !== 'agriculteur') {
    header('Location: ../auth/login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verifierCsrf()
{
    return isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

$message = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_plantation'])) {
    if (!verifierCsrf()) {
        $erreur = "Session expirée, veuillez recharger la page.";
    } else {
        $idParcelle = nettoyer($_POST['id_parcelle']);
        $idCulture = nettoyer($_POST['id_culture']);
        $idSaison = nettoyer($_POST['id_saison']);
        $dateSemis = $_POST['date_semis'];

        // Verification
        if (empty($idParcelle) || empty($idCulture) || empty($idSaison) || empty($dateSemis)) {
            $erreur = "Veuillez remplir tous les champs.";
        } else {
            // Verification de sécurité : la parcelle appartient bien à CET agriculteur
            $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM PARCELLE WHERE id_parcelle = ? AND id_agri = ?");
            $stmt->execute([$idParcelle, $_SESSION['id_agri']]);
            if ($stmt->fetch()['total'] == 0) {
                $erreur = "Cette parcelle ne vous appartient pas.";
            } else {
                try {
                    $nouvelId = genererCodeSimple($pdo, 'PLANTATION', 'PLT');
                    $stmt = $pdo->prepare("INSERT INTO PLANTATION (id_plantation, date_semis, id_parcelle, id_culture, id_saison) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$nouvelId, $dateSemis, $idParcelle, $idCulture, $idSaison]);
                    $message = "Plantation enregistrée avec succès (code : $nouvelId).";
                } catch (PDOException $e) {
                    $erreur = "Une erreur est survenue.";
                    error_log("Erreur ajout plantation : " . $e->getMessage());
                }
            }
        }
    }
}

// Recuperer UNIQUEMENT les parcelles de cet agriculteur
$parcelles = $pdo->prepare("SELECT * FROM PARCELLE WHERE id_agri = ?");
$parcelles->execute([$_SESSION['id_agri']]);
$parcelles = $parcelles->fetchAll();

$cultures = $pdo->query("SELECT * FROM CULTURE ORDER BY nom_culture")->fetchAll();
$saisons = $pdo->query("SELECT * FROM SAISON ORDER BY date_debut_saison DESC")->fetchAll();

// Historique des plantations de cet agriculteur
$stmt = $pdo->prepare("
    SELECT pl.*, p.nom_parcelle, c.nom_culture, s.libelle_saison
    FROM PLANTATION pl
    JOIN PARCELLE p ON pl.id_parcelle = p.id_parcelle
    JOIN CULTURE c ON pl.id_culture = c.id_culture
    JOIN SAISON s ON pl.id_saison = s.id_saison
    WHERE p.id_agri = ?
    ORDER BY pl.date_semis DESC
");
$stmt->execute([$_SESSION['id_agri']]);
$plantations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Déclarer une plantation - AgriGest Togo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="page-container">
        <h1>Déclarer une mise en culture</h1>
        <p><a href="dashboard.php">&larr; Retour à mon espace</a></p>

        <?php if ($message): ?>
            <div class="alerte-succes"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <?php if (empty($parcelles)): ?>
            <div class="alerte-erreur">Vous n'avez aucune parcelle enregistrée. Contactez l'administrateur.</div>
        <?php elseif (empty($saisons)): ?>
            <div class="alerte-erreur">Aucune saison n'est encore configurée. Contactez l'administrateur.</div>
        <?php else: ?>

            <div class="card">
                <h2>Nouvelle plantation</h2>
                <form method="POST" class="form-card">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <div class="form-group">
                        <label for="id_parcelle">Parcelle</label>
                        <select id="id_parcelle" name="id_parcelle" required>
                            <option value="">-- Choisir --</option>
                            <?php foreach ($parcelles as $p): ?>
                                <option value="<?= htmlspecialchars($p['id_parcelle']) ?>"><?= htmlspecialchars($p['nom_parcelle']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_culture">Culture</label>
                        <select id="id_culture" name="id_culture" required>
                            <option value="">-- Choisir --</option>
                            <?php foreach ($cultures as $c): ?>
                                <option value="<?= htmlspecialchars($c['id_culture']) ?>"><?= htmlspecialchars($c['nom_culture']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_saison">Saison</label>
                        <select id="id_saison" name="id_saison" required>
                            <option value="">-- Choisir --</option>
                            <?php foreach ($saisons as $s): ?>
                                <option value="<?= htmlspecialchars($s['id_saison']) ?>"><?= htmlspecialchars($s['libelle_saison']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="date_semis">Date de semis</label>
                        <input type="date" id="date_semis" name="date_semis" required>
                    </div>

                    <button type="submit" name="ajouter_plantation" class="btn-primary">Enregistrer la plantation</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="card" style="margin-top: 30px;">
            <h2>Mes plantations</h2>
            <table class="table-data">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Parcelle</th>
                        <th>Culture</th>
                        <th>Saison</th>
                        <th>Date semis</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plantations as $pl): ?>
                        <tr>
                            <td><?= htmlspecialchars($pl['id_plantation']) ?></td>
                            <td><?= htmlspecialchars($pl['nom_parcelle']) ?></td>
                            <td><?= htmlspecialchars($pl['nom_culture']) ?></td>
                            <td><?= htmlspecialchars($pl['libelle_saison']) ?></td>
                            <td><?= htmlspecialchars($pl['date_semis']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p style="margin-top: 20px;"><a href="../auth/logout.php">Se déconnecter</a></p>
    </div>
</body>

</html>