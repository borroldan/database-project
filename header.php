<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="nav-button">Főoldal</a>
        <a href="match.php" class="nav-button">Meccsek</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profile.php" class="nav-button">Profil</a>
            <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] == 1): ?>
            <a href="admin.php" class="nav-button">Admin</a>
            <?php endif; ?>
            <a href="logout.php" class="nav-button" style="background-color: #FF0000;">Kijelentkezés</a>
        <?php else: ?>
            <a href="login.php" class="nav-button">Bejelentkezés</a>
            <a href="register.php" class="nav-button">Regisztráció</a>
        <?php endif; ?>
    </nav>
</body>
