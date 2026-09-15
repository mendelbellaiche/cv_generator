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
    $school = $_POST['school'] ?? '';
    $degree = $_POST['degree'] ?? '';
    $city = $_POST['city'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $description = $_POST['description'] ?? '';

    $db->query("INSERT INTO formations (school, degree, city, start_date, end_date, description, cv_version_id) VALUES (:school, :degree, :city, :start_date, :end_date, :description, :v)", [
        'school'      => $school,
        'degree'      => $degree,
        'city'        => $city,
        'start_date'  => $startDate,
        'end_date'    => $endDate,
        'description' => $description,
        'v'           => $currentVersionId
    ]);
}

if (isset($_GET['delete'])) {
    $db->query("DELETE FROM formations WHERE id = :id AND cv_version_id = :v", ['id' => $_GET['delete'], 'v' => $currentVersionId]);
    header('Location: ' . cvVersionLink('/admin/formations.php', $currentVersionId));
    exit;
}

$formations = $db->query("SELECT * FROM formations WHERE cv_version_id = :v ORDER BY start_date DESC", ['v' => $currentVersionId])->fetchAll();

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" />

    <style>
        #descriptionEditor {
            background: white;
        }

        #descriptionEditor .ql-editor {
            min-height: 220px;
        }

        .description-preview ul,
        .description-preview ol {
            padding-left: 1.5em;
        }

        .description-preview h3 {
            font-size: 1.1em;
            margin-top: 0.3em;
        }
    </style>
</head>
<body>
<?php require_once("includes/nav.php"); ?>
<?php require_once("includes/aside.php"); ?>

<main>

    <h2>Formations</h2>
    <br />

    <form action="<?= cvVersionLink('/admin/formations.php', $currentVersionId) ?>" method="post">
        <div class="row">
            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label for="school">École / Établissement</label>
                    <input type="text" name="school" id="school" class="form-control" required />
                </div>

                <div class="mb-3">
                    <label for="degree">Diplôme</label>
                    <input type="text" name="degree" id="degree" class="form-control" />
                </div>

                <div class="mb-3">
                    <label for="city">Ville</label>
                    <input type="text" name="city" id="city" class="form-control" />
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label for="start_date">Date de début</label>
                    <input type="text" name="start_date" id="start_date" class="form-control" placeholder="ex: 2020" />
                </div>

                <div class="mb-3">
                    <label for="end_date">Date de fin</label>
                    <input type="text" name="end_date" id="end_date" class="form-control" placeholder="ex: 2022" />
                </div>

                <div class="mb-3">
                    <label for="description">Description</label>
                    <div id="descriptionEditor"></div>
                    <textarea name="description" id="description" style="display: none;"></textarea>
                </div>
            </div>
        </div>

        <div>
            <input type="submit" value="Ajouter" class="btn btn-primary" />
        </div>
    </form>

    <hr />

    <div class="mt-4">
        <?php if (empty($formations)): ?>
            <p>Aucune formation enregistrée.</p>
        <?php else: ?>
            <?php foreach ($formations as $formation): ?>
                <div class="row align-items-center mb-3">
                    <div class="col-10">
                        <strong><?= htmlspecialchars($formation['degree']) ?></strong>
                        — <?= htmlspecialchars($formation['school']) ?>
                        <?php if ($formation['city']): ?>
                            (<?= htmlspecialchars($formation['city']) ?>)
                        <?php endif; ?>
                        <br />
                        <small><?= htmlspecialchars($formation['start_date']) ?> - <?= htmlspecialchars($formation['end_date']) ?></small>
                        <?php if ($formation['description']): ?>
                            <div class="description-preview"><?= $formation['description'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-2 text-end">
                        <a href="<?= cvVersionLink('/admin/formations.php?delete=' . $formation['id'], $currentVersionId) ?>" class="btn btn-danger" onclick="return confirm('Supprimer cette formation ?');">Supprimer</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
    const descriptionQuill = new Quill('#descriptionEditor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ header: [3, false] }],
                ['bold', 'italic', 'underline'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['clean']
            ]
        }
    });

    function normalizeQuillLists(html) {
        const container = document.createElement('div');
        container.innerHTML = html;

        container.querySelectorAll('ol').forEach((ol) => {
            const items = Array.from(ol.children);
            const isBullet = items.length > 0 && items.every((li) => li.getAttribute('data-list') === 'bullet');
            const list = document.createElement(isBullet ? 'ul' : 'ol');

            items.forEach((li) => {
                li.removeAttribute('data-list');
                li.querySelector('.ql-ui')?.remove();
                list.appendChild(li);
            });

            ol.replaceWith(list);
        });

        return container.innerHTML;
    }

    document.querySelector('form').addEventListener('submit', () => {
        document.querySelector('#description').value = normalizeQuillLists(descriptionQuill.root.innerHTML);
    });
</script>
<script src="/admin/assets/js/scripts.js?time=<?= time(); ?>"></script>
</body>
</html>
