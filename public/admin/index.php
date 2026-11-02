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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_template') {
    $templateKey = $_POST['template_key'] ?? '';
    if (array_key_exists($templateKey, cvTemplateOptions())) {
        $db->query("UPDATE cv_versions SET template_key = :template_key, updated_at = datetime('now') WHERE id = :id", [
            'template_key' => $templateKey,
            'id'           => (int) ($_POST['id'] ?? 0),
        ]);
    }
    header('Location: /admin/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_ats') {
    $db->query("UPDATE cv_versions SET ats_friendly = :ats_friendly, updated_at = datetime('now') WHERE id = :id", [
        'ats_friendly' => isset($_POST['ats_friendly']) ? 1 : 0,
        'id'           => (int) ($_POST['id'] ?? 0),
    ]);
    header('Location: /admin/index.php');
    exit;
}

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
            display: flex;
            flex-wrap: wrap;
            gap: 6px 8px;
            margin-top: 8px;
            font-size: 0.82em;
        }

        .version-card .meta-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 999px;
            background: #F3F4F6;
            color: #6B7280;
            white-space: nowrap;
        }

        .version-card .meta-chip strong {
            color: #374151;
            font-weight: 600;
        }

        .version-card .meta-chip.meta-design {
            background: #EEF2FF;
            color: #4F46E5;
        }

        .version-card .meta-chip.meta-design strong {
            color: #3730A3;
        }

        .version-card .meta-design-form {
            margin: 0;
            padding-right: 4px;
            transition: background 0.15s;
        }

        .version-card .meta-design-form:hover {
            background: #E0E7FF;
        }

        .version-card .meta-ats-form {
            margin: 0;
            padding-left: 10px;
            min-height: 0;
        }

        .version-card .meta-ats-form .form-check-input {
            float: none;
            margin: 0 4px 0 0;
            cursor: pointer;
        }

        .version-card .meta-ats-form .form-check-label {
            cursor: pointer;
            font-weight: 600;
        }

        .version-card .meta-ats-form.is-on {
            background: #E7F6EC;
            color: #1E7B3C;
        }

        .version-card .meta-design-form label {
            margin: 0;
            cursor: pointer;
        }

        .version-card .meta-design-form select {
            border: none;
            background: transparent;
            color: #3730A3;
            font-weight: 600;
            font-size: 1em;
            cursor: pointer;
            padding: 0 2px;
            outline: none;
        }

        .version-card .version-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            align-items: center;
            flex-wrap: wrap;
        }

        .version-card .version-actions .btn {
            white-space: nowrap;
        }

        .version-card .version-actions .actions-separator {
            width: 1px;
            align-self: stretch;
            background: #DEE2E6;
            margin: 0 4px;
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
                        <div class="col-12 col-xl">
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
                                <?php $fmtDate = static fn($d) => $d ? date('d/m/Y à H:i', strtotime($d)) : '—'; ?>
                                <form method="post" action="/admin/index.php" class="meta-chip meta-design meta-design-form" title="Changer le design">
                                    <input type="hidden" name="action" value="set_template" />
                                    <input type="hidden" name="id" value="<?= $version['id'] ?>" />
                                    <label for="tpl-<?= $version['id'] ?>">🎨</label>
                                    <select name="template_key" id="tpl-<?= $version['id'] ?>" onchange="this.form.submit()">
                                        <?php foreach (cvTemplateOptions() as $key => $template): ?>
                                            <option value="<?= htmlspecialchars($key) ?>" <?= $version['template_key'] === $key ? 'selected' : '' ?>><?= htmlspecialchars($template['label']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                                <form method="post" action="/admin/index.php" class="meta-chip meta-ats-form form-check form-switch<?= $version['ats_friendly'] ? ' is-on' : '' ?>" title="Mise en page simplifiée pour les logiciels de tri de candidatures">
                                    <input type="hidden" name="action" value="toggle_ats" />
                                    <input type="hidden" name="id" value="<?= $version['id'] ?>" />
                                    <input type="checkbox" role="switch" name="ats_friendly" value="1" id="ats-<?= $version['id'] ?>" class="form-check-input" <?= $version['ats_friendly'] ? 'checked' : '' ?> onchange="this.form.submit()" />
                                    <label for="ats-<?= $version['id'] ?>" class="form-check-label">ATS</label>
                                </form>
                                <span class="meta-chip" title="<?= htmlspecialchars($version['created_at']) ?>">Créée le <strong><?= $fmtDate($version['created_at']) ?></strong></span>
                                <?php if ($version['updated_at'] && $version['updated_at'] !== $version['created_at']): ?>
                                    <span class="meta-chip" title="<?= htmlspecialchars($version['updated_at']) ?>">Modifiée le <strong><?= $fmtDate($version['updated_at']) ?></strong></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-12 col-xl-auto version-actions mt-3 mt-xl-0">
                            <a href="/admin/information.php?v=<?= $version['id'] ?>" class="btn btn-sm btn-primary">Modifier le contenu</a>
                            <a href="/admin/preview.php?v=<?= $version['id'] ?>" class="btn btn-sm btn-outline-primary">Aperçu</a>
                            <span class="actions-separator"></span>
                            <div class="d-flex flex-wrap gap-2" role="group">
                                <a href="/admin/index.php?edit=<?= $version['id'] ?>" class="btn btn-sm btn-outline-secondary">⚙️ Paramètres</a>
                                <a href="/admin/index.php?duplicate=<?= $version['id'] ?>" class="btn btn-sm btn-outline-secondary">Dupliquer</a>
                                <?php if (!$version['is_primary']): ?>
                                    <a href="/admin/index.php?primary=<?= $version['id'] ?>" class="btn btn-sm btn-outline-secondary">Définir principal</a>
                                <?php endif; ?>
                            </div>
                            <span class="actions-separator"></span>
                            <a href="/admin/index.php?delete=<?= $version['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cette version et tout son contenu ?');">Supprimer</a>
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
