<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Uključi datoteku za konekciju s bazom
require_once 'db_connection.php';  // Uključivanje db_connection.php

// Preuzmi podatke o korisniku
$userId    = $_SESSION['user_id'];
$userLevel = $_SESSION['razina'] ?? 2; // 2 = student by default

$poruka = "";

// Dohvati povijest ispita za ulogiranog korisnika.
// Pretpostavimo da tablica ep_test ima primarni ključ ID (kao exam_id).
$historySql = "
    SELECT e.ID AS exam_id, e.vrijeme_kraja, e.trajanje, e.ukupno_pitanja, e.tocno_odgovori, e.netocno_odgovori, e.kviz_id,
           t.naziv AS theme_name
    FROM ep_test e
    LEFT JOIN ep_teme t ON e.kviz_id = t.ID
    WHERE e.korisnikID = :userId
    ORDER BY e.vrijeme_kraja DESC
";
$historyStmt = $pdo->prepare($historySql);
$historyStmt->execute([':userId' => $userId]);
$examHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

// Dohvati teme za odabir (ovisno o razini korisnika)
if ($userLevel == 1) {
    // Profesor (razina 1) vidi sve teme
    $stmt = $pdo->prepare("
        SELECT t.ID AS theme_id, 
               t.naziv AS theme_name, 
               COUNT(p.ID) AS broj_pitanja
        FROM ep_teme t
        LEFT JOIN ep_pitanje p ON p.temaID = t.ID
        GROUP BY t.ID, t.naziv
        ORDER BY t.naziv
    ");
    $stmt->execute();
    $korisnikTeme = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Student (razina 2) vidi samo one teme koje su mu dodijeljene
    $stmt = $pdo->prepare(" 
        SELECT t.ID AS theme_id, 
               t.naziv AS theme_name, 
               COUNT(p.ID) AS broj_pitanja
        FROM ep_teme t
        INNER JOIN ep_korisnik_teme kt ON kt.tema_id = t.ID
        LEFT JOIN ep_pitanje p ON p.temaID = t.ID
        WHERE kt.korisnik_id = ?
        GROUP BY t.ID, t.naziv
        ORDER BY t.naziv
    ");
    $stmt->execute([$userId]);
    $korisnikTeme = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Odabir teme i Povijest ispita</title>
    <style>
        /* Base reset i font */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            background: #000;
            color: #fff;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        h1, h2 {
            text-align: center;
            color: #40ffe5;
            text-shadow: 0 0 8px #40ffe5, 0 0 20px #40ffe5;
            margin-bottom: 20px;
        }
        /* Povijest ispita – skrivena po defaultu */
        .exam-history {
            margin-bottom: 40px;
            display: none; /* sakrij po defaultu */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ff00ff;
            text-align: left;
        }
        th {
            background-color: rgba(255, 0, 255, 0.2);
            text-shadow: 0 0 5px #ff00ff;
        }
        tr:nth-child(even) {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .percentage {
            font-weight: bold;
        }
        /* Odabir teme */
        .odabir-teme-container {
            background: linear-gradient(145deg, #111, #000);
            border: 2px solid #ff00ff;
            border-radius: 12px;
            box-shadow: 0 0 25px rgba(255, 0, 255, 0.2),
                        0 0 60px rgba(255, 0, 255, 0.1);
            padding: 30px;
            text-align: center;
        }
        .teacher-link {
            display: inline-block;
            margin-bottom: 25px;
            padding: 16px 32px;
            font-size: 1.2rem;
            font-weight: bold;
            text-transform: uppercase;
            color: #fff;
            background: linear-gradient(45deg, #ff00ff, #d100d1);
            border: none;
            border-radius: 8px;
            box-shadow: 0 0 10px #ff00ff, 0 0 20px #ff00ff;
            text-decoration: none;
            transition: 0.3s;
        }
        .teacher-link:hover {
            background: linear-gradient(45deg, #d100d1, #ff00ff);
            box-shadow: 0 0 20px #ff00ff, 0 0 40px #ff00ff;
            transform: scale(1.05);
        }
        form {
            margin-top: 10px;
        }
        .theme-button {
            display: block;
            width: 90%;
            max-width: 600px;
            margin: 15px auto;
            padding: 14px 0;
            background: linear-gradient(45deg, #ff00ff, #d100d1);
            color: #fff;
            font-size: 1.2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            box-shadow: 0 0 10px #ff00ff, 0 0 20px #ff00ff;
            transition: 0.3s ease;
        }
        .theme-button:hover {
            background: linear-gradient(45deg, #d100d1, #ff00ff);
            box-shadow: 0 0 20px #ff00ff, 0 0 40px #ff00ff;
            transform: scale(1.03);
        }
        /* Gumb za prikaz povijesti ispita */
        .history-button {
            display: block;
            width: 200px;
            margin: 20px auto;
            padding: 12px;
            background: linear-gradient(45deg, #ff00ff, #d100d1);
            color: #fff;
            font-size: 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            box-shadow: 0 0 10px #ff00ff, 0 0 20px #ff00ff;
            transition: 0.3s ease;
        }
        .history-button:hover {
            background: linear-gradient(45deg, #d100d1, #ff00ff);
            box-shadow: 0 0 20px #ff00ff, 0 0 40px #ff00ff;
            transform: scale(1.03);
        }
        /* Gumb "Pregled" u povijesti */
        .preview-button {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(45deg, #ff00ff, #d100d1);
            color: #fff;
            font-size: 0.9rem;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            transition: 0.3s ease;
        }
        .preview-button:hover {
            background: linear-gradient(45deg, #d100d1, #ff00ff);
            transform: scale(1.03);
        }
    </style>
    <script>
        function toggleExamHistory() {
            var historyDiv = document.getElementById("exam-history");
            if (historyDiv.style.display === "none" || historyDiv.style.display === "") {
                historyDiv.style.display = "block";
            } else {
                historyDiv.style.display = "none";
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Odabir teme</h1>
        
        <!-- Gumb za prikaz/pokri povijesti ispita -->
        <button class="history-button" onclick="toggleExamHistory()">Povijest ispita</button>
        
        <!-- Povijest ispita, skrivena po defaultu -->
        <div id="exam-history" class="exam-history">
            <h2>Povijest ispita</h2>
            <?php if (!empty($examHistory)): ?>
                <table>
                    <tr>
                        <th>Datum ispita</th>
                        <th>Tematika ispita</th>
                        <th>Trajanje</th>
                        <th>Točni</th>
                        <th>Netočni</th>
                        <th>Postotak točnih</th>
                        <th>Pregled</th>
                    </tr>
                    <?php foreach ($examHistory as $exam): 
                        $ukupno   = $exam['ukupno_pitanja'];
                        $tocno    = $exam['tocno_odgovori'];
                        $netocno  = $exam['netocno_odgovori'];
                        $postotak = ($ukupno > 0) ? round(($tocno / $ukupno) * 100, 2) : 0;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($exam['vrijeme_kraja']) ?></td>
                        <td><?= htmlspecialchars($exam['theme_name'] ?? 'Nedefinirano') ?></td>
                        <td><?= htmlspecialchars($exam['trajanje']) ?></td>
                        <td><?= htmlspecialchars($tocno) ?></td>
                        <td><?= htmlspecialchars($netocno) ?></td>
                        <td class="percentage"><?= $postotak ?>%</td>
                        <td>
                            <!-- Gumb "Pregled" koji preusmjerava na forms.php s exam_id -->
                            <a href="forms.php?exam_id=<?= htmlspecialchars($exam['exam_id']) ?>" class="preview-button">
                                Pregled
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p style="text-align:center;">Nema povijesti ispita.</p>
            <?php endif; ?>
        </div>
        
        <!-- Odabir teme -->
        <div class="odabir-teme-container">
            <h2>Odaberite temu</h2>
            <?php if ($userLevel == 1): ?>
                <a href="dodaj_pitanje.php" class="teacher-link">+ DODAJ NOVO PITANJE</a>
            <?php endif; ?>
            <form action="index.php" method="POST">
                <?php foreach ($korisnikTeme as $tema): ?>
                    <button 
                        class="theme-button" 
                        type="submit" 
                        name="tema_id" 
                        value="<?= htmlspecialchars($tema['theme_id']) ?>">
                        <?= htmlspecialchars($tema['theme_name']) ?>
                        (<?= htmlspecialchars($tema['broj_pitanja']) ?>)
                    </button>
                <?php endforeach; ?>
            </form>
        </div>
    </div>
</body>
</html>
