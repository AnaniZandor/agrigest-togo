<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'admin') {
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

// TRAITEMENT : Ajout d'une culture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_culture'])) {
    if (!verifierCsrf()) {
        $erreur = "Session expirée, veuillez recharger la page.";
    } else {
        $nomCulture = nettoyer($_POST['nom_culture']);
        $dureeCycle = $_POST['duree_cycle'];

        if (empty($nomCulture) || empty($dureeCycle)) {
            $erreur = "Veuillez remplir tous les champs.";
        } elseif (!is_numeric($dureeCycle) || $dureeCycle <= 0) {
            $erreur = "La durée du cycle doit être un nombre positif.";
        } else {
            try {
                $nouvelId = genererCodeSimple($pdo, 'CULTURE', 'CUL');
                $stmt = $pdo->prepare("INSERT INTO CULTURE (id_culture, nom_culture, duree_cycle) VALUES (?, ?, ?)");
                $stmt->execute([$nouvelId, $nomCulture, $dureeCycle]);
                $message = "Culture ajoutée avec succès (code : $nouvelId).";
            } catch (PDOException $e) {
                $erreur = "Cette culture existe peut-être déjà.";
                error_log("Erreur ajout culture : " . $e->getMessage());
            }
        }
    }
}

// TRAITEMENT : Modification d'une culture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_culture'])) {
    if (!verifierCsrf()) {
        $erreur = "Session expirée, veuillez recharger la page.";
    } else {
        $idCulture = $_POST['id_culture'];
        $nomCulture = nettoyer($_POST['nom_culture']);
        $dureeCycle = $_POST['duree_cycle'];

        // Correction ici : Ajout des || manquants
        if (empty($nomCulture) || empty($dureeCycle) || !is_numeric($dureeCycle) || $dureeCycle <= 0) {
            $erreur = "Données invalides.";
        } else {
            $stmt = $pdo->prepare("UPDATE CULTURE SET nom_culture = ?, duree_cycle = ? WHERE id_culture = ?");
            $stmt->execute([$nomCulture, $dureeCycle, $idCulture]);
            $message = "Culture modifiée avec succès.";
        }
    }
}

// TRAITEMENT : Suppression d'une culture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_culture'])) {
    if (!verifierCsrf()) {
        $erreur = "Session expirée, veuillez recharger la page.";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM CULTURE WHERE id_culture = ?");
            $stmt->execute([$_POST['id_culture']]);
            $message = "Culture supprimée avec succès.";
        } catch (PDOException $e) {
            $erreur = "Impossible de supprimer : cette culture est utilisée dans des plantations.";
        }
    }
}

$cultures = $pdo->query("SELECT * FROM CULTURE ORDER BY nom_culture")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Gestion des Cultures - AgriGest Togo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="page-container">
        <h1>Gestion des cultures</h1>
        <p><a href="dashboard.php">&larr; Retour au tableau de bord</a></p>

        <?php if ($message): ?>
            <div class="alerte-succes"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Cultures existantes</h2>
            <table class="table-data">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Nom</th>
                        <th>Durée cycle (jours)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cultures as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['id_culture']) ?></td>
                            <td><?= htmlspecialchars($c['nom_culture']) ?></td>
                            <td><?= htmlspecialchars($c['duree_cycle']) ?></td>
                            <td>
                                <button type="button" class="btn-secondary" onclick="afficherFormModif('<?= htmlspecialchars($c['id_culture']) ?>', '<?= htmlspecialchars($c['nom_culture']) ?>', '<?= htmlspecialchars($c['duree_cycle']) ?>')">Modifier</button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette culture ?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="id_culture" value="<?= htmlspecialchars($c['id_culture']) ?>">
                                    <button type="submit" name="supprimer_culture" class="btn-danger">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3 id="titre_form">Ajouter une culture</h3>
            <form method="POST" class="form-card" id="form_culture">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="id_culture" id="champ_id" value="">

                <div class="form-group">
                    <label for="nom_culture">Nom de la culture</label>
                    <input type="text" id="nom_culture" name="nom_culture" maxlength="50" required>
                </div>
                <div class="form-group">
                    <label for="duree_cycle">Durée du cycle (jours)</label>
                    <input type="number" id="duree_cycle" name="duree_cycle" min="1" required>
                </div>

                <button type="submit" name="ajouter_culture" id="bouton_submit" class="btn-primary">Ajouter</button>
                <button type="button" class="btn-secondary" onclick="reinitialiser()" id="bouton_annuler" style="display:none;">Annuler</button>
            </form>
        </div>

        <p style="margin-top: 20px;"><a href="../auth/logout.php">Se déconnecter</a></p>
    </div>

    <script>
        function afficherFormModif(id, nom, duree) {
            document.getElementById('titre_form').innerText = 'Modifier la culture';
            document.getElementById('champ_id').value = id;
            document.getElementById('nom_culture').value = nom;
            document.getElementById('duree_cycle').value = duree;
            document.getElementById('bouton_submit').name = 'modifier_culture';
            document.getElementById('bouton_submit').innerText = 'Enregistrer';
            document.getElementById('bouton_annuler').style.display = 'inline';
        }

        function reinitialiser() {
            document.getElementById('titre_form').innerText = 'Ajouter une culture';
            document.getElementById('form_culture').reset();
            document.getElementById('champ_id').value = '';
            document.getElementById('bouton_submit').name = 'ajouter_culture';
            document.getElementById('bouton_submit').innerText = 'Ajouter';
            document.getElementById('bouton_annuler').style.display = 'none';
        }
    </script>
</body>

</html>