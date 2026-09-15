<?php
require __DIR__.'/utils/Database.php';

$db = Database::getInstance(__DIR__ . '/moncv.sqlite');
$primaryVersion = $db->query("SELECT id FROM cv_versions WHERE is_primary = 1 LIMIT 1", [])->fetch()
    ?: $db->query("SELECT id FROM cv_versions ORDER BY id ASC LIMIT 1", [])->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Mon CV</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #222222 0%, #3D3D3D 100%);
            font-family: Helvetica, Arial, sans-serif;
        }

        .home-links {
            display: flex;
            gap: 20px;
        }

        .home-links a {
            background: #ffffff;
            color: #222222;
            text-decoration: none;
            padding: 16px 28px;
            border-radius: 6px;
            font-size: 1.1em;
            box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
            transition: transform 0.2s;
        }

        .home-links a:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="home-links">
        <?php if ($primaryVersion): ?>
            <a href="/cv.php?version=<?= $primaryVersion['id'] ?>" target="_blank" rel="noopener">Voir mon CV</a>
        <?php endif; ?>
        <a href="/admin/index.php">Espace admin</a>
    </div>
</body>
</html>
