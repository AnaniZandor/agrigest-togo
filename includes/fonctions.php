<?php
/**
 * includes/fonctions.php
 * Fonctions reutilisables pour tout le projet AgriGest Togo.
 * 
 * Codification articulee par role :
 *   Admin       : ADM-001
 *   Responsable : RSP-COP001-001
 *   Agriculteur : AGR-COP001-001
 *   Cooperative : COP-001
 *   Zone        : ZON-001
 *   Parcelle    : PAR-ZON001-001
 *   Culture     : CUL-001
 *   Saison      : SAI-2025P
 *   Intrant     : INT-001
 *   Plantation  : PLT-001
 *   Recolte     : REC-001
 */

// ---------------------------------------------------------
// FONCTIONS DE GENERATION DE CODES ARTICULES
// ---------------------------------------------------------

/**
 * Genere un code simple : PREFIXE-NUMERO
 * Exemple : genererCodeSimple($pdo, 'CULTURE', 'CUL') -> "CUL-001"
 */
function genererCodeSimple($pdo, $table, $prefixe) {
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM $table");
    $result = $stmt->fetch();
    $prochainNumero = $result['total'] + 1;
    return $prefixe . '-' . str_pad($prochainNumero, 3, '0', STR_PAD_LEFT);
}

/**
 * Genere un code avec segment parent : PREFIXE-CODEPARENT-NUMERO
 * Exemple : genererCodeAvecParent($pdo, 'PARCELLE', 'id_parcelle', 'PAR', 'ZON-001')
 *           -> "PAR-ZON001-001"
 */
function genererCodeAvecParent($pdo, $table, $colonneId, $prefixe, $codeParent) {
    $codeParentNettoye = str_replace('-', '', $codeParent);
    $motif = $prefixe . '-' . $codeParentNettoye . '-%';
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM $table WHERE $colonneId LIKE ?");
    $stmt->execute([$motif]);
    $result = $stmt->fetch();
    $prochainNumero = $result['total'] + 1;
    return $prefixe . '-' . $codeParentNettoye . '-' . str_pad($prochainNumero, 3, '0', STR_PAD_LEFT);
}

/**
 * Genere le code d'un utilisateur selon son role
 * 
 * Admin       : ADM-001
 * Responsable : RSP-COP001-001 (rattache a une cooperative)
 * Agriculteur : AGR-COP001-001 (rattache a une cooperative)
 * 
 * @param PDO    $pdo      Connexion PDO
 * @param string $role     'admin', 'responsable', 'agriculteur'
 * @param string $idCoop   Obligatoire pour responsable et agriculteur
 */
function genererCodeUtilisateur($pdo, $role, $idCoop = null) {
    switch ($role) {
        case 'admin':
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) AS total FROM UTILISATEUR WHERE role = 'admin'"
            );
            $stmt->execute();
            $result = $stmt->fetch();
            $numero = str_pad($result['total'] + 1, 3, '0', STR_PAD_LEFT);
            return 'ADM-' . $numero;

        case 'responsable':
            if (!$idCoop) {
                throw new InvalidArgumentException(
                    "id_coop obligatoire pour generer un code responsable."
                );
            }
            $codeParent = str_replace('-', '', $idCoop);
            $motif = 'RSP-' . $codeParent . '-%';
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) AS total FROM UTILISATEUR 
                 WHERE role = 'responsable' AND id_utilisateur LIKE ?"
            );
            $stmt->execute([$motif]);
            $result = $stmt->fetch();
            $numero = str_pad($result['total'] + 1, 3, '0', STR_PAD_LEFT);
            return 'RSP-' . $codeParent . '-' . $numero;

        case 'agriculteur':
            if (!$idCoop) {
                throw new InvalidArgumentException(
                    "id_coop obligatoire pour generer un code agriculteur."
                );
            }
            $codeParent = str_replace('-', '', $idCoop);
            $motif = 'AGR-' . $codeParent . '-%';
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) AS total FROM UTILISATEUR 
                 WHERE role = 'agriculteur' AND id_utilisateur LIKE ?"
            );
            $stmt->execute([$motif]);
            $result = $stmt->fetch();
            $numero = str_pad($result['total'] + 1, 3, '0', STR_PAD_LEFT);
            return 'AGR-' . $codeParent . '-' . $numero;

        default:
            throw new InvalidArgumentException("Role invalide : $role");
    }
}

/**
 * Genere le code d'une saison
 * Format : SAI-AAAAP (pluvieuse) ou SAI-AAAAS (seche)
 * Exemple : SAI-2025P, SAI-2026S
 * 
 * @param string $annee  Annee (ex: '2025')
 * @param string $type   'P' pour pluvieuse, 'S' pour seche
 */
function genererCodeSaison($annee, $type) {
    $typeNettoye = strtoupper($type);
    if (!in_array($typeNettoye, ['P', 'S'])) {
        throw new InvalidArgumentException("Type de saison invalide : P ou S attendu.");
    }
    return 'SAI-' . $annee . $typeNettoye;
}

// ---------------------------------------------------------
// FONCTIONS DE SECURITE ET VALIDATION
// ---------------------------------------------------------

/**
 * Nettoie une valeur recue d'un formulaire
 * Protection basique contre XSS
 */
function nettoyer($valeur) {
    return htmlspecialchars(trim($valeur), ENT_QUOTES, 'UTF-8');
}

/**
 * Verifie le token CSRF
 * Appeler dans chaque traitement de formulaire POST
 */
function verifierCsrf() {
    return isset($_POST['csrf_token'])
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/**
 * Genere et stocke un token CSRF en session si absent
 * Appeler en haut de chaque page avec formulaire
 */
function initialiserCsrf() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// ---------------------------------------------------------
// FONCTIONS DE CONTROLE D'ACCES
// ---------------------------------------------------------

/**
 * Verifie que l'utilisateur connecte a le bon role
 * Redirige vers login si non connecte ou mauvais role
 * 
 * @param string|array $rolesAutorises  'admin', 'responsable', 'agriculteur' ou tableau
 */
function exigerRole($rolesAutorises) {
    if (!isset($_SESSION['id_utilisateur']) || !isset($_SESSION['role'])) {
        header('Location: ../auth/login.php');
        exit;
    }
    $roles = is_array($rolesAutorises) ? $rolesAutorises : [$rolesAutorises];
    if (!in_array($_SESSION['role'], $roles)) {
        header('Location: ../auth/login.php');
        exit;
    }
}

// ---------------------------------------------------------
// FONCTIONS UTILITAIRES
// ---------------------------------------------------------

/**
 * Formate une date MySQL (YYYY-MM-DD) en format lisible (DD/MM/YYYY)
 */
function formaterDate($date) {
    if (!$date) return '';
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d ? $d->format('d/m/Y') : $date;
}

/**
 * Formate un nombre decimal avec separateur de milliers
 * Exemple : 1200.50 -> "1 200,50"
 */
function formaterNombre($nombre, $decimales = 2) {
    return number_format((float)$nombre, $decimales, ',', ' ');
}