<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login_admin.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verifierCsrf() {
    return isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

$message = '';
$erreur = '';

// ---------------------------------------------------------
// TRAITEMENT : Ajout d'une zone
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_zone'])) {
    if (!verifierCsrf()) {
        $erreur = "Session expiree, veuillez recharger la page.";
    } else {
        $nomZone = nettoyer($_POST['nom_zone']);

        if (empty($nomZone)) {
            $erreur = "Veuillez indiquer un nom de zone.";
        } elseif (mb_strlen($nomZone) > 50) {
            $erreur = "Le nom de la zone ne doit pas depasser 50 caracteres.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM ZONE_AGROECOLOGIQUE WHERE nom_zone = ?");
                $stmt->execute([$nomZone]);
                if ($stmt->fetch()['total'] > 0) {
                    $erreur = "Cette zone existe deja.";
                } else {
                    $nouvelId = genererCodeSimple($pdo, 'ZONE_AGROECOLOGIQUE', 'ZON');
                    $stmt = $pdo->prepare("INSERT INTO ZONE_AGROECOLOGIQUE (id_zone, nom_zone) VALUES (?, ?)");
                    $stmt->execute([$nouvelId, $nomZone]);
                    $message = "Zone ajoutee avec succes (code : $nouvelId).";
                }
            } catch (PDOException $e) {
                $erreur = "Une erreur est survenue lors de l'ajout.";
                error_log("Erreur ajout zone : " . $e->getMessage());
            }
        }
    }
}

// ---------------------------------------------------------
// TRAITEMENT : Suppression d'une zone
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_zone'])) {
    if (!verifierCsrf()) {
        $erreur = "Session expiree, veuillez recharger la page.";
    } else {
        $idZone = $_POST['id_zone'];
        try {
            $stmt = $pdo->prepare("DELETE FROM ZONE_AGROECOLOGIQUE WHERE id_zone = ?");
            $stmt->execute([$idZone]);
            $message = "Zone supprimee avec succes.";
        } catch (PDOException $e) {
            $erreur = "Impossible de supprimer cette zone : elle est utilisee par une ou plusieurs parcelles.";
            error_log("Erreur suppression zone : " . $e->getMessage());
        }
    }
}

$zones = $pdo->query("SELECT * FROM ZONE_AGROECOLOGIQUE ORDER BY nom_zone")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Zones - AgriGest Togo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="page-container">
        <h1>Gestion des zones agro-ecologiques</h1>
        <p><a href="dashboard.php">&larr; Retour au tableau de bord</a></p>

        <?php if ($message): ?>
            <div class="alerte-succes"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Zones existantes</h2>

            <table class="table-data">
                <thead>
                    <tr><th>Code</th><th>Nom de la zone</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($zones as $z): ?>
                    <tr>
                        <td><?= htmlspecialchars($z['id_zone']) ?></td>
                        <td><?= htmlspecialchars($z['nom_zone']) ?></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette zone ?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="id_zone" value="<?= htmlspecialchars($z['id_zone']) ?>">
                                <button type="submit" name="supprimer_zone" class="btn-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Ajouter une zone</h3>
            <form method="POST" class="form-card">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="form-group">
                    <label for="nom_zone">Nom de la zone</label>
                    <input type="text" id="nom_zone" name="nom_zone" maxlength="50" required
                           placeholder="Ex: Zone des plaines du Nord, Zone de la Kara...">
                </div>
                <button type="submit" name="ajouter_zone" class="btn-primary">Ajouter la zone</button>
            </form>

            <p style="margin-top: 15px; color: #666;">
                <em>Suggestion : les 5 zones agro-ecologiques officielles du Togo sont generalement
                "Zone des plaines du Nord", "Zone de la Kara", "Zone des plateaux centraux",
                "Zone du Litime" et "Zone cotiere du Sud" — mais vous pouvez creer vos propres
                zones selon vos besoins.</em>
            </p>
        </div>

        <p style="margin-top: 20px;"><a href="../auth/logout.php">Se deconnecter</a></p>
    </div>
</body>
</html>