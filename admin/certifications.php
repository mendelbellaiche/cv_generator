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
    $title = $_POST['title'] ?? '';
    $examVersion = $_POST['exam_version'] ?? '';
    $year = $_POST['year'] ?? '';

    if ($id !== '') {
        $db->query("UPDATE certifications SET title = :title, exam_version = :exam_version, year = :year WHERE id = :id AND cv_version_id = :v", [
            'title'        => $title,
            'exam_version' => $examVersion,
            'year'         => $year,
            'id'           => $id,
            'v'            => $currentVersionId
        ]);
    } else {
        $maxOrder = $db->query("SELECT MAX(sort_order) AS max_order FROM certifications WHERE cv_version_id = :v", ['v' => $currentVersionId])->fetch();
        $nextOrder = ($maxOrder['max_order'] ?? 0) + 1;

        $db->query("INSERT INTO certifications (title, exam_version, year, sort_order, cv_version_id) VALUES (:title, :exam_version, :year, :sort_order, :v)", [
            'title'        => $title,
            'exam_version' => $examVersion,
            'year'         => $year,
            'sort_order'   => $nextOrder,
            'v'            => $currentVersionId
        ]);
    }

    header('Location: ' . cvVersionLink('/admin/certifications.php', $currentVersionId));
    exit;
}

if (isset($_GET['delete'])) {
    $db->query("DELETE FROM certifications WHERE id = :id AND cv_version_id = :v", ['id' => $_GET['delete'], 'v' => $currentVersionId]);
    header('Location: ' . cvVersionLink('/admin/certifications.php', $currentVersionId));
    exit;
}

if (isset($_GET['move'], $_GET['direction'])) {
    $current = $db->query("SELECT * FROM certifications WHERE id = :id AND cv_version_id = :v", ['id' => $_GET['move'], 'v' => $currentVersionId])->fetch();

    if ($current) {
        $neighbor = $_GET['direction'] === 'up'
            ? $db->query("SELECT * FROM certifications WHERE cv_version_id = :v AND sort_order < :order ORDER BY sort_order DESC LIMIT 1", ['v' => $currentVersionId, 'order' => $current['sort_order']])->fetch()
            : $db->query("SELECT * FROM certifications WHERE cv_version_id = :v AND sort_order > :order ORDER BY sort_order ASC LIMIT 1", ['v' => $currentVersionId, 'order' => $current['sort_order']])->fetch();

        if ($neighbor) {
            $db->query("UPDATE certifications SET sort_order = :order WHERE id = :id", ['order' => $neighbor['sort_order'], 'id' => $current['id']]);
            $db->query("UPDATE certifications SET sort_order = :order WHERE id = :id", ['order' => $current['sort_order'], 'id' => $neighbor['id']]);
        }
    }

    header('Location: ' . cvVersionLink('/admin/certifications.php', $currentVersionId));
    exit;
}

$certifications = $db->query("SELECT * FROM certifications WHERE cv_version_id = :v ORDER BY sort_order ASC", ['v' => $currentVersionId])->fetchAll();

$editingCertification = null;

if (isset($_GET['edit'])) {
    $editingCertification = $db->query("SELECT * FROM certifications WHERE id = :id AND cv_version_id = :v", ['id' => $_GET['edit'], 'v' => $currentVersionId])->fetch();
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
        .certification-card {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-left: 4px solid #222222;
            border-radius: 6px;
            padding: 16px 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
            transition: box-shadow 0.2s;
        }

        .certification-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .certification-card .certification-title {
            font-size: 1.1em;
            color: #222222;
        }

        .certification-card .certification-exam {
            color: #4F4F4F;
        }

        .certification-card .certification-year {
            display: inline-block;
            background: #EFEFEF;
            color: #4F4F4F;
            border-radius: 4px;
            padding: 2px 10px;
            font-size: 0.85em;
            margin-top: 6px;
        }

        .certification-card .certification-actions {
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

    <h2><?= $editingCertification ? 'Modifier une certification' : 'Certifications' ?></h2>
    <br />

    <form action="<?= cvVersionLink('/admin/certifications.php', $currentVersionId) ?>" method="post">
        <?php if ($editingCertification): ?>
            <input type="hidden" name="id" value="<?= $editingCertification['id'] ?>" />
        <?php endif; ?>

        <div class="row">
            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label for="title">Titre</label>
                    <input type="text" value="<?= htmlspecialchars($editingCertification['title'] ?? '') ?>" name="title" id="title" class="form-control" placeholder="ex: PCEP™ – Certified Entry-Level Python Programmer" required />
                </div>

                <div class="mb-3">
                    <label for="exam_version">Version de l'examen</label>
                    <input type="text" value="<?= htmlspecialchars($editingCertification['exam_version'] ?? '') ?>" name="exam_version" id="exam_version" class="form-control" placeholder="ex: PCEP-30-02" />
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label for="year">Année</label>
                    <input type="number" value="<?= htmlspecialchars($editingCertification['year'] ?? '') ?>" name="year" id="year" class="form-control" placeholder="ex: 2022" min="1900" max="2100" />
                </div>
            </div>
        </div>

        <div>
            <input type="submit" value="<?= $editingCertification ? 'Enregistrer' : 'Ajouter' ?>" class="btn btn-primary" />
            <?php if ($editingCertification): ?>
                <a href="<?= cvVersionLink('/admin/certifications.php', $currentVersionId) ?>" class="btn btn-secondary">Annuler</a>
            <?php endif; ?>
        </div>
    </form>

    <hr />

    <div class="mt-4">
        <?php if (empty($certifications)): ?>
            <p>Aucune certification enregistrée.</p>
        <?php else: ?>
            <?php foreach ($certifications as $index => $certification): ?>
                <div class="certification-card">
                    <div class="row align-items-start">
                        <div class="col-12 col-md-9">
                            <div class="certification-title"><strong><?= htmlspecialchars($certification['title']) ?></strong></div>
                            <?php if ($certification['exam_version']): ?>
                                <div class="certification-exam">Exam Version: <?= htmlspecialchars($certification['exam_version']) ?></div>
                            <?php endif; ?>
                            <?php if ($certification['year']): ?>
                                <span class="certification-year"><?= htmlspecialchars($certification['year']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="col-12 col-md-3 certification-actions mt-3 mt-md-0">
                            <a href="<?= cvVersionLink('/admin/certifications.php?move=' . $certification['id'] . '&direction=up', $currentVersionId) ?>" class="btn btn-secondary <?= $index === 0 ? 'disabled' : '' ?>">&uarr;</a>
                            <a href="<?= cvVersionLink('/admin/certifications.php?move=' . $certification['id'] . '&direction=down', $currentVersionId) ?>" class="btn btn-secondary <?= $index === count($certifications) - 1 ? 'disabled' : '' ?>">&darr;</a>
                            <a href="<?= cvVersionLink('/admin/certifications.php?edit=' . $certification['id'], $currentVersionId) ?>" class="btn btn-primary">Modifier</a>
                            <a href="<?= cvVersionLink('/admin/certifications.php?delete=' . $certification['id'], $currentVersionId) ?>" class="btn btn-danger" onclick="return confirm('Supprimer cette certification ?');">Supprimer</a>
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
