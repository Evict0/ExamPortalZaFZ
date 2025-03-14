<?php
session_start();

// Uključi datoteku za konekciju s bazom
require_once 'db_connection.php';  // Uključivanje db_connection.php

$poruka = "";

// Process the login form if submitted (POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve input values from the form
    $inputUsername = trim($_POST['username'] ?? '');
    $inputPassword = trim($_POST['password'] ?? '');

    // Check that both fields are filled
    if (empty($inputUsername) || empty($inputPassword)) {
        $poruka = "Molim unesite korisničko ime i lozinku.";
    } else {
        // Fetch the user from the ep_korisnik table (including razred_id)
        $sql = "SELECT ID, ime, lozinka, razinaID, razred_id 
                FROM ep_korisnik 
                WHERE ime = :ime
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':ime', $inputUsername);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check MD5 hash of the password
            if ($user['lozinka'] === md5($inputPassword)) {
                // Store important data in the session
                $_SESSION['user_id'] = $user['ID'];
                $_SESSION['razina']  = $user['razinaID'];
                $_SESSION['razred_id'] = $user['razred_id']; // Save the user’s grade

                // After successful login, redirect to topic selection
                header("Location: odabir_teme.php");
                exit();
            } else {
                $poruka = "Neispravna lozinka.";
            }
        } else {
            $poruka = "Korisnik ne postoji.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Prijava</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Container for login */
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background: #1c1c1c;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
            color: #fff;
        }
        .login-container h2 {
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #ff00ff;
            border-radius: 5px;
            background: #2a2a2a;
            color: #fff;
            font-size: 1rem;
        }
        .neon-button {
            background-color: #ff00ff;
            color: #fff;
            padding: 14px 28px;
            font-size: 1.1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 0 5px #ff00ff, 0 0 10px #ff00ff;
        }
        .neon-button:hover {
            background-color: #d100d1;
            box-shadow: 0 0 10px #ff00ff, 0 0 20px #ff00ff;
        }
        .error {
            color: #ff2e2e;
            font-weight: bold;
            margin-top: 10px;
            text-shadow: 0 0 5px #ff2e2e;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Prijava</h2>
        <!-- Error message if any -->
        <?php if (!empty($poruka)): ?>
            <p id="login-message" class="error"><?= htmlspecialchars($poruka) ?></p>
        <?php endif; ?>

        <!-- Login form -->
        <form method="POST">
            <div class="form-group">
                <label for="username">Korisničko ime:</label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="form-group">
                <label for="password">Lozinka:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit" class="neon-button">Prijavi se</button>
        </form>
        <br>
        <!-- Button to go to registration -->
        <a href="registracija.php" style="text-decoration:none;">
            <button type="button" class="neon-button">Registracija</button>
        </a>
    </div>
</body>
</html>
