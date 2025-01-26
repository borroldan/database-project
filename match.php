<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");    
    exit;
}
include 'db.php';

$search = $_GET['search'] ?? '';
$team_filter = $_GET['team'] ?? '';
$score_filter = $_GET['score'] ?? '';

$query = "
    SELECT 
        m.id, 
        c1.nev AS csapat1_nev, 
        c2.nev AS csapat2_nev, 
        m.eredmeny1, 
        m.eredmeny2, 
        m.datum, 
        m.helyszin 
    FROM 
        merkozes m
    JOIN 
        csapat c1 ON m.csapat1 = c1.id
    JOIN 
        csapat c2 ON m.csapat2 = c2.id
    WHERE 
        (c1.nev LIKE ? OR c2.nev LIKE ? OR m.eredmeny1 LIKE ? OR m.eredmeny2 LIKE ?)
";
$params = ["%$search%", "%$search%", "%$search%", "%$search%"];

if ($team_filter) {
    $query .= " AND (c1.nev = ? OR c2.nev = ?)";
    $params[] = $team_filter;
    $params[] = $team_filter;
}

if ($score_filter) {
    $query .= " AND (m.eredmeny1 = ? OR m.eredmeny2 = ?)";
    $params[] = $score_filter;
    $params[] = $score_filter;
}

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die('Hiba: ' . htmlspecialchars($conn->error));
}

$types = str_repeat('s', count($params));
$stmt->bind_param($types, ...$params);

$stmt->execute();
$result = $stmt->get_result();
$matches = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();

$teamPlayerCounts = [];
$stmt = $conn->prepare("SELECT Csapat.nev AS CsapatNeve, COUNT(Tag.id) AS TagokSzama FROM Csapat JOIN Tag ON Csapat.id = Tag.csapat_id GROUP BY Csapat.nev");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $teamPlayerCounts[] = $row;
    }
    $stmt->close();
}


$count = $_GET['count'] ?? 0;

$teamsWithMoreMatches = [];
$stmt = $conn->prepare("
    SELECT c.nev, COUNT(m.id) AS match_count
    FROM csapat c
    LEFT JOIN merkozes m ON c.id = m.csapat1 OR c.id = m.csapat2
    GROUP BY c.id
    HAVING match_count > ?
");
if ($stmt) {
    $stmt->bind_param('i', $count);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $teamsWithMoreMatches[] = $row;
    }
    $stmt->close();
}


$teamScores = [];
$stmt = $conn->prepare("
    SELECT Csapat.nev AS CsapatNeve, 
           SUM(CASE 
               WHEN Merkozes.csapat1 = Csapat.id THEN Merkozes.eredmeny1 
               WHEN Merkozes.csapat2 = Csapat.id THEN Merkozes.eredmeny2 
               ELSE 0 
           END) AS OsszesitettPontszam
    FROM Csapat
    JOIN Merkozes ON Csapat.id = Merkozes.csapat1 OR Csapat.id = Merkozes.csapat2
    GROUP BY Csapat.nev
");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $teamScores[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meccsek</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="content-box">
        <h1>Meccs Eredmények</h1>
        <form action="match.php" method="GET">
            <input type="text" name="search" placeholder="Általános keresés" value="<?= htmlspecialchars($search) ?>" class="form-input">
            <input type="text" name="team" placeholder="Szűrés csapatra" value="<?= htmlspecialchars($team_filter) ?>" class="form-input">
            <input type="text" name="score" placeholder="Szűrés pontszámra" value="<?= htmlspecialchars($score_filter) ?>" class="form-input">
            <input type="number" name="count" placeholder="Minimum meccs szám" value="<?= htmlspecialchars($count) ?>" class="form-input">
            <button type="submit" class="submit-button">Keresés</button>
        </form>
        
        <div class="match-cards">
            <?php foreach ($matches as $match): ?>
                <div class="match-card">
                    <h2><?= htmlspecialchars($match['csapat1_nev']) ?> vs <?= htmlspecialchars($match['csapat2_nev']) ?></h2>
                    <p>Pontok: <?= htmlspecialchars($match['eredmeny1']) ?>-<?= htmlspecialchars($match['eredmeny2']) ?></p>
                    <p>Dátum: <?= htmlspecialchars($match['datum']) ?></p>
                    <p>Helyszín: <?= htmlspecialchars($match['helyszin']) ?></p>
                    <?php
                    $score1 = (int)$match['eredmeny1'];
                    $score2 = (int)$match['eredmeny2'];
                    if ($score1 > $score2) {
                        echo "<p>Eredmény: " . htmlspecialchars($match['csapat1_nev']) . " nyert</p>";
                    } elseif ($score1 < $score2) {
                        echo "<p>Eredmény: " . htmlspecialchars($match['csapat2_nev']) . " nyert</p>";
                    } else {
                        echo "<p>Eredmény: Döntetlen</p>";
                    }
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="content-box">
    <h2>Csapatok és Tagok Száma</h2><br>
    <table class="content-table">
        <thead>
            <tr>
                <th>Csapat Neve</th>
                <th>Tagok Száma</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($teamPlayerCounts as $team): ?>
                <tr>
                    <td><?= htmlspecialchars($team['CsapatNeve']) ?></td>
                    <td><?= htmlspecialchars($team['TagokSzama']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <div class="content-box">
    <h2>Csapatok, melyek több meccset játszottak mint <?= htmlspecialchars($count) ?></h2><br>
    <table class="content-table">
        <thead>
            <tr>
                <th>Csapat Neve</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($teamsWithMoreMatches as $team): ?>
                <tr>
                    <td><?= htmlspecialchars($team['nev']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <div class="content-box">
    <h2>Csapatok és Összesített Pontok</h2><br>
    <table class="content-table">
        <thead>
            <tr>
                <th>Csapat</th>
                <th>Összpontszám</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($teamScores as $team): ?>
                <tr>
                    <td><?= htmlspecialchars($team['CsapatNeve']) ?></td>
                    <td><?= htmlspecialchars($team['OsszesitettPontszam']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</body>
</html>