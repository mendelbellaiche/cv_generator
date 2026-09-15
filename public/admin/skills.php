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
    $id = $_POST['id'] ?? '';
    $category = $_POST['category'] ?? '';

    $items = array_filter(array_map('trim', explode(',', $_POST['items'] ?? '')));
    $items = implode(', ', $items);

    if ($id !== '') {
        $db->query("UPDATE skills SET category = :category, items = :items WHERE id = :id AND cv_version_id = :v", [
            'category' => $category,
            'items'    => $items,
            'id'       => $id,
            'v'        => $currentVersionId
        ]);
    } else {
        $maxOrder = $db->query("SELECT MAX(sort_order) AS max_order FROM skills WHERE cv_version_id = :v", ['v' => $currentVersionId])->fetch();
        $nextOrder = ($maxOrder['max_order'] ?? 0) + 1;

        $db->query("INSERT INTO skills (category, items, sort_order, cv_version_id) VALUES (:category, :items, :sort_order, :v)", [
            'category'   => $category,
            'items'      => $items,
            'sort_order' => $nextOrder,
            'v'          => $currentVersionId
        ]);
    }

    header('Location: ' . cvVersionLink('/admin/skills.php', $currentVersionId));
    exit;
}

if (isset($_GET['delete'])) {
    $db->query("DELETE FROM skills WHERE id = :id AND cv_version_id = :v", ['id' => $_GET['delete'], 'v' => $currentVersionId]);
    header('Location: ' . cvVersionLink('/admin/skills.php', $currentVersionId));
    exit;
}

if (isset($_GET['move'], $_GET['direction'])) {
    $current = $db->query("SELECT * FROM skills WHERE id = :id AND cv_version_id = :v", ['id' => $_GET['move'], 'v' => $currentVersionId])->fetch();

    if ($current) {
        $neighbor = $_GET['direction'] === 'up'
            ? $db->query("SELECT * FROM skills WHERE cv_version_id = :v AND sort_order < :order ORDER BY sort_order DESC LIMIT 1", ['v' => $currentVersionId, 'order' => $current['sort_order']])->fetch()
            : $db->query("SELECT * FROM skills WHERE cv_version_id = :v AND sort_order > :order ORDER BY sort_order ASC LIMIT 1", ['v' => $currentVersionId, 'order' => $current['sort_order']])->fetch();

        if ($neighbor) {
            $db->query("UPDATE skills SET sort_order = :order WHERE id = :id", ['order' => $neighbor['sort_order'], 'id' => $current['id']]);
            $db->query("UPDATE skills SET sort_order = :order WHERE id = :id", ['order' => $current['sort_order'], 'id' => $neighbor['id']]);
        }
    }

    header('Location: ' . cvVersionLink('/admin/skills.php', $currentVersionId));
    exit;
}

$skills = $db->query("SELECT * FROM skills WHERE cv_version_id = :v ORDER BY sort_order ASC", ['v' => $currentVersionId])->fetchAll();

$editingSkill = null;

if (isset($_GET['edit'])) {
    $editingSkill = $db->query("SELECT * FROM skills WHERE id = :id AND cv_version_id = :v", ['id' => $_GET['edit'], 'v' => $currentVersionId])->fetch();
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
        .skill-card {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-left: 4px solid #222222;
            border-radius: 6px;
            padding: 16px 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
        }

        .skill-card .skill-category {
            font-size: 1.1em;
            color: #222222;
            margin-bottom: 4px;
        }

        .skill-card .skill-items {
            color: #4F4F4F;
        }

        .skill-card .skill-actions {
            display: flex;
            gap: 6px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
<?php require_once("includes/nav.php"); ?>
<?php require_once("includes/aside.php"); ?>

<main>

    <h2><?= $editingSkill ? 'Modifier une catégorie de compétences' : 'Compétences techniques' ?></h2>
    <br />

    <form action="<?= cvVersionLink('/admin/skills.php', $currentVersionId) ?>" method="post">
        <?php if ($editingSkill): ?>
            <input type="hidden" name="id" value="<?= $editingSkill['id'] ?>" />
        <?php endif; ?>

        <div class="row">
            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label for="category">Catégorie</label>
                    <input type="text" value="<?= htmlspecialchars($editingSkill['category'] ?? '') ?>" name="category" id="category" class="form-control" placeholder="ex: Languages de programmation" required />
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label for="items">Compétences (séparées par des virgules)</label>
                    <input type="text" value="<?= htmlspecialchars($editingSkill['items'] ?? '') ?>" name="items" id="items" class="form-control" placeholder="ex: PHP, Python, Java" />
                </div>
            </div>
        </div>

        <div>
            <input type="submit" value="<?= $editingSkill ? 'Enregistrer' : 'Ajouter' ?>" class="btn btn-primary" />
            <?php if ($editingSkill): ?>
                <a href="<?= cvVersionLink('/admin/skills.php', $currentVersionId) ?>" class="btn btn-secondary">Annuler</a>
            <?php endif; ?>
        </div>
    </form>

    <hr />

    <div class="mt-4">
        <?php if (empty($skills)): ?>
            <p>Aucune compétence enregistrée.</p>
        <?php else: ?>
            <?php foreach ($skills as $index => $skill): ?>
                <div class="skill-card">
                    <div class="row align-items-start">
                        <div class="col-12 col-md-9">
                            <div class="skill-category"><strong><?= htmlspecialchars($skill['category']) ?></strong></div>
                            <div class="skill-items"><?= htmlspecialchars($skill['items']) ?></div>
                        </div>
                        <div class="col-12 col-md-3 skill-actions mt-3 mt-md-0">
                            <a href="<?= cvVersionLink('/admin/skills.php?move=' . $skill['id'] . '&direction=up', $currentVersionId) ?>" class="btn btn-secondary <?= $index === 0 ? 'disabled' : '' ?>">&uarr;</a>
                            <a href="<?= cvVersionLink('/admin/skills.php?move=' . $skill['id'] . '&direction=down', $currentVersionId) ?>" class="btn btn-secondary <?= $index === count($skills) - 1 ? 'disabled' : '' ?>">&darr;</a>
                            <a href="<?= cvVersionLink('/admin/skills.php?edit=' . $skill['id'], $currentVersionId) ?>" class="btn btn-primary">Modifier</a>
                            <a href="<?= cvVersionLink('/admin/skills.php?delete=' . $skill['id'], $currentVersionId) ?>" class="btn btn-danger" onclick="return confirm('Supprimer cette catégorie ?');">Supprimer</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</main>

<script src="/admin/assets/js/scripts.js?time=<?= time(); ?>"></script>
</body>
</html>
