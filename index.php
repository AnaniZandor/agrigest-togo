<?php
/**
 * index.php
 * Page d'accueil AgriGest Togo
 */

session_start();

// Si l'utilisateur est connecté, rediriger vers son tableau de bord
if (isset($_SESSION['id_utilisateur']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    
    if ($role === 'admin') {
        header('Location: admin/dashboard.php');
    } elseif ($role === 'responsable') {
        header('Location: responsable/dashboard.php');
    } elseif ($role === 'agriculteur') {
        header('Location: agriculteur/dashboard.php');
    } else {
        session_destroy();
        header('Location: auth/login.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriGest Togo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="header-inner">
<h1><i class="fas fa-seedling" style="color: var(--color-primary);"></i> AgriGest Togo</h1>
                <button class="hamburger" id="hamburgerBtn" aria-label="Menu principal" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <nav id="navMenu">
                    <a href="index.php">Accueil</a>
                    <a href="auth/login.php" class="btn">Se connecter</a>
                </nav>
            </div>
        </header>

        <main>
            <section class="hero">
                <h2>Gestion des Exploitations Agricoles</h2>
                <p>Plateforme de gestion complète pour agriculteurs, coopératives et administrateurs</p>
                <a href="auth/login.php" class="btn btn-large">Se connecter</a>
            </section>
        </main>

        <footer>
            <p>&copy; 2024 AgriGest Togo</p>
        </footer>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>