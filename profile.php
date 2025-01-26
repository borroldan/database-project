<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM felhasznalo WHERE id = ?");
if ($stmt === false) {
    die('Hiba: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $new_username = $_POST['felhasznalonev'];
        $new_email = $_POST['email'];
        $new_name = $_POST['nev'];

        $stmt = $conn->prepare("UPDATE felhasznalo SET felhasznalonev = ?, email = ?, nev = ? WHERE id = ?");
        if ($stmt === false) {
            $error = 'Hiba: ' . htmlspecialchars($conn->error);
        } else {
            $stmt->bind_param('sssi', $new_username, $new_email, $new_name, $user_id);
            if ($stmt->execute()) {
                $success = 'Profil sikeresen frissítve.';
                $_SESSION['felhasznalonev'] = $new_username;
                $_SESSION['email'] = $new_email;
                $_SESSION['nev'] = $new_name;
            } else {
                $error = 'Hiba: ' . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        }
    } elseif (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (!password_verify($current_password, $user['jelszo'])) {
            $error = 'A Jelenlegi Jelszó helytelen!';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Az Új Jelszók nem egyeznek!';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE felhasznalo SET jelszo = ? WHERE id = ?");
            if ($stmt === false) {
                $error = 'Hiba: ' . htmlspecialchars($conn->error);
            } else {
                $stmt->bind_param('si', $hashed_password, $user_id);
                if ($stmt->execute()) {
                    $success = 'Jelszó sikeresen frissítve.';
                } else {
                    $error = 'Hiba: ' . htmlspecialchars($stmt->error);
                }
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="content-box">
        <h1>Profil</h1>
        <table class="content-table">
            <tr>
                <th>Felhasználónév</th>
                <td><?= htmlspecialchars($user['felhasznalonev']) ?></td>
            </tr>
            <tr>
                <th>Teljes Név</th>
                <td><?= htmlspecialchars($user['nev']) ?></td>
            </tr>
            <tr>
                <th>E-mail</th>
                <td><?= htmlspecialchars($user['email']) ?></td>
            </tr>
        </table>
    </div>
    <div class="content-box">
        <h1>Profil szerkesztése</h1>
        <?php if ($error): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p style="color: green;"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>
        <form action="profile.php" method="POST">
            <input type="text" name="felhasznalonev" placeholder="Felhasználónév" value="<?= htmlspecialchars($user['felhasznalonev']) ?>" required class="form-input"><br>
            <input type="email" name="email" placeholder="E-mail" value="<?= htmlspecialchars($user['email']) ?>" required class="form-input"><br>
            <input type="text" name="nev" placeholder="Teljes név" value="<?= htmlspecialchars($user['nev']) ?>" required class="form-input"><br>
            <button type="submit" name="update_profile" class="submit-button">Profil Frissítése</button>
        </form>
    </div>
    <div class="content-box">
        <h1>Jelszó módosítása</h1>
        <form action="profile.php" method="POST">
            <input type="password" name="current_password" placeholder="Jelenlegi Jelszó" required class="form-input"><br>
            <input type="password" name="new_password" placeholder="Új Jelszó" pattern="(?=.*[0-9])(?=.*[a-zA-Z]).{6,}" title="A jelszónak legalább 6 karakter hosszúnak kell lennie, és tartalmaznia kell betűt és számot!" required class="form-input"><br>
            <input type="password" name="confirm_password" placeholder="Új Jelszó Megerősítése" required class="form-input"><br>
            <button type="submit" name="update_password" class="submit-button">Jelszó Frissítése</button>
        </form>
    </div>
</body>
</html>