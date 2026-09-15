<?php
    session_start();

    require_once __DIR__ . '/../utils/Env.php';

    $env = Env::load(__DIR__ . '/../../.env');
    $adminUsername = $env['ADMIN_USERNAME'] ?? null;
    $adminPasswordHash = $env['ADMIN_PASSWORD_HASH'] ?? null;

    if (isset($_GET['logout'])) {
        $_SESSION = [];
        session_destroy();
        header('Location: auth.php');
        exit;
    }

    if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
        header('Location: index.php');
        exit;
    }

    $error = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($adminUsername !== null && $adminPasswordHash !== null
            && $username === $adminUsername && password_verify($password, $adminPasswordHash)) {
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            header('Location: index.php');
            exit;
        }

        $error = 'Identifiant ou mot de passe incorrect.';
    }
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Admin - Connexion</title>

        <link rel="stylesheet" type="text/css" href="/admin/assets/css/styles.css" />
    </head>
    <body>
        <main class="login-page" style="margin:0;">
            <form method="post" action="auth.php" class="login-form">
                <h1>Connexion Admin</h1>

                <?php if ($error): ?>
                    <p class="error"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>

                <label for="username">Identifiant</label>
                <input type="text" id="username" name="username" required autofocus />

                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required />

                <button type="submit">Se connecter</button>
            </form>
        </main>
    </body>
</html>
