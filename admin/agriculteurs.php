<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login_admin.php');
    exit;
}

$message = '';
$erreur = '';

// ---------------------------------------------------------
// TRAITEMENT : Ajout d'un agriculteur
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_agriculteur'])) {
    $nom = nettoyer($_POST['nom_agri']);
    $prenom = nettoyer($_POST['prenom_agri']);
    $contact = nettoyer($_POST['contact_agri']);
    $email = nettoyer($_POST['email_agri']);
    $motDePasse = $_POST['mot_de_passe_agri'];
    $idCoop = nettoyer($_POST['id_coop']);

    if (empty($nom) || empty($prenom) || empty($contact) || empty($email) || empty($motDePasse) || empty($idCoop)) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        // Verifier que l'email n'existe pas deja
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM AGRICULTEUR WHERE email_agri = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()['total'] > 0) {
            $erreur = "Cet email est deja utilise par un autre agriculteur.";
        } else {
            $nouvelId = genererCodeAvecParent($pdo, 'AGRICULTEUR', 'id_agri', 'AGR', $idCoop);
            $hash = password_hash($motDePasse, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO AGRICULTEUR (id_agri, nom_agri, prenom_agri, contact_agri, email_agri, mot_de_passe_agri, id_coop) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nouvelId, $nom, $prenom, $contact, $email, $hash, $idCoop]);

            $message = "Agriculteur ajoute avec succes (code : $nouvelId).";
        }
    }
}

// ---------------------------------------------------------
// TRAITEMENT : Modification d'un agriculteur
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_agriculteur'])) {
    $idAgri = $_POST['id_agri'];
    $nom = nettoyer($_POST['nom_agri']);
    $prenom = nettoyer($_POST['prenom_agri']);
    $contact = nettoyer($_POST['contact_agri']);
    $email = nettoyer($_POST['email_agri']);
    $idCoop = nettoyer($_POST['id_coop']);

    $stmt = $pdo->prepare("UPDATE AGRICULTEUR SET nom_agri = ?, prenom_agri = ?, contact_agri = ?, email_agri = ?, id_coop = ? WHERE id_agri = ?");
    $stmt->execute([$nom, $prenom, $contact, $email, $idCoop, $idAgri]);

    $message = "Agriculteur modifie avec succes.";
}

// ---------------------------------------------------------
// TRAITEMENT : Suppression d'un agriculteur
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_agriculteur'])) {
    $idAgri = $_POST['id_agri'];

    try {
        $stmt = $pdo->prepare("DELETE FROM AGRICULTEUR WHERE id_agri = ?");
        $stmt->execute([$idAgri]);
        $message = "Agriculteur supprime avec succes.";
    } catch (PDOException $e) {
        $erreur = "Impossible de supprimer cet agriculteur : il possede des parcelles ou des donnees liees.";
    }
}

// ---------------------------------------------------------
// TRAITEMENT : Ajout d'une cooperative
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_coop'])) {
    $nomCoop = nettoyer($_POST['nom_coop']);
    $localisationCoop = nettoyer($_POST['localisation_coop']);

    if (empty($nomCoop) || empty($localisationCoop)) {
        $erreur = "Veuillez remplir tous les champs de la cooperative.";
    } else {
        $nouvelId = genererCodeSimple($pdo, 'COOPERATIVE', 'COP');
        $stmt = $pdo->prepare("INSERT INTO COOPERATIVE (id_coop, nom_coop, localisation_coop) VALUES (?, ?, ?)");
        $stmt->execute([$nouvelId, $nomCoop, $localisationCoop]);
        $message = "Cooperative ajoutee avec succes (code : $nouvelId).";
    }
}

// ---------------------------------------------------------
// RECUPERATION DES DONNEES POUR AFFICHAGE
// ---------------------------------------------------------
$coops = $pdo->query("SELECT * FROM COOPERATIVE ORDER BY nom_coop")->fetchAll();

$agriculteurs = $pdo->query("
    SELECT a.*, c.nom_coop 
    FROM AGRICULTEUR a 
    JOIN COOPERATIVE c ON a.id_coop = c.id_coop 
    ORDER BY a.nom_agri
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Agriculteurs - AgriGest Togo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="page-container">
        <h1>Gestion des agriculteurs et cooperatives</h1>
        <p><a href="dashboard.php">&larr; Retour au tableau de bord</a></p>

        <?php if ($message): ?>
            <div class="alerte-succes"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <!-- ============ SECTION COOPERATIVES ============ -->
        <div class="card" style="margin-bottom: 30px;">
            <h2>Cooperatives</h2>

            <table class="table-data">
                <thead>
                    <tr><th>Code</th><th>Nom</th><th>Localisation</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($coops as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['id_coop']) ?></td>
                        <td><?= htmlspecialchars($c['nom_coop']) ?></td>
                        <td><?= htmlspecialchars($c['localisation_coop']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Ajouter une cooperative</h3>
            <form method="POST" class="form-card">
                <div class="form-group">
                    <label for="nom_coop">Nom de la cooperative</label>
                    <input type="text" id="nom_coop" name="nom_coop" required>
                </div>
                <div class="form-group">
                    <label for="localisation_coop">Localisation</label>
                    <input type="text" id="localisation_coop" name="localisation_coop" required>
                </div>
                <button type="submit" name="ajouter_coop" class="btn-primary">Ajouter la cooperative</button>
            </form>
        </div>

        <!-- ============ SECTION AGRICULTEURS ============ -->
        <div class="card" style="margin-bottom: 30px;">
            <h2>Agriculteurs</h2>

            <table class="table-data">
                <thead>
                    <tr>
                        <th>Code</th><th>Nom</th><th>Prenom</th><th>Contact</th><th>Email</th><th>Cooperative</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agriculteurs as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['id_agri']) ?></td>
                        <td><?= htmlspecialchars($a['nom_agri']) ?></td>
                        <td><?= htmlspecialchars($a['prenom_agri']) ?></td>
                        <td><?= htmlspecialchars($a['contact_agri']) ?></td>
                        <td><?= htmlspecialchars($a['email_agri']) ?></td>
                        <td><?= htmlspecialchars($a['nom_coop']) ?></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cet agriculteur ?');">
                                <input type="hidden" name="id_agri" value="<?= htmlspecialchars($a['id_agri']) ?>">
                                <button type="submit" name="supprimer_agriculteur" class="btn-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Ajouter un agriculteur</h3>
            <form method="POST" class="form-card">
                <div class="form-group">
                    <label for="nom_agri">Nom</label>
                    <input type="text" id="nom_agri" name="nom_agri" required>
                </div>
                <div class="form-group">
                    <label for="prenom_agri">Prenom</label>
                    <input type="text" id="prenom_agri" name="prenom_agri" required>
                </div>
                <div class="form-group">
                    <label for="contact_agri">Contact (telephone)</label>
                    <input type="text" id="contact_agri" name="contact_agri" required>
                </div>
                <div class="form-group">
                    <label for="email_agri">Email</label>
                    <input type="email" id="email_agri" name="email_agri" required>
                </div>
                <div class="form-group">
                    <label for="mot_de_passe_agri">Mot de passe</label>
                    <input type="password" id="mot_de_passe_agri" name="mot_de_passe_agri" required>
                </div>
                <div class="form-group">
                    <label for="id_coop">Cooperative</label>
                    <select id="id_coop" name="id_coop" required>
                        <option value="">-- Choisir --</option>
                        <?php foreach ($coops as $c): ?>
                            <option value="<?= htmlspecialchars($c['id_coop']) ?>"><?= htmlspecialchars($c['nom_coop']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="ajouter_agriculteur" class="btn-primary">Ajouter l'agriculteur</button>
            </form>
        </div>

        <p><a href="../auth/logout.php">Se deconnecter</a></p>
    </div>
</body>
</html>