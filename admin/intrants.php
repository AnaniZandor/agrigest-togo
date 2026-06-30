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

function verifierCsrf() {
    return isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

$message = '';
$erreur = '';
$typesAutorises = ['engrais', 'semence', 'eau'];

// AJOUT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_intrant'])) {
    if (!verifierCsrf()) {
        $erreur = "Session expirée, veuillez recharger la page.";
    } else {
        $nomIntrant  = nettoyer($_POST['nom_intrant']);
        $typeIntrant = nettoyer($_POST['type_intrant']);
        $uniteMesure = nettoyer($_POST['unite_mesure']);

        if (empty($nomIntrant) || empty($typeIntrant) || empty($uniteMesure)) {
            $erreur = "Veuillez remplir tous les champs.";
        } elseif (!in_array($typeIntrant, $typesAutorises)) {
            $erreur = "Type d'intrant invalide.";
        } elseif (mb_strlen($nomIntrant) > 50 || mb_strlen($uniteMesure) > 10) {
            $erreur = "Un champ dépasse la longueur autorisée.";
        } else {
            try {
                $nouvelId = genererCodeSimple($pdo, 'INTRANT', 'INT');
                $stmt = $pdo->prepare("INSERT INTO intrant (id_intrant, nom_intrant, type_intrant, unite_mesure) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nouvelId, $nomIntrant, $typeIntrant, $uniteMesure]);
                $message = "Intrant ajouté avec succès (code : $nouvelId).";
            } catch (PDOException $e) {
                $erreur = "Une erreur est survenue lors de l'ajout.";
                error_log("Erreur ajout intrant : " . $e->getMessage());
            }
        }
    }
}

// MODIFICATION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_intrant'])) {
    if (!verifierCsrf()) {
        $erreur = "Session expirée, veuillez recharger la page.";
    } else {
        $idIntrant   = $_POST['id_intrant'];
        $nomIntrant  = nettoyer($_POST['nom_intrant']);
        $typeIntrant = nettoyer($_POST['type_intrant']);
        $uniteMesure = nettoyer($_POST['unite_mesure']);

        if (empty($nomIntrant) || !in_array($typeIntrant, $typesAutorises) || empty($uniteMesure)) {
            $erreur = "Données invalides.";
        } else {
            $stmt = $pdo->prepare("UPDATE intrant SET nom_intrant = ?, type_intrant = ?, unite_mesure = ? WHERE id_intrant = ?");
            $stmt->execute([$nomIntrant, $typeIntrant, $uniteMesure, $idIntrant]);
            $message = "Intrant modifié avec succès.";
        }
    }
}

// SUPPRESSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_intrant'])) {
    if (!verifierCsrf()) {
        $erreur = "Session expirée, veuillez recharger la page.";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM intrant WHERE id_intrant = ?");
            $stmt->execute([$_POST['id_intrant']]);
            $message = "Intrant supprimé avec succès.";
        } catch (PDOException $e) {
            $erreur = "Impossible de supprimer : cet intrant est déjà utilisé dans des plantations.";
        }
    }
}

$intrants = $pdo->query("SELECT * FROM intrant ORDER BY nom_intrant")->fetchAll();
$libellesType = ['engrais' => 'Engrais', 'semence' => 'Semence', 'eau' => 'Eau'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Intrants - AgriGest Togo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="page-container">
        <h1>Gestion des intrants</h1>
        <p><a href="dashboard.php">&larr; Retour au tableau de bord</a></p>

        <?php if ($message): ?>
            <div class="alerte-succes"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Intrants existants</h2>
            <table class="table-data">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Unité</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($intrants as $i): ?>
                    <tr>
                        <td><?= htmlspecialchars($i['id_intrant']) ?></td>
                        <td><?= htmlspecialchars($i['nom_intrant']) ?></td>
                        <td><?= htmlspecialchars($libellesType[$i['type_intrant']] ?? $i['type_intrant']) ?></td>
                        <td><?= htmlspecialchars($i['unite_mesure']) ?></td>
                        <td>
                            <button type="button" class="btn-secondary" onclick="afficherFormModif(
                                '<?= htmlspecialchars($i['id_intrant'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($i['nom_intrant'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($i['type_intrant'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($i['unite_mesure'], ENT_QUOTES) ?>'
                            )">Modifier</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cet intrant ?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="id_intrant" value="<?= htmlspecialchars($i['id_intrant']) ?>">
                                <button type="submit" name="supprimer_intrant" class="btn-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($intrants)): ?>
                    <tr><td colspan="5" style="text-align:center; color:#888;">Aucun intrant enregistré.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h3 id="titre_form">Ajouter un intrant</h3>
            <form method="POST" class="form-card" id="form_intrant">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="id_intrant" id="champ_id" value="">

                <div class="form-group">
                    <label for="nom_intrant">Nom de l'intrant</label>
                    <input type="text" id="nom_intrant" name="nom_intrant" maxlength="50" required
                           placeholder="Ex : Urée 46%, NPK 15-15-15, Semence maïs...">
                </div>

                <div class="form-group">
                    <label for="type_intrant">Type</label>
                    <select id="type_intrant" name="type_intrant" required>
                        <option value="">-- Choisir --</option>
                        <option value="engrais">Engrais</option>
                        <option value="semence">Semence</option>
                        <option value="eau">Eau</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="unite_mesure">Unité de mesure</label>
                    <input type="text" id="unite_mesure" name="unite_mesure" maxlength="10" required
                           placeholder="Ex : kg, litre, sachet">
                </div>

                <button type="submit" name="ajouter_intrant" id="bouton_submit" class="btn-primary">Ajouter</button>
                <button type="button" class="btn-secondary" onclick="reinitialiser()" id="bouton_annuler" style="display:none;">Annuler</button>
            </form>
        </div>

        <p style="margin-top: 20px;"><a href="../auth/logout.php">Se déconnecter</a></p>
    </div>

    <script>
    function afficherFormModif(id, nom, type, unite) {
        document.getElementById('titre_form').innerText = "Modifier l'intrant";
        document.getElementById('champ_id').value = id;
        document.getElementById('nom_intrant').value = nom;
        document.getElementById('type_intrant').value = type;
        document.getElementById('unite_mesure').value = unite;
        document.getElementById('bouton_submit').name = 'modifier_intrant';
        document.getElementById('bouton_submit').innerText = 'Enregistrer';
        document.getElementById('bouton_annuler').style.display = 'inline';
        document.getElementById('titre_form').scrollIntoView({ behavior: 'smooth' });
    }
    function reinitialiser() {
        document.getElementById('titre_form').innerText = 'Ajouter un intrant';
        document.getElementById('form_intrant').reset();
        document.getElementById('champ_id').value = '';
        document.getElementById('bouton_submit').name = 'ajouter_intrant';
        document.getElementById('bouton_submit').innerText = 'Ajouter';
        document.getElementById('bouton_annuler').style.display = 'none';
    }
    </script>
</body>
</html>