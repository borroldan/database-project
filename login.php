<?php
require 'db.php';

session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM felhasznalo WHERE felhasznalonev = ?");
    if ($stmt === false) {
        die('Hiba: ' . htmlspecialchars($conn->error));
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['jelszo'])) {
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['admin'] = $user['admin'];
        header("Location: index.php");
        exit;
    } else {
        $error = 'Hibás felhasználónév vagy jelszó!';
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bejelentkezés</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'header.php'; ?>  

    <div class="content-box">
        <h1>Bejelentkezés</h1>
        <?php if ($error): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <input type="text" name="username" placeholder="Felhasználónév" required class="form-input"><br>
            <input type="password" name="password" placeholder="Jelszó" required class="form-input"><br>
            <button type="submit" class="submit-button">Bejelentkezés</button>
        </form>
    </div>
</body>
</html>