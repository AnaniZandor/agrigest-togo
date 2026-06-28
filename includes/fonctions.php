<?php
/**
 * includes/fonctions.php
 * Fonctions reutilisables pour tout le projet AgriGest Togo.
 */

function genererCodeSimple($pdo, $table, $prefixe) {
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM $table");
    $result = $stmt->fetch();
    $prochainNumero = $result['total'] + 1;
    $numeroFormate = str_pad($prochainNumero, 3, '0', STR_PAD_LEFT);
    return $prefixe . '-' . $numeroFormate;
}

function genererCodeAvecParent($pdo, $table, $colonneId, $prefixe, $codeParent) {
    $motif = $prefixe . '-' . $codeParent . '-%';
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM $table WHERE $colonneId LIKE ?");
    $stmt->execute([$motif]);
    $result = $stmt->fetch();
    $prochainNumero = $result['total'] + 1;
    $numeroFormate = str_pad($prochainNumero, 3, '0', STR_PAD_LEFT);
    return $prefixe . '-' . $codeParent . '-' . $numeroFormate;
}

function nettoyer($valeur) {
    return htmlspecialchars(trim($valeur), ENT_QUOTES, 'UTF-8');
}
