<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['id_utilisateur']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Generation du token CSRF (une fois par session)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$erreur = '';

// Fonction de validation du token CSRF
function verifierCsrf() {
    return isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

// ---------------------------------------------------------
// TRAITEMENT : Ajout d'un agriculteur
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_agriculteur'])) {
    if (!verifierCsrf()) {
        $erreur = "Session expiree, veuillez recharger la page et reessayer.";
    } else {
        $nom = nettoyer($_POST['nom_agri']);
        $prenom = nettoyer($_POST['prenom_agri']);
        $contact = nettoyer($_POST['contact_agri']);
        $email = nettoyer($_POST['email_agri']);
        $motDePasse = $_POST['mot_de_passe_agri'];
        $idCoop = nettoyer($_POST['id_coop']);

        // Validation stricte
        if (empty($nom) || empty($prenom) || empty($contact) || empty($email) || empty($motDePasse) || empty($idCoop)) {
            $erreur = "Veuillez remplir tous les champs.";
        } elseif (mb_strlen($nom) > 50 || mb_strlen($prenom) > 50) {
            $erreur = "Le nom et le prenom ne doivent pas depasser 50 caracteres.";
        } elseif (!preg_match('/^[0-9]{8}$/', $contact)) {
            $erreur = "Le contact doit etre un numero togolais valide (8 chiffres).";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreur = "Adresse email invalide.";
        } elseif (mb_strlen($motDePasse) < 6) {
            $erreur = "Le mot de passe doit contenir au moins 6 caracteres.";
        } else {
            try {
              $stmt = $pdo->prepare("SELECT nom_agri, prenom_agri, id_agri FROM AGRICULTEUR WHERE email_agri = ?");
                $stmt->execute([$email]);
                $existant = $stmt->fetch();

                if ($existant) {
                    $erreur = "Cet email est deja utilise par " . htmlspecialchars($existant['prenom_agri']) . " " . htmlspecialchars($existant['nom_agri']) . " (code " . htmlspecialchars($existant['id_agri']) . ").";
                } else {
                    $nouvelId = genererCodeAvecParent($pdo, 'AGRICULTEUR', 'id_agri', 'AGR', $idCoop);
                    $hash = password_hash($motDePasse, PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare("INSERT INTO AGRICULTEUR (id_agri, nom_agri, prenom_agri, contact_agri, email_agri, mot_de_passe_agri, id_coop) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$nouvelId, $nom, $prenom, $contact, $email, $hash, $idCoop]);

                    $message = "Agriculteur ajoute avec succes (code : $nouvelId).";
                }
            } catch (PDOException $e) {
                $erreur = "Une erreur est survenue lors de l'ajout. Veuillez reessayer.";
                error_log("Erreur ajout agriculteur : " . $e->getMessage());
            }
        }
    }
}

// ---------------------------------------------------------
// TRAITEMENT : Modification d'un agriculteur
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_agriculteur'])) {
    if (!verifierCsrf()) {
        $erreur = "Session expiree, veuillez recharger la page et reessayer.";
    } else {
        $idAgri = $_POST['id_agri'];
        $nom = nettoyer($_POST['nom_agri']);
        $prenom = nettoyer($_POST['prenom_agri']);
        $contact = nettoyer($_POST['contact_agri']);
        $email = nettoyer($_POST['email_agri']);
        $idCoop = nettoyer($_POST['id_coop']);

        if (empty($nom) || empty($prenom) || empty($contact) || empty($email) || empty($idCoop)) {
            $erreur = "Veuillez remplir tous les champs.";
        } elseif (!preg_match('/^[0-9]{8}$/', $contact)) {
            $erreur = "Le contact doit etre un numero togolais valide (8 chiffres).";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreur = "Adresse email invalide.";
        } else {
            try {
                // Verifier que l'email n'est pas deja pris par un AUTRE agriculteur
                $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM AGRICULTEUR WHERE email_agri = ? AND id_agri != ?");
                $stmt->execute([$email, $idAgri]);
                if ($stmt->fetch()['total'] > 0) {
                    $erreur = "Cet email est deja utilise par un autre agriculteur.";
                } else {
                    $stmt = $pdo->prepare("UPDATE AGRICULTEUR SET nom_agri = ?, prenom_agri = ?, contact_agri = ?, email_agri = ?, id_coop = ? WHERE id_agri = ?");
                    $stmt->execute([$nom, $prenom, $contact, $email, $idCoop, $idAgri]);
                    $message = "Agriculteur modifie avec succes.";
                }
            } catch (PDOException $e) {
                $erreur = "Une erreur est survenue lors de la modification.";
                error_log("Erreur modification agriculteur : " . $e->getMessage());
            }
        }
    }
}

// ---------------------------------------------------------
// TRAITEMENT : Suppression d'un agriculteur
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_agriculteur'])) {
    if (!verifierCsrf()) {
        $erreur = "Session expiree, veuillez recharger la page et reessayer.";
    } else {
        $idAgri = $_POST['id_agri'];
        try {
            $stmt = $pdo->prepare("DELETE FROM AGRICULTEUR WHERE id_agri = ?");
            $stmt->execute([$idAgri]);
            $message = "Agriculteur supprime avec succes.";
        } catch (PDOException $e) {
            $erreur = "Impossible de supprimer cet agriculteur : il possede des parcelles ou des donnees liees.";
            error_log("Erreur suppression agriculteur : " . $e->getMessage());
        }
    }
}

// ---------------------------------------------------------
// TRAITEMENT : Ajout d'une cooperative
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_coop'])) {
    if (!verifierCsrf()) {
        $erreur = "Session expiree, veuillez recharger la page et reessayer.";
    } else {
        $nomCoop = nettoyer($_POST['nom_coop']);
        $localisationCoop = nettoyer($_POST['localisation_coop']);

        if (empty($nomCoop) || empty($localisationCoop)) {
            $erreur = "Veuillez remplir tous les champs de la cooperative.";
        } elseif (mb_strlen($nomCoop) > 100 || mb_strlen($localisationCoop) > 150) {
            $erreur = "Le nom ou la localisation est trop long.";
        } else {
            try {
                $nouvelId = genererCodeSimple($pdo, 'COOPERATIVE', 'COP');
                $stmt = $pdo->prepare("INSERT INTO COOPERATIVE (id_coop, nom_coop, localisation_coop) VALUES (?, ?, ?)");
                $stmt->execute([$nouvelId, $nomCoop, $localisationCoop]);
                $message = "Cooperative ajoutee avec succes (code : $nouvelId).";
            } catch (PDOException $e) {
                $erreur = "Une erreur est survenue lors de l'ajout de la cooperative.";
                error_log("Erreur ajout cooperative : " . $e->getMessage());
            }
        }
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
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="form-group">
                    <label for="nom_coop">Nom de la cooperative</label>
                    <input type="text" id="nom_coop" name="nom_coop" maxlength="100" required>
                </div>
                <div class="form-group">
                    <label for="localisation_coop">Localisation</label>
                    <input type="text" id="localisation_coop" name="localisation_coop" maxlength="150" required>
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
                            <button type="button" class="btn-secondary" onclick="afficherFormModif('<?= htmlspecialchars($a['id_agri']) ?>', '<?= htmlspecialchars($a['nom_agri']) ?>', '<?= htmlspecialchars($a['prenom_agri']) ?>', '<?= htmlspecialchars($a['contact_agri']) ?>', '<?= htmlspecialchars($a['email_agri']) ?>', '<?= htmlspecialchars($a['id_coop']) ?>')">Modifier</button>

                            <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cet agriculteur ?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="id_agri" value="<?= htmlspecialchars($a['id_agri']) ?>">
                                <button type="submit" name="supprimer_agriculteur" class="btn-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Formulaire d'ajout -->
            <h3 id="titre_form_agri">Ajouter un agriculteur</h3>
            <form method="POST" class="form-card" id="form_agriculteur">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="id_agri" id="champ_id_agri" value="">

                <div class="form-group">
                    <label for="nom_agri">Nom</label>
                    <input type="text" id="nom_agri" name="nom_agri" maxlength="50" required>
                </div>
                <div class="form-group">
                    <label for="prenom_agri">Prenom</label>
                    <input type="text" id="prenom_agri" name="prenom_agri" maxlength="50" required>
                </div>
                <div class="form-group">
                    <label for="contact_agri">Contact (8 chiffres)</label>
                    <input type="text" id="contact_agri" name="contact_agri" pattern="[0-9]{8}" maxlength="8" required>
                </div>
                <div class="form-group">
                    <label for="email_agri">Email</label>
                    <input type="email" id="email_agri" name="email_agri" required>
                </div>
                <div class="form-group" id="groupe_mot_de_passe">
                    <label for="mot_de_passe_agri">Mot de passe (min. 6 caracteres)</label>
                    <input type="password" id="mot_de_passe_agri" name="mot_de_passe_agri" minlength="6">
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

                <button type="submit" name="ajouter_agriculteur" id="bouton_submit_agri" class="btn-primary">Ajouter l'agriculteur</button>
                <button type="button" class="btn-secondary" onclick="reinitialiserForm()" id="bouton_annuler" style="display:none;">Annuler</button>
            </form>
        </div>

        <p><a href="../auth/logout.php">Se deconnecter</a></p>
    </div>

    <script>
    function afficherFormModif(id, nom, prenom, contact, email, idCoop) {
        document.getElementById('titre_form_agri').innerText = 'Modifier l\'agriculteur';
        document.getElementById('champ_id_agri').value = id;
        document.getElementById('nom_agri').value = nom;
        document.getElementById('prenom_agri').value = prenom;
        document.getElementById('contact_agri').value = contact;
        document.getElementById('email_agri').value = email;
        document.getElementById('id_coop').value = idCoop;

        // On masque le mot de passe en mode modification (pas obligatoire de le changer)
        document.getElementById('groupe_mot_de_passe').style.display = 'none';
        document.getElementById('mot_de_passe_agri').required = false;

        document.getElementById('bouton_submit_agri').name = 'modifier_agriculteur';
        document.getElementById('bouton_submit_agri').innerText = 'Enregistrer les modifications';
        document.getElementById('bouton_annuler').style.display = 'inline';

        window.scrollTo(0, document.getElementById('form_agriculteur').offsetTop);
    }

    function reinitialiserForm() {
        document.getElementById('titre_form_agri').innerText = 'Ajouter un agriculteur';
        document.getElementById('form_agriculteur').reset();
        document.getElementById('champ_id_agri').value = '';
        document.getElementById('groupe_mot_de_passe').style.display = 'block';
        document.getElementById('mot_de_passe_agri').required = true;
        document.getElementById('bouton_submit_agri').name = 'ajouter_agriculteur';
        document.getElementById('bouton_submit_agri').innerText = 'Ajouter l\'agriculteur';
        document.getElementById('bouton_annuler').style.display = 'none';
    }
    </script>
</body>
</html>
