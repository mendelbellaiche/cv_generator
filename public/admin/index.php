<?php
session_start();

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: auth.php');
    exit;
}

require __DIR__.'/../utils/Database.php';
require __DIR__.'/../utils/CvTemplates.php';
require __DIR__.'/../utils/CvVersion.php';

$db = Database::getInstance(__DIR__ . '/../moncv.sqlite');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $templateKey = $_POST['template_key'] ?? 'default';
    $atsFriendly = isset($_POST['ats_friendly']) ? 1 : 0;

    if (!array_key_exists($templateKey, cvTemplateOptions())) {
        $templateKey = 'default';
    }

    if ($name !== '') {
        if ($id !== '') {
            $db->query("UPDATE cv_versions SET name = :name, template_key = :template_key, ats_friendly = :ats_friendly, updated_at = datetime('now') WHERE id = :id", [
                'name'         => $name,
                'template_key' => $templateKey,
                'ats_friendly' => $atsFriendly,
                'id'           => $id
            ]);
        } else {
            $db->query("INSERT INTO cv_versions (name, template_key, ats_friendly) VALUES (:name, :template_key, :ats_friendly)", [
                'name'         => $name,
                'template_key' => $templateKey,
                'ats_friendly' => $atsFriendly
            ]);
            $newId = (int) $db->getPdo()->lastInsertId();

            $db->query("INSERT INTO information (cv_version_id) VALUES (:v)", ['v' => $newId]);
            $db->query("INSERT INTO address (cv_version_id) VALUES (:v)", ['v' => $newId]);

            header('Location: /admin/information.php?v=' . $newId);
            exit;
        }
    }

    header('Location: /admin/index.php');
    exit;
}

if (isset($_GET['primary'])) {
    $id = (int) $_GET['primary'];

    $db->getPdo()->beginTransaction();
    $db->query("UPDATE cv_versions SET is_primary = 0", []);
    $db->query("UPDATE cv_versions SET is_primary = 1 WHERE id = :id", ['id' => $id]);
    $db->getPdo()->commit();

    $_SESSION['cv_version_id'] = $id;

    header('Location: /admin/index.php');
    exit;
}

if (isset($_GET['duplicate'])) {
    $sourceId = (int) $_GET['duplicate'];
    $source = $db->query("SELECT * FROM cv_versions WHERE id = :id", ['id' => $sourceId])->fetch();

    if ($source) {
        $db->getPdo()->beginTransaction();

        $db->query("INSERT INTO cv_versions (name, template_key, ats_friendly) VALUES (:name, :template_key, :ats_friendly)", [
            'name'         => $source['name'] . ' (copie)',
            'template_key' => $source['template_key'],
            'ats_friendly' => $source['ats_friendly']
        ]);
        $newId = (int) $db->getPdo()->lastInsertId();

        foreach (['information', 'address', 'hobbies', 'formations', 'experiences', 'certifications', 'skills'] as $table) {
            $columns = array_values(array_filter(
                array_column($db->query("PRAGMA table_info($table)", [])->fetchAll(), 'name'),
                fn($column) => $column !== 'id'
            ));

            $selectColumns = array_map(
                fn($column) => $column === 'cv_version_id' ? (string) $newId : $column,
                $columns
            );

            $columnList = implode(', ', $columns);
            $selectList = implode(', ', $selectColumns);

            $db->query(
                "INSERT INTO $table ($columnList) SELECT $selectList FROM $table WHERE cv_version_id = :v",
                ['v' => $sourceId]
            );
        }

        $db->getPdo()->commit();
    }

    header('Location: /admin/index.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    $db->getPdo()->beginTransaction();
    foreach (['information', 'address', 'hobbies', 'formations', 'experiences', 'certifications', 'skills'] as $table) {
        $db->query("DELETE FROM $table WHERE cv_version_id = :v", ['v' => $id]);
    }
    $db->query("DELETE FROM cv_versions WHERE id = :id", ['id' => $id]);

    $remainingPrimary = $db->query("SELECT id FROM cv_versions WHERE is_primary = 1", [])->fetch();
    if (!$remainingPrimary) {
        $db->query("UPDATE cv_versions SET is_primary = 1 WHERE id = (SELECT MIN(id) FROM cv_versions)", []);
    }

    $db->getPdo()->commit();

    if (($_SESSION['cv_version_id'] ?? null) == $id) {
        unset($_SESSION['cv_version_id']);
    }

    header('Location: /admin/index.php');
    exit;
}

$versions = $db->query("SELECT * FROM cv_versions ORDER BY id DESC", [])->fetchAll();

$editingVersion = null;
if (isset($_GET['edit'])) {
    $editingVersion = $db->query("SELECT * FROM cv_versions WHERE id = :id", ['id' => $_GET['edit']])->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin - Mes CV</title>
    <link rel="stylesheet" type="text/css" href="/admin/assets/css/styles.css" />
    <link rel="stylesheet" type="text/css" href="/admin/assets/css/bootstrap.css" />

    <style>
        .version-card {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-left: 4px solid #222222;
            border-radius: 6px;
            padding: 16px 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
        }

        .version-card .version-name {
            font-size: 1.15em;
            color: #222222;
        }

        .version-card .version-meta {
            color: #8E8E8E;
            font-size: 0.9em;
        }

        .version-card .version-actions {
            display: flex;
            gap: 6px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
<?php
    // aside.php a besoin de $currentVersionId pour construire ses liens ; le
    // tableau de bord n'a pas de version "active" propre. On privilégie la
    // version marquée "CV principal" (c'est elle que l'utilisateur a choisi
    // de mettre en avant), sinon on retombe sur la résolution standard.
    $primaryVersion = $db->query("SELECT id FROM cv_versions WHERE is_primary = 1", [])->fetch();
    $currentVersionId = $primaryVersion ? (int) $primaryVersion['id'] : (cvVersionResolveCurrent($db) ?? 0);
?>
<?php require_once("includes/nav.php"); ?>
<?php require_once("includes/aside.php"); ?>

<main>

    <h2><?= $editingVersion ? 'Modifier la version' : 'Mes CV' ?></h2>
    <br />

    <form action="/admin/index.php" method="post">
        <?php if ($editingVersion): ?>
            <input type="hidden" name="id" value="<?= $editingVersion['id'] ?>" />
        <?php endif; ?>

        <div class="row">
            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label for="name">Nom de la version</label>
                    <input type="text" value="<?= htmlspecialchars($editingVersion['name'] ?? '') ?>" name="name" id="name" class="form-control" placeholder="ex: CV - Développeur Python" required />
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label for="template_key">Design</label>
                    <select name="template_key" id="template_key" class="form-control">
                        <?php foreach (cvTemplateOptions() as $key => $template): ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= ($editingVersion['template_key'] ?? 'default') === $key ? 'selected' : '' ?>><?= htmlspecialchars($template['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="ats_friendly" id="ats_friendly" class="form-check-input" value="1" <?= !empty($editingVersion['ats_friendly']) ? 'checked' : '' ?> />
            <label for="ats_friendly" class="form-check-label">CV ATS-friendly (mise en page simplifiée, sans icônes ni tableaux, pour les logiciels de tri de candidatures)</label>
        </div>

        <div>
            <input type="submit" value="<?= $editingVersion ? 'Enregistrer' : 'Créer cette version' ?>" class="btn btn-primary" />
            <?php if ($editingVersion): ?>
                <a href="/admin/index.php" class="btn btn-secondary">Annuler</a>
            <?php endif; ?>
        </div>
    </form>

    <hr />

    <div class="mt-4">
        <?php if (empty($versions)): ?>
            <p>Aucune version de CV pour l'instant. Créez-en une ci-dessus.</p>
        <?php else: ?>
            <?php foreach ($versions as $version): ?>
                <div class="version-card">
                    <div class="row align-items-start">
                        <div class="col-12 col-md-8">
                            <div class="version-name">
                                <strong><?= htmlspecialchars($version['name']) ?></strong>
                                <?php if ($version['is_primary']): ?>
                                    <span class="badge bg-primary ms-2">CV principal</span>
                                <?php endif; ?>
                                <?php if ($version['ats_friendly']): ?>
                                    <span class="badge bg-secondary ms-2">ATS-friendly</span>
                                <?php endif; ?>
                            </div>
                            <div class="version-meta">
                                Design : <?= htmlspecialchars(cvTemplateOptions()[$version['template_key']]['label'] ?? $version['template_key']) ?>
                                — créée le <?= htmlspecialchars($version['created_at']) ?>
                                — mise à jour le <?= htmlspecialchars($version['updated_at']) ?>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 version-actions mt-3 mt-md-0">
                            <a href="/admin/information.php?v=<?= $version['id'] ?>" class="btn btn-primary">Modifier le contenu</a>
                            <a href="/admin/preview.php?v=<?= $version['id'] ?>" class="btn btn-secondary">Aperçu</a>
                            <a href="/admin/index.php?edit=<?= $version['id'] ?>" class="btn btn-secondary">Renommer</a>
                            <a href="/admin/index.php?duplicate=<?= $version['id'] ?>" class="btn btn-secondary">Dupliquer</a>
                            <?php if (!$version['is_primary']): ?>
                                <a href="/admin/index.php?primary=<?= $version['id'] ?>" class="btn btn-secondary">Définir comme principal</a>
                            <?php endif; ?>
                            <a href="/admin/index.php?delete=<?= $version['id'] ?>" class="btn btn-danger" onclick="return confirm('Supprimer cette version et tout son contenu ?');">Supprimer</a>
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
