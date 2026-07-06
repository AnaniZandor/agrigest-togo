<?php
/**
 * logout.php
 * Déconnexion sécurisée pour AgriGest Togo - Version améliorée
 */

// Démarrer la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // =====================================================
    // 1. JOURNALISATION DE LA DÉCONNEXION (Optionnel)
    // =====================================================
    if (isset($_SESSION['id_utilisateur'])) {
        $nom = $_SESSION['nom'] ?? 'Inconnu';
        $prenom = $_SESSION['prenom'] ?? '';
        $role = $_SESSION['role'] ?? 'inconnu';
        $id_utilisateur = $_SESSION['id_utilisateur'];
        
        // Optionnel: écrire dans un fichier de log
        $log_message = sprintf(
            "[%s] Déconnexion de %s %s (ID: %s, Rôle: %s)\n",
            date('Y-m-d H:i:s'),
            $prenom,
            $nom,
            $id_utilisateur,
            $role
        );
        // error_log($log_message, 3, __DIR__ . '/../logs/activite.log');
    }

    // =====================================================
    // 2. NETTOYAGE COMPLET DE LA SESSION
    // =====================================================
    
    // 2.1. Sauvegarder le nom de la session si besoin (pour le cookie)
    $session_name = session_name();
    
    // 2.2. Vider toutes les variables de session
    $_SESSION = array();
    
    // 2.3. Détruire le cookie de session si présent
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        
        // Détruire le cookie de session
        setcookie(
            $session_name,
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
        
        // Détruire également le cookie de session alternatif (si PHP le crée)
        if (ini_get('session.use_trans_sid')) {
            setcookie(
                $session_name . '_sid',
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
    }
    
    // 2.4. Détruire la session côté serveur
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    
    // 2.5. Supprimer la variable globale de session
    unset($_SESSION);
    
    // 2.6. Régénérer l'ID de session pour plus de sécurité
    // Note: session_regenerate_id() ne fonctionne pas après session_destroy()
    // On va donc créer une nouvelle session propre pour le message de succès
    
    // On redémarre une nouvelle session pour le message de confirmation
    session_start();
    $_SESSION['logout_message'] = "Vous avez été déconnecté avec succès !";
    $_SESSION['logout_time'] = time();

} catch (Exception $e) {
    // En cas d'erreur, on tente quand même de rediriger
    error_log("Erreur lors de la déconnexion : " . $e->getMessage());
}

// =====================================================
// 3. REDIRECTION VERS LA PAGE DE CONNEXION
// =====================================================
header('Location: ../index.php');
exit;