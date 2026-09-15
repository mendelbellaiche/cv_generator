<?php
session_start();

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: auth.php');
    exit;
}

require __DIR__.'/../utils/Database.php';
require __DIR__.'/../utils/CvVersion.php';

$db = Database::getInstance(__DIR__ . '/../moncv.sqlite');

$currentVersionId = cvVersionResolveCurrent($db);
if ($currentVersionId === null) {
    header('Location: /admin/index.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin - DASHBOARD</title>

    <link rel="stylesheet" type="text/css" href="/admin/assets/css/styles.css" />
    <link rel="stylesheet" type="text/css" href="/admin/assets/css/bootstrap.css" />

    <style>
        .preview-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .preview-frame-wrapper {
            border: 1px solid #cccccc;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .preview-frame-wrapper iframe {
            width: 100%;
            height: 80vh;
            border: none;
            display: block;
        }
    </style>
</head>
<body>
<?php require_once("includes/nav.php"); ?>
<?php require_once("includes/aside.php"); ?>

<main>

    <div class="preview-toolbar">
        <h2>Preview du CV</h2>
        <a href="/cv.php?version=<?= $currentVersionId ?>" target="_blank" class="btn btn-primary">Télécharger le PDF</a>
    </div>

    <div class="preview-frame-wrapper">
        <iframe src="/admin/preview-render.php?v=<?= $currentVersionId ?>"></iframe>
    </div>

</main>

<script src="/admin/assets/js/scripts.js?time=<?= time(); ?>"></script>
</body>
</html>
