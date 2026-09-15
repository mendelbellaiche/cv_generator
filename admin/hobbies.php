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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $label = $_POST['label'] ?? '';

    if ($label !== '') {
        $db->query("INSERT INTO hobbies (label, cv_version_id) VALUES (:label, :v)", [
            'label' => $label,
            'v'     => $currentVersionId
        ]);
    }
}

if (isset($_GET['delete'])) {
    $db->query("DELETE FROM hobbies WHERE id = :id AND cv_version_id = :v", ['id' => $_GET['delete'], 'v' => $currentVersionId]);
    header('Location: ' . cvVersionLink('/admin/hobbies.php', $currentVersionId));
    exit;
}

if (isset($_GET['toggle'])) {
    $db->query("UPDATE hobbies SET displayed = 1 - displayed WHERE id = :id AND cv_version_id = :v", ['id' => $_GET['toggle'], 'v' => $currentVersionId]);
    header('Location: ' . cvVersionLink('/admin/hobbies.php', $currentVersionId));
    exit;
}

$hobbies = $db->query("SELECT * FROM hobbies WHERE cv_version_id = :v ORDER BY label", ['v' => $currentVersionId])->fetchAll();

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

</head>
<body>
    <?php require_once("includes/nav.php"); ?>
    <?php require_once("includes/aside.php"); ?>

    <main>

        <h2>Hobbies</h2>
        <br />

        <form action="<?= cvVersionLink('/admin/hobbies.php', $currentVersionId) ?>" method="post">
            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="label">Nom du hobby</label>
                        <input type="text" name="label" id="label" class="form-control" maxlength="30" required />
                    </div>
                </div>
            </div>

            <div>
                <input type="submit" value="Ajouter" class="btn btn-primary" />
            </div>
        </form>

        <hr />

        <div class="mt-4">
            <?php if (empty($hobbies)): ?>
                <p>Aucun hobby enregistré.</p>
            <?php else: ?>
                <?php foreach ($hobbies as $hobby): ?>
                    <div class="row align-items-center mb-2">
                        <div class="col-8">
                            <?= htmlspecialchars($hobby['label']) ?>
                            <?php if (!$hobby['displayed']): ?>
                                <span class="text-muted">(masqué)</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-4 text-end">
                            <a href="<?= cvVersionLink('/admin/hobbies.php?toggle=' . $hobby['id'], $currentVersionId) ?>" class="btn btn-secondary">
                                <?= $hobby['displayed'] ? 'Masquer' : 'Afficher' ?>
                            </a>
                            <a href="<?= cvVersionLink('/admin/hobbies.php?delete=' . $hobby['id'], $currentVersionId) ?>" class="btn btn-danger" onclick="return confirm('Supprimer ce hobby ?');">Supprimer</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

    <script src="/admin/assets/js/scripts.js?time=<?= time(); ?>"></script>
</body>
</html>
