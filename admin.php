<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['admin']) || $_SESSION['admin'] != 1) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

function fetchData($conn, $query) {
    $data = [];
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
    }
    return $data;
}

$teams = [];
$stmt = $conn->prepare("SELECT id, nev FROM csapat");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $teams[] = $row;
    }
    $stmt->close();
}
$players = fetchData($conn, "SELECT id, nev FROM tag");
$matches = fetchData($conn, "SELECT id, csapat1, csapat2, eredmeny1, eredmeny2, datum, helyszin FROM merkozes");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_match'])) {
        $csapat1 = $_POST['csapat1'];
        $csapat2 = $_POST['csapat2'];
        $eredmeny1 = $_POST['eredmeny1'];
        $eredmeny2 = $_POST['eredmeny2'];
        $datum = $_POST['datum'];
        $helyszin = $_POST['helyszin'];

        if ($csapat1 == $csapat2) {
            $error = 'Egy csapat nem játszhat önmaga ellen!';
        } else {
            $stmt = $conn->prepare("INSERT INTO merkozes (csapat1, csapat2, eredmeny1, eredmeny2, datum, helyszin) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt === false) {
                $error = 'Hiba: ' . htmlspecialchars($conn->error);
            } else {
                $stmt->bind_param('ssiiss', $csapat1, $csapat2, $eredmeny1, $eredmeny2, $datum, $helyszin);
                if ($stmt->execute()) {
                    $success = 'Meccs sikeresen hozzáadva.';
                } else {
                    $error = 'Hiba: ' . htmlspecialchars($stmt->error);
                }
                $stmt->close();
            }
        }
    } elseif (isset($_POST['delete_match'])) {
        $match_id = $_POST['match_id'];

        $stmt = $conn->prepare("DELETE FROM merkozes WHERE id = ?");
        if ($stmt === false) {
            $error = 'Hiba: ' . htmlspecialchars($conn->error);
        } else {
            $stmt->bind_param('i', $match_id);
            if ($stmt->execute()) {
                $success = 'Meccs sikeresen törölve.';
            } else {
                $error = 'Hiba: ' . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        }
    } elseif (isset($_POST['add_team'])) {
        $nev = $_POST['nev'];
        $varos = $_POST['varos'];
        $alapitas_eve = $_POST['alapitas_eve'];

        $stmt = $conn->prepare("INSERT INTO csapat (nev, varos, alapitas_eve) VALUES (?, ?, ?)");
        if ($stmt === false) {
            $error = 'Hiba: ' . htmlspecialchars($conn->error);
        } else {
            $stmt->bind_param('sss', $nev, $varos, $alapitas_eve);
            if ($stmt->execute()) {
                $success = 'Csapat sikeresen hozzáadva.';
            } else {
                $error = 'Hiba: ' . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        }
    } elseif (isset($_POST['delete_team'])) {
        $team_id = $_POST['team_id'];

        $stmt = $conn->prepare("DELETE FROM csapat WHERE id = ?");
        if ($stmt === false) {
            $error = 'Hiba: ' . htmlspecialchars($conn->error);
        } else {
            $stmt->bind_param('i', $team_id);
            if ($stmt->execute()) {
                $success = 'Csapat sikeresen törölve.';
            } else {
                $error = 'Hiba: ' . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        }
    } elseif (isset($_POST['add_member'])) {
        $nev = $_POST['nev'];
        $allampolgarsag = $_POST['allampolgarsag'];
        $szuletesi_datum = $_POST['szuletesi_datum'];
        $poszt = $_POST['poszt'];
        $csapat_id = $_POST['csapat_id'];

        $stmt = $conn->prepare("INSERT INTO tag (nev, allampolgarsag, szuletesi_datum, poszt, csapat_id) VALUES (?, ?, ?, ?, ?)");
        if ($stmt === false) {
            $error = 'Hiba: ' . htmlspecialchars($conn->error);
        } else {
            $stmt->bind_param('ssssi', $nev, $allampolgarsag, $szuletesi_datum, $poszt, $csapat_id);
            if ($stmt->execute()) {
                $success = 'Tag sikeresen hozzáadva.';
            } else {
                $error = 'Hiba: ' . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        }
    } elseif (isset($_POST['delete_member'])) {
        $member_id = $_POST['member_id'];

        $stmt = $conn->prepare("DELETE FROM tag WHERE id = ?");
        if ($stmt === false) {
            $error = 'Hiba: ' . htmlspecialchars($conn->error);
        } else {
            $stmt->bind_param('i', $member_id);
            if ($stmt->execute()) {
                $success = 'Tag sikeresen törölve.';
            } else {
                $error = 'Hiba: ' . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="content-box">
        <h1>Admin Oldal</h1>
        <?php if ($error): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p style="color: green;"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        
        <h2>Meccs Hozzáadása</h2>
        <form action="admin.php" method="POST">
            <select name="csapat1" required class="form-input">
                <option value="">Csapat 1 Kiválasztása</option>
                <?php foreach ($teams as $team): ?>
                    <option value="<?= htmlspecialchars($team['id']) ?>"><?= htmlspecialchars($team['nev']) ?> (<?= htmlspecialchars($team['id']) ?>)</option>
                <?php endforeach; ?>
            </select><br>
            <select name="csapat2" required class="form-input">
                <option value="">Csapat 2 Kiválasztása</option>
                <?php foreach ($teams as $team): ?>
                    <option value="<?= htmlspecialchars($team['id']) ?>"><?= htmlspecialchars($team['nev']) ?> (<?= htmlspecialchars($team['id']) ?>)</option>
                <?php endforeach; ?>
            </select><br>
            <input type="text" name="eredmeny1" placeholder="Eredmény 1" required class="form-input"><br>
            <input type="text" name="eredmeny2" placeholder="Eredmény 2" required class="form-input"><br>
            <input type="date" name="datum" placeholder="Dátum" required class="form-input"><br>
            <input type="text" name="helyszin" placeholder="Helyszín" required class="form-input"><br>
            <button type="submit" name="add_match" class="submit-button">Meccs Hozzáadása</button>
        </form>
    </div>

    <div class="content-box">
        <h2>Meccs Törlése</h2>
        <form action="admin.php" method="POST">
            <input type="number" name="match_id" required placeholder="Meccs ID" required class="form-input"><br>
            <button type="submit" name="delete_match" class="submit-button">Meccs Törlése</button>
        </form>
    </div>

    <div class="content-box">
        <h2>Csapat Hozzáadása</h2>
        <form action="admin.php" method="POST">
            <input type="text" name="nev" placeholder="Csapat Neve" required class="form-input"><br>
            <input type="text" name="varos" placeholder="Város" required class="form-input"><br>
            <input type="number" name="alapitas_eve" placeholder="Alapítás Éve" required class="form-input"><br>
            <button type="submit" name="add_team" class="submit-button">Csapat Hozzáadása</button>
        </form>
    </div>

    <div class="content-box">
        <h2>Csapat Törlése</h2>
        <form action="admin.php" method="POST">
            <select name="team_id" required class="form-input">
                <option value="">Csapat Kiválasztása</option>
                <?php foreach ($teams as $team): ?>
                    <option value="<?= htmlspecialchars($team['id']) ?>"><?= htmlspecialchars($team['nev']) ?> (<?= htmlspecialchars($team['id']) ?>)</option>
                <?php endforeach; ?>
            </select><br>
            <button type="submit" name="delete_team" class="submit-button">Csapat Törlése</button>
        </form>
    </div>

    <div class="content-box">
        <h2>Tag Hozzáadása</h2>
        <form action="admin.php" method="POST">
            <input type="text" name="nev" placeholder="Név" required class="form-input"><br>
            <input type="text" name="allampolgarsag" placeholder="Nemzetiség" required class="form-input"><br>
            <input type="date" name="szuletesi_datum" placeholder="Születési Dátum" required class="form-input"><br>
            <select name="poszt" required class="form-input">
                <option value="">Poszt Kiválasztása</option>
                <option value="Hajtó">Hajtó</option>
                <option value="Ütő">Ütő</option>
                <option value="Örző">Örző</option>
                <option value="Fogó">Fogó</option>
            </select><br>
            <select name="csapat_id" required class="form-input">
                <option value="">Csapat Kiválasztása</option>
                <?php foreach ($teams as $team): ?>
                    <option value="<?= htmlspecialchars($team['id']) ?>"><?= htmlspecialchars($team['nev']) ?> (<?= htmlspecialchars($team['id']) ?>)</option>
                <?php endforeach; ?>
            </select><br>
            <button type="submit" name="add_member" class="submit-button">Tag Hozzáadása</button>
        </form>
    </div>

    <div class="content-box">
        <h2>Tag Törlése</h2>
        <form action="admin.php" method="POST">
            <input type="number" name="member_id" placeholder="Tag ID" required class="form-input"><br>
            <button type="submit" name="delete_member" class="submit-button">Tag Törlése</button>
        </form>
    </div>
</body>
</html>