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

// ---------------------------------------------------------
// TRAITEMENT : Ajout d'une parcelle
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_parcelle'])) {
    if (!verifierCsrf()) {
        $erreur = "Session expiree, veuillez recharger la page.";
    } else {
        $nomParcelle = nettoyer($_POST['nom_parcelle']);
        $localisation = nettoyer($_POST['localisation_parcelle']);
        $superficie = $_POST['superficie'];
        $idZone = nettoyer($_POST['id_zone']);
        $idAgri = nettoyer($_POST['id_agri']);

        if (empty($nomParcelle) || empty($localisation) || empty($superficie) || empty($idZone) || empty($idAgri)) {
            $erreur = "Veuillez remplir tous les champs.";
        } elseif (mb_strlen($nomParcelle) > 50) {
            $erreur = "Le nom de la parcelle ne doit pas depasser 50 caracteres.";
        } elseif (!is_numeric($superficie) || $superficie <= 0) {
            $erreur = "La superficie doit etre un nombre positif.";
        } elseif ($superficie > 10000) {
            $erreur = "La superficie semble irrealiste (max 10000 hectares).";
        } else {
            try {
                $nouvelId = genererCodeAvecParent($pdo, 'PARCELLE', 'id_parcelle', 'PAR', $idZone);
                $stmt = $pdo->prepare("INSERT INTO PARCELLE (id_parcelle, nom_parcelle, localisation_parcelle, superficie, id_zone, id_agri) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nouvelId, $nomParcelle, $localisation, $superficie, $idZone, $idAgri]);
                $message = "Parcelle ajoutee avec succes (code : $nouvelId).";
            } catch (PDOException $e) {
                $erreur = "Une erreur est survenue lors de l'ajout.";
                error_log("Erreur ajout parcelle : " . $e->getMessage());
            }
        }
    }
}

// ---------------------------------------------------------
// TRAITEMENT : Modification d'une parcelle
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_parcelle'])) {
    if (!verifierCsrf()) {
        $erreur = "Session expiree, veuillez recharger la page.";
    } else {
        $idParcelle = $_POST['id_parcelle'];
        $nomParcelle = nettoyer($_POST['nom_parcelle']);
        $localisation = nettoyer($_POST['localisation_parcelle']);
        $superficie = $_POST['superficie'];
        $idZone = nettoyer($_POST['id_zone']);
        $idAgri = nettoyer($_POST['id_agri']);

        if (empty($nomParcelle) || empty($localisation) || empty($superficie) || empty($idZone) || empty($idAgri)) {
            $erreur = "Veuillez remplir tous les champs.";
        } elseif (!is_numeric($superficie) || $superficie <= 0) {
            $erreur = "La superficie doit etre un nombre positif.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE PARCELLE SET nom_parcelle = ?, localisation_parcelle = ?, superficie = ?, id_zone = ?, id_agri = ? WHERE id_parcelle = ?");
                $stmt->execute([$nomParcelle, $localisation, $superficie, $idZone, $idAgri, $idParcelle]);
                $message = "Parcelle modifiee avec succes.";
            } catch (PDOException $e) {
                $erreur = "Une erreur est survenue lors de la modification.";
                error_log("Erreur modification parcelle : " . $e->getMessage());
            }
        }
    }
}

// ---------------------------------------------------------
// TRAITEMENT : Suppression d'une parcelle
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_parcelle'])) {
    if (!verifierCsrf()) {
        $erreur = "Session expiree, veuillez recharger la page.";
    } else {
        $idParcelle = $_POST['id_parcelle'];
        try {
            $stmt = $pdo->prepare("DELETE FROM PARCELLE WHERE id_parcelle = ?");
            $stmt->execute([$idParcelle]);
            $message = "Parcelle supprimee avec succes.";
        } catch (PDOException $e) {
            $erreur = "Impossible de supprimer cette parcelle : elle possede des plantations enregistrees.";
            error_log("Erreur suppression parcelle : " . $e->getMessage());
        }
    }
}

// ---------------------------------------------------------
// RECUPERATION DES DONNEES POUR AFFICHAGE
// ---------------------------------------------------------
$zones = $pdo->query("SELECT * FROM ZONE_AGROECOLOGIQUE ORDER BY nom_zone")->fetchAll();
$agriculteurs = $pdo->query("SELECT * FROM AGRICULTEUR ORDER BY nom_agri")->fetchAll();

$parcelles = $pdo->query("
    SELECT p.*, z.nom_zone, a.nom_agri, a.prenom_agri
    FROM PARCELLE p
    JOIN ZONE_AGROECOLOGIQUE z ON p.id_zone = z.id_zone
    JOIN AGRICULTEUR a ON p.id_agri = a.id_agri
    ORDER BY p.nom_parcelle
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Parcelles - AgriGest Togo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="page-container">
        <h1>Gestion des parcelles</h1>
        <p><a href="dashboard.php">&larr; Retour au tableau de bord</a> | <a href="zones.php">Gerer les zones</a></p>

        <?php if ($message): ?>
            <div class="alerte-succes"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <?php if (empty($agriculteurs)): ?>
            <div class="alerte-erreur">
                Aucun agriculteur enregistre. Veuillez d'abord <a href="agriculteurs.php">ajouter un agriculteur</a> avant de creer une parcelle.
            </div>
        <?php endif; ?>

        <?php if (empty($zones)): ?>
            <div class="alerte-erreur">
                Aucune zone agro-ecologique enregistree. Veuillez d'abord <a href="zones.php">ajouter une zone</a> avant de creer une parcelle.
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>Parcelles existantes</h2>

            <table class="table-data">
                <thead>
                    <tr>
                        <th>Code</th><th>Nom</th><th>Localisation</th><th>Superficie (ha)</th><th>Zone</th><th>Agriculteur</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($parcelles as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['id_parcelle']) ?></td>
                        <td><?= htmlspecialchars($p['nom_parcelle']) ?></td>
                        <td><?= htmlspecialchars($p['localisation_parcelle']) ?></td>
                        <td><?= htmlspecialchars($p['superficie']) ?></td>
                        <td><?= htmlspecialchars($p['nom_zone']) ?></td>
                        <td><?= htmlspecialchars($p['prenom_agri'] . ' ' . $p['nom_agri']) ?></td>
                        <td>
                            <button type="button" class="btn-secondary" onclick="afficherFormModif(
                                '<?= htmlspecialchars($p['id_parcelle']) ?>',
                                '<?= htmlspecialchars($p['nom_parcelle']) ?>',
                                '<?= htmlspecialchars($p['localisation_parcelle']) ?>',
                                '<?= htmlspecialchars($p['superficie']) ?>',
                                '<?= htmlspecialchars($p['id_zone']) ?>',
                                '<?= htmlspecialchars($p['id_agri']) ?>'
                            )">Modifier</button>

                            <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette parcelle ?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="id_parcelle" value="<?= htmlspecialchars($p['id_parcelle']) ?>">
                                <button type="submit" name="supprimer_parcelle" class="btn-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3 id="titre_form_parcelle">Ajouter une parcelle</h3>
            <form method="POST" class="form-card" id="form_parcelle">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="id_parcelle" id="champ_id_parcelle" value="">

                <div class="form-group">
                    <label for="nom_parcelle">Nom / repere de la parcelle</label>
                    <input type="text" id="nom_parcelle" name="nom_parcelle" maxlength="50" required>
                </div>
                <div class="form-group">
                    <label for="localisation_parcelle">Localisation</label>
                    <input type="text" id="localisation_parcelle" name="localisation_parcelle" maxlength="100" required>
                </div>
                <div class="form-group">
                    <label for="superficie">Superficie (hectares)</label>
                    <input type="number" id="superficie" name="superficie" step="0.01" min="0.01" max="10000" required>
                </div>
                <div class="form-group">
                    <label for="id_zone">Zone agro-ecologique</label>
                    <select id="id_zone" name="id_zone" required>
                        <option value="">-- Choisir --</option>
                        <?php foreach ($zones as $z): ?>
                            <option value="<?= htmlspecialchars($z['id_zone']) ?>"><?= htmlspecialchars($z['nom_zone']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="id_agri">Agriculteur proprietaire</label>
                    <select id="id_agri" name="id_agri" required>
                        <option value="">-- Choisir --</option>
                        <?php foreach ($agriculteurs as $a): ?>
                            <option value="<?= htmlspecialchars($a['id_agri']) ?>"><?= htmlspecialchars($a['prenom_agri'] . ' ' . $a['nom_agri']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" name="ajouter_parcelle" id="bouton_submit_parcelle" class="btn-primary">Ajouter la parcelle</button>
                <button type="button" class="btn-secondary" onclick="reinitialiserForm()" id="bouton_annuler_parcelle" style="display:none;">Annuler</button>
            </form>
        </div>

        <p style="margin-top: 20px;"><a href="../auth/logout.php">Se deconnecter</a></p>
    </div>

    <script>
    function afficherFormModif(id, nom, localisation, superficie, idZone, idAgri) {
        document.getElementById('titre_form_parcelle').innerText = 'Modifier la parcelle';
        document.getElementById('champ_id_parcelle').value = id;
        document.getElementById('nom_parcelle').value = nom;
        document.getElementById('localisation_parcelle').value = localisation;
        document.getElementById('superficie').value = superficie;
        document.getElementById('id_zone').value = idZone;
        document.getElementById('id_agri').value = idAgri;

        document.getElementById('bouton_submit_parcelle').name = 'modifier_parcelle';
        document.getElementById('bouton_submit_parcelle').innerText = 'Enregistrer les modifications';
        document.getElementById('bouton_annuler_parcelle').style.display = 'inline';

        window.scrollTo(0, document.getElementById('form_parcelle').offsetTop);
    }

    function reinitialiserForm() {
        document.getElementById('titre_form_parcelle').innerText = 'Ajouter une parcelle';
        document.getElementById('form_parcelle').reset();
        document.getElementById('champ_id_parcelle').value = '';
        document.getElementById('bouton_submit_parcelle').name = 'ajouter_parcelle';
        document.getElementById('bouton_submit_parcelle').innerText = 'Ajouter la parcelle';
        document.getElementById('bouton_annuler_parcelle').style.display = 'none';
    }
    </script>
</body>
</html>
