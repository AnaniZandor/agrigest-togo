<?php
session_start();
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit;
    } elseif ($_SESSION['role'] === 'responsable') {
        header('Location: responsable/dashboard.php');
        exit;
    } elseif ($_SESSION['role'] === 'agriculteur') {
        header('Location: agriculteur/dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>AgriGest Togo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="page-container">
        <div class="card" style="max-width: 500px; margin: 80px auto; text-align: center;">
            <h1>AgriGest Togo</h1>
            <p>Systeme de gestion des exploitations agricoles au Togo</p>

            <div style="margin-top: 30px;">
                <p><a href="auth/login.php" class="btn-primary" style="display:block;">Se connecter</a></p>
            </div>
        </div>
    </div>
</body>
</html>