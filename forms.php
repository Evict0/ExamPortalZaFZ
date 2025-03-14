<?php
session_start();

// Provjera postoji li exam_id u GET-u
if (isset($_GET['exam_id'])) {
    // === REŽIM PREGLEDA RANIJEG ISPITA ===
    $exam_id = intval($_GET['exam_id']);

    // Uključi datoteku za konekciju s bazom
    require_once 'db_connection.php';  // Uključivanje db_connection.php

    // Dohvati osnovne podatke o ispitu iz ep_test
    $stmt = $pdo->prepare("SELECT * FROM ep_test WHERE ID = :exam_id LIMIT 1");
    $stmt->execute([':exam_id' => $exam_id]);
    $exam = $stmt->fetch();
    if (!$exam) {
        die("Ispit nije pronađen.");
    }

    // Dohvati sve odgovore iz ep_test_odgovori za ovaj ispit
    $stmt2 = $pdo->prepare("SELECT * FROM ep_test_odgovori WHERE test_id = :exam_id");
    $stmt2->execute([':exam_id' => $exam_id]);
    $allAnswers = $stmt2->fetchAll();

    // Razdvoji točne i netočne odgovore
    $correctAnswers = [];
    $wrongAnswers   = [];
    foreach ($allAnswers as $row) {
        $entry = [
            "question"       => $row["question_text"],
            "your_answer"    => $row["user_answer_text"],
            "correct_answer" => $row["correct_answer_text"],
            "explanation"    => $row["explanation"]
        ];
        if ($row["is_correct"] == 1) {
            $correctAnswers[] = $entry;
        } else {
            $wrongAnswers[]   = $entry;
        }
    }

    // Varijable za prikaz
    $score           = $exam['rezultat'];
    $totalQuestions  = $exam['ukupno_pitanja'];
    $correctCount    = $exam['tocno_odgovori'];
    $incorrectCount  = $exam['netocno_odgovori'];
    $trajanje        = $exam['trajanje'];
    $vrijeme_pocetka = $exam['vrijeme_pocetka'];
    $vrijeme_kraja   = $exam['vrijeme_kraja'];
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Rezultati Kviza - Pregled</title>
        <style>
            /* Slični stilovi kao kod završetka kviza */
            * {
                margin: 0; 
                padding: 0; 
                box-sizing: border-box;
                font-family: 'Poppins', sans-serif;
            }
            body {
                background: #090909;
                background-image: linear-gradient(145deg, #1b1b1b, #000);
                color: #fff;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                padding: 20px;
            }
            .results-container {
                width: 100%;
                max-width: 1200px;
                background: linear-gradient(145deg, #1b1b1b, #000);
                border: 2px solid #ff00ff;
                box-shadow: 0 0 20px rgba(255, 0, 255, 0.2), 0 0 60px rgba(255, 0, 255, 0.1);
                border-radius: 12px;
                padding: 30px;
            }
            h1, h2 {
                text-align: center;
                color: #ffae00;
                text-shadow: 0 0 8px #ffae00;
                margin-bottom: 20px;
            }
            .score {
                text-align: center;
                font-size: 1.6rem;
                margin-bottom: 30px;
                color: #40ffe5;
                text-shadow: 0 0 5px #40ffe5;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
            }
            th, td {
                padding: 12px;
                text-align: left;
                border: 1px solid #ff00ff;
                color: #fff;
            }
            th {
                background-color: rgba(255, 0, 255, 0.2);
                text-shadow: 0 0 5px #ff00ff;
            }
            .correct {
                background-color: rgba(200, 230, 201, 0.2);
                box-shadow: inset 0 0 10px #40ffe5;
            }
            .incorrect {
                background-color: rgba(255, 205, 210, 0.2);
                box-shadow: inset 0 0 10px #ff00ff;
            }
            .results-btn {
                display: block;
                width: 250px;
                margin: 0 auto 20px auto;
                padding: 14px 28px;
                background-color: #ff00ff;
                color: #fff;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 1.1rem;
                text-align: center;
                box-shadow: 0 0 10px #ff00ff, 0 0 20px #ff00ff;
                transition: 0.3s ease;
            }
            .results-btn:hover {
                background-color: #d100d1;
                box-shadow: 0 0 15px #ff00ff, 0 0 30px #ff00ff;
            }
        </style>
    </head>
    <body>
    <div class="results-container">
        <h1>Rezultati Kviza - Pregled</h1>
        <p class="score">
            <strong>Ukupno bodova:</strong> <?= htmlspecialchars($score) ?> 
            od <?= htmlspecialchars($totalQuestions) ?>
        </p>
        <p style="text-align:center;">
            <strong>Trajanje:</strong> <?= htmlspecialchars($trajanje) ?><br>
            <strong>Vrijeme početka:</strong> <?= htmlspecialchars($vrijeme_pocetka) ?><br>
            <strong>Vrijeme završetka:</strong> <?= htmlspecialchars($vrijeme_kraja) ?>
        </p>

        <h2>Točni Odgovori</h2>
        <?php if (!empty($correctAnswers)) { ?>
            <table>
                <tr>
                    <th>Pitanje</th>
                    <th>Vaš Odgovor</th>
                    <th>Točan Odgovor</th>
                    <th>Objašnjenje</th>
                </tr>
                <?php foreach ($correctAnswers as $item) { ?>
                <tr class="correct">
                    <td><?= htmlspecialchars($item["question"]) ?></td>
                    <td><?= htmlspecialchars($item["your_answer"]) ?></td>
                    <td><?= htmlspecialchars($item["correct_answer"]) ?></td>
                    <td><?= htmlspecialchars($item["explanation"]) ?></td>
                </tr>
                <?php } ?>
            </table>
        <?php } else { ?>
            <p style="text-align:center;">Niste imali točnih odgovora.</p>
        <?php } ?>

        <h2>Netočni Odgovori</h2>
        <?php if (!empty($wrongAnswers)) { ?>
            <table>
                <tr>
                    <th>Pitanje</th>
                    <th>Vaš Odgovor</th>
                    <th>Točan Odgovor</th>
                    <th>Objašnjenje</th>
                </tr>
                <?php foreach ($wrongAnswers as $item) { ?>
                <tr class="incorrect">
                    <td><?= htmlspecialchars($item["question"]) ?></td>
                    <td><?= htmlspecialchars($item["your_answer"]) ?></td>
                    <td><?= htmlspecialchars($item["correct_answer"]) ?></td>
                    <td><?= htmlspecialchars($item["explanation"]) ?></td>
                </tr>
                <?php } ?>
            </table>
        <?php } else { ?>
            <p style="text-align:center;">Svi odgovori su točni!</p>
        <?php } ?>

        <form action="odabir_teme.php" method="post">
            <input type="submit" name="retry" value="Pokušaj ponovo" class="results-btn">
        </form>
    </div>
    </body>
    </html>
    <?php
    exit();
} else {
    // === REŽIM OBRADE KVIZA (nakon što je korisnik završio ispunjavanje) ===

    /**
     * Funkcija za dohvaćanje pitanja s index.php?getQuestions=1
     */
    function loadQuizQuestions() {
        $host = $_SERVER['HTTP_HOST'];
        $uri  = rtrim(dirname($_SERVER['REQUEST_URI']), '/\\');
        $query = "getQuestions=1";

        // Ako postoji 'tema' u POST-u ili u sesiji, dodaj ju u URL
        if (isset($_POST['tema']) && trim($_POST['tema']) !== '') {
            $tema = urlencode($_POST['tema']);
            $query .= "&tema=" . $tema;
        } elseif (isset($_SESSION['temaID']) && trim($_SESSION['temaID']) !== '') {
            $tema = urlencode($_SESSION['temaID']);
            $query .= "&tema=" . $tema;
        }

        $url = "http://$host$uri/index.php?$query";
        $json = @file_get_contents($url);
        if ($json === false) {
            die("Greška pri dohvaćanju pitanja iz index.php");
        }
        $questions = json_decode($json, true);
        if (!is_array($questions)) {
            die("Greška pri dekodiranju JSON-a s pitanjima");
        }
        return $questions;
    }

    // Učitaj pitanja
    $questions = loadQuizQuestions();

    $correctAnswers = [];
    $wrongAnswers   = [];
    $score = 0;

    // Obrada svakog pitanja
    foreach ($questions as $index => $question) {
        $qKey = "question" . $index;
        $userAnswer = $_POST[$qKey] ?? "";

        // Odgovori su pipe-odvojeni
        $answerOptions = explode("|", $question["answers"]);
        $correctIndex  = $question["correctAnswer"];

        $correctAnswerText = $answerOptions[$correctIndex] ?? "Nije definirano";
        $userAnswerText    = isset($answerOptions[$userAnswer]) ? $answerOptions[$userAnswer] : "Nije odabrano";
        $explanation       = $question["hint"] ?: "Nema dodatnog objašnjenja.";

        if ((string)$userAnswer === (string)$correctIndex) {
            $score++;
            $correctAnswers[] = [
                "question"       => $question["question"],
                "your_answer"    => $userAnswerText,
                "correct_answer" => $correctAnswerText,
                "explanation"    => $explanation
            ];
        } else {
            $wrongAnswers[] = [
                "question"       => $question["question"],
                "your_answer"    => $userAnswerText,
                "correct_answer" => $correctAnswerText,
                "explanation"    => $explanation
            ];
        }
    }

    $totalQuestions = count($questions);
    $correctCount   = $score;
    $incorrectCount = $totalQuestions - $score;

    // Povezivanje na bazu
    require_once 'db_connection.php';  // Uključivanje db_connection.php

    // Odredi vrijeme početka i kraja
    $vrijeme_pocetka = isset($_SESSION['quiz_start_time']) ? $_SESSION['quiz_start_time'] : date("Y-m-d H:i:s");
    $vrijeme_kraja   = date("Y-m-d H:i:s");
    $diffSec = strtotime($vrijeme_kraja) - strtotime($vrijeme_pocetka);
    if ($diffSec < 0) {
        $diffSec = 0;
    }
    $trajanje = gmdate("H:i:s", $diffSec);

    // ID korisnika iz sesije
    $korisnikID = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

    // Pokušaj dohvatiti kviz_id iz sesije ili POST podataka
    if (isset($_SESSION['temaID']) && $_SESSION['temaID'] > 0) {
        $kviz_id = (int)$_SESSION['temaID'];
    } elseif (isset($_POST['tema']) && $_POST['tema'] > 0) {
        $kviz_id = (int)$_POST['tema'];
    } else {
        $kviz_id = 0;
    }

    $broj_pokusaja = 1;
    $rezultat = $score;

    // Spremi glavni zapis u ep_test
    try {
        $sql = "
            INSERT INTO ep_test 
            (
              korisnikID,
              vrijeme_pocetka,
              vrijeme_kraja,
              kviz_id,
              rezultat,
              ukupno_pitanja,
              tocno_odgovori,
              netocno_odgovori,
              trajanje,
              broj_pokusaja
            )
            VALUES
            (
              :korisnikID,
              :vrijeme_pocetka,
              :vrijeme_kraja,
              :kviz_id,
              :rezultat,
              :ukupno_pitanja,
              :tocno_odgovori,
              :netocno_odgovori,
              :trajanje,
              :broj_pokusaja
            )
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([ 
            ':korisnikID'      => $korisnikID,
            ':vrijeme_pocetka' => $vrijeme_pocetka,
            ':vrijeme_kraja'   => $vrijeme_kraja,
            ':kviz_id'         => $kviz_id,
            ':rezultat'        => $rezultat,
            ':ukupno_pitanja'  => $totalQuestions,
            ':tocno_odgovori'  => $correctCount,
            ':netocno_odgovori'=> $incorrectCount,
            ':trajanje'        => $trajanje,
            ':broj_pokusaja'   => $broj_pokusaja
        ]);
    } catch (Exception $e) {
        die("Greška pri unosu u ep_test: " . $e->getMessage());
    }

    // Dohvati ID novounesenog ispita
    $testId = $pdo->lastInsertId();

    // Spremi svaki odgovor u ep_test_odgovori
    try {
        $sql2 = "
            INSERT INTO ep_test_odgovori
            (test_id, question_text, user_answer_text, correct_answer_text, explanation, is_correct)
            VALUES
            (:test_id, :question_text, :user_answer_text, :correct_answer_text, :explanation, :is_correct)
        ";
        $stmt2 = $pdo->prepare($sql2);

        // Prvo spremi točne odgovore
        foreach ($correctAnswers as $item) {
            $stmt2->execute([
                ':test_id'           => $testId,
                ':question_text'     => $item["question"],
                ':user_answer_text'  => $item["your_answer"],
                ':correct_answer_text'=> $item["correct_answer"],
                ':explanation'       => $item["explanation"],
                ':is_correct'        => 1
            ]);
        }
        // Zatim netočne
        foreach ($wrongAnswers as $item) {
            $stmt2->execute([
                ':test_id'           => $testId,
                ':question_text'     => $item["question"],
                ':user_answer_text'  => $item["your_answer"],
                ':correct_answer_text'=> $item["correct_answer"],
                ':explanation'       => $item["explanation"],
                ':is_correct'        => 0
            ]);
        }
    } catch (Exception $e) {
        die("Greška pri unosu u ep_test_odgovori: " . $e->getMessage());
    }

    // Makni session start time
    unset($_SESSION['quiz_start_time']);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Rezultati Kviza</title>
        <style>
            /* Stilovi za rezultate */
            * {
                margin: 0; 
                padding: 0; 
                box-sizing: border-box;
                font-family: 'Poppins', sans-serif;
            }
            body {
                background: #090909;
                background-image: linear-gradient(145deg, #1b1b1b, #000);
                color: #fff;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                padding: 20px;
            }
            .results-container {
                width: 100%;
                max-width: 1200px;
                background: linear-gradient(145deg, #1b1b1b, #000);
                border: 2px solid #ff00ff;
                box-shadow: 0 0 20px rgba(255, 0, 255, 0.2), 0 0 60px rgba(255, 0, 255, 0.1);
                border-radius: 12px;
                padding: 30px;
            }
            h1, h2 {
                text-align: center;
                color: #ffae00;
                text-shadow: 0 0 8px #ffae00;
                margin-bottom: 20px;
            }
            .score {
                text-align: center;
                font-size: 1.6rem;
                margin-bottom: 30px;
                color: #40ffe5;
                text-shadow: 0 0 5px #40ffe5;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
            }
            th, td {
                padding: 12px;
                text-align: left;
                border: 1px solid #ff00ff;
                color: #fff;
            }
            th {
                background-color: rgba(255, 0, 255, 0.2);
                text-shadow: 0 0 5px #ff00ff;
            }
            .correct {
                background-color: rgba(200, 230, 201, 0.2);
                box-shadow: inset 0 0 10px #40ffe5;
            }
            .incorrect {
                background-color: rgba(255, 205, 210, 0.2);
                box-shadow: inset 0 0 10px #ff00ff;
            }
            .results-btn {
                display: block;
                width: 250px;
                margin: 0 auto 20px auto;
                padding: 14px 28px;
                background-color: #ff00ff;
                color: #fff;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 1.1rem;
                text-align: center;
                box-shadow: 0 0 10px #ff00ff, 0 0 20px #ff00ff;
                transition: 0.3s ease;
            }
            .results-btn:hover {
                background-color: #d100d1;
                box-shadow: 0 0 15px #ff00ff, 0 0 30px #ff00ff;
            }
        </style>
    </head>
    <body>
    <div class="results-container">
        <h1>Rezultati Kviza</h1>
        <p class="score">
            <strong>Ukupno bodova:</strong> <?= $score ?> od <?= $totalQuestions ?>
        </p>

        <h2>Točni Odgovori</h2>
        <?php if (!empty($correctAnswers)) { ?>
            <table>
                <tr>
                    <th>Pitanje</th>
                    <th>Vaš Odgovor</th>
                    <th>Točan Odgovor</th>
                    <th>Objašnjenje</th>
                </tr>
                <?php foreach ($correctAnswers as $item) { ?>
                <tr class="correct">
                    <td><?= htmlspecialchars($item["question"]) ?></td>
                    <td><?= htmlspecialchars($item["your_answer"]) ?></td>
                    <td><?= htmlspecialchars($item["correct_answer"]) ?></td>
                    <td><?= htmlspecialchars($item["explanation"]) ?></td>
                </tr>
                <?php } ?>
            </table>
        <?php } else { ?>
            <p style="text-align:center;">Niste imali točnih odgovora.</p>
        <?php } ?>

        <h2>Netočni Odgovori</h2>
        <?php if (!empty($wrongAnswers)) { ?>
            <table>
                <tr>
                    <th>Pitanje</th>
                    <th>Vaš Odgovor</th>
                    <th>Točan Odgovor</th>
                    <th>Objašnjenje</th>
                </tr>
                <?php foreach ($wrongAnswers as $item) { ?>
                <tr class="incorrect">
                    <td><?= htmlspecialchars($item["question"]) ?></td>
                    <td><?= htmlspecialchars($item["your_answer"]) ?></td>
                    <td><?= htmlspecialchars($item["correct_answer"]) ?></td>
                    <td><?= htmlspecialchars($item["explanation"]) ?></td>
                </tr>
                <?php } ?>
            </table>
        <?php } else { ?>
            <p style="text-align:center;">Svi odgovori su točni!</p>
        <?php } ?>

        <form action="odabir_teme.php" method="post">
            <input type="submit" name="retry" value="Pokušaj ponovo" class="results-btn">
        </form>
    </div>
    </body>
    </html>
    <?php
}
?>
