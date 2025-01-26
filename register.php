<?php
require 'db.php';

session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $username = $_POST['username'];
    $full_name = $_POST['full_name'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = 'A jelszók nem egyeznek.';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO felhasznalo (felhasznalonev, email, jelszo, nev) VALUES (?, ?, ?, ?)");

        if ($stmt === false) {
            die('Hiba: ' . htmlspecialchars($conn->error));
        }

        $stmt->bind_param('ssss', $username, $email, $hashed_password, $full_name);

        try {
            $stmt->execute();
            header("Location: index.php");
            exit;
        } catch (mysqli_sql_exception $e) {
            if ($stmt->errno == 1062) {
                $error = 'Ez a felhasználónév már létezik.';
            } else {
                $error = 'Hiba!';
            }
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regisztráció</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="content-box">
        <h1>Regisztráció</h1>
        <?php if ($error): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form action="register.php" method="POST">
            <input type="email" name="email" placeholder="E-mail" required class="form-input"><br>
            <input type="text" name="username" placeholder="Felhasználónév" minlength="3" required class="form-input"><br>
            <input type="text" name="full_name" placeholder="Teljes Név" minlength="4" required class="form-input"><br>
            <input type="password" name="password" placeholder="Jelszó" pattern="(?=.*[0-9])(?=.*[a-zA-Z]).{6,}" title="A jelszónak legalább 6 karakter hosszúnak kell lennie, és tartalmaznia kell betűt és számot!" required class="form-input"><br>
            <input type="password" name="confirm_password" placeholder="Jelszó Megerősítése" required class="form-input"><br>
            <button type="submit" class="submit-button">Regisztráció</button>
        </form>
    </div>
</body>
</html>