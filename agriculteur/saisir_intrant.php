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

function verifierCsrf() {
    return isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

$message = '';
$erreur  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_utilisation'])) {
    if (!verifierCsrf()) {
        $erreur = "Session expirée, veuillez recharger la page.";
    } else {
        $idPlantation    = nettoyer($_POST['id_plantation']);
        $idIntrant       = nettoyer($_POST['id_intrant']);
        $quantite        = $_POST['quantite_utilisee'];
        $dateUtilisation = $_POST['date_utilisation'];

        if (empty($idPlantation) || empty($idIntrant) || empty($quantite) || empty($dateUtilisation)) {
            $erreur = "Veuillez remplir tous les champs.";
        } elseif (!is_numeric($quantite) || $quantite <= 0) {
            $erreur = "La quantité doit être un nombre positif.";
        } else {
            // Vérification : la plantation appartient bien à CET agriculteur
            $stmt = $pdo->prepare("
                SELECT COUNT(*) AS total FROM plantation pl
                JOIN parcelle p ON pl.id_parcelle = p.id_parcelle
                WHERE pl.id_plantation = ? AND p.id_agri = ?
            ");
            $stmt->execute([$idPlantation, $_SESSION['id_agri']]);
            if ($stmt->fetch()['total'] == 0) {
                $erreur = "Cette plantation ne vous appartient pas.";
            } else {
                try {
                    // Vérifier doublon (clé primaire composite)
                    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM utiliser WHERE id_plantation = ? AND id_intrant = ?");
                    $stmt->execute([$idPlantation, $idIntrant]);
                    if ($stmt->fetch()['total'] > 0) {
                        $erreur = "Cet intrant a déjà été déclaré pour cette plantation.";
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO utiliser (id_plantation, id_intrant, quantite_utilisee, date_utilisation) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$idPlantation, $idIntrant, $quantite, $dateUtilisation]);
                        $message = "Utilisation d'intrant enregistrée avec succès.";
                    }
                } catch (PDOException $e) {
                    $erreur = "Une erreur est survenue.";
                    error_log("Erreur ajout utilisation intrant : " . $e->getMessage());
                }
            }
        }
    }
}

// Plantations de cet agriculteur uniquement
$stmt = $pdo->prepare("
    SELECT pl.id_plantation, p.nom_parcelle, c.nom_culture, pl.date_semis
    FROM plantation pl
    JOIN parcelle p ON pl.id_parcelle = p.id_parcelle
    JOIN culture c ON pl.id_culture = c.id_culture
    WHERE p.id_agri = ?
    ORDER BY pl.date_semis DESC
");
$stmt->execute([$_SESSION['id_agri']]);
$plantations = $stmt->fetchAll();

$intrants = $pdo->query("SELECT * FROM intrant ORDER BY nom_intrant")->fetchAll();

// Historique des utilisations
$stmt = $pdo->prepare("
    SELECT u.*, i.nom_intrant, i.unite_mesure, p.nom_parcelle, c.nom_culture
    FROM utiliser u
    JOIN intrant i ON u.id_intrant = i.id_intrant
    JOIN plantation pl ON u.id_plantation = pl.id_plantation
    JOIN parcelle p ON pl.id_parcelle = p.id_parcelle
    JOIN culture c ON pl.id_culture = c.id_culture
    WHERE p.id_agri = ?
    ORDER BY u.date_utilisation DESC
");
$stmt->execute([$_SESSION['id_agri']]);
$utilisations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Déclarer un intrant - AgriGest Togo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="page-container">
        <h1>Déclarer l'utilisation d'un intrant</h1>
        <p><a href="dashboard.php">&larr; Retour à mon espace</a></p>

        <?php if ($message): ?>
            <div class="alerte-succes"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <?php if (empty($plantations)): ?>
            <div class="alerte-erreur">
                Vous n'avez aucune plantation enregistrée. Allez d'abord
                <a href="saisir_plantation.php">déclarer une mise en culture</a>.
            </div>
        <?php elseif (empty($intrants)): ?>
            <div class="alerte-erreur">Aucun intrant n'est encore configuré. Contactez l'administrateur.</div>
        <?php else: ?>

        <div class="card">
            <h2>Nouvelle utilisation d'intrant</h2>
            <form method="POST" class="form-card">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <div class="form-group">
                    <label for="id_plantation">Plantation concernée</label>
                    <select id="id_plantation" name="id_plantation" required>
                        <option value="">-- Choisir --</option>
                        <?php foreach ($plantations as $p): ?>
                            <option value="<?= htmlspecialchars($p['id_plantation']) ?>">
                                <?= htmlspecialchars($p['nom_parcelle'] . ' — ' . $p['nom_culture'] . ' (' . $p['date_semis'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="id_intrant">Intrant utilisé</label>
                    <select id="id_intrant" name="id_intrant" required>
                        <option value="">-- Choisir --</option>
                        <?php foreach ($intrants as $i): ?>
                            <option value="<?= htmlspecialchars($i['id_intrant']) ?>">
                                <?= htmlspecialchars($i['nom_intrant'] . ' (' . $i['unite_mesure'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="quantite_utilisee">Quantité utilisée</label>
                    <input type="number" id="quantite_utilisee" name="quantite_utilisee"
                           step="0.01" min="0.01" required>
                </div>

                <div class="form-group">
                    <label for="date_utilisation">Date d'utilisation</label>
                    <input type="date" id="date_utilisation" name="date_utilisation" required>
                </div>

                <button type="submit" name="ajouter_utilisation" class="btn-primary">Enregistrer</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="card" style="margin-top: 30px;">
            <h2>Historique de mes utilisations d'intrants</h2>
            <?php if (empty($utilisations)): ?>
                <p style="color:#888;">Aucune utilisation déclarée pour l'instant.</p>
            <?php else: ?>
            <table class="table-data">
                <thead>
                    <tr>
                        <th>Parcelle</th>
                        <th>Culture</th>
                        <th>Intrant</th>
                        <th>Quantité</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($utilisations as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['nom_parcelle']) ?></td>
                        <td><?= htmlspecialchars($u['nom_culture']) ?></td>
                        <td><?= htmlspecialchars($u['nom_intrant']) ?></td>
                        <td><?= htmlspecialchars($u['quantite_utilisee']) ?> <?= htmlspecialchars($u['unite_mesure']) ?></td>
                        <td><?= htmlspecialchars($u['date_utilisation']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <p style="margin-top: 20px;"><a href="../auth/logout.php">Se déconnecter</a></p>
    </div>
</body>
</html>