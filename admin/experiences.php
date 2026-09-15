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
    $company = $_POST['company'] ?? '';
    $position = $_POST['position'] ?? '';
    $city = $_POST['city'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $description = $_POST['description'] ?? '';

    $techStack = array_filter(array_map('trim', explode(',', $_POST['tech_stack'] ?? '')));
    $techStack = implode(', ', $techStack);

    $pageBreakBefore = isset($_POST['page_break_before']) ? 1 : 0;

    if ($id !== '') {
        $db->query("UPDATE experiences SET company = :company, position = :position, city = :city, start_date = :start_date, end_date = :end_date, description = :description, tech_stack = :tech_stack, page_break_before = :page_break_before WHERE id = :id AND cv_version_id = :v", [
            'company'            => $company,
            'position'           => $position,
            'city'               => $city,
            'start_date'         => $startDate,
            'end_date'           => $endDate,
            'description'        => $description,
            'tech_stack'         => $techStack,
            'page_break_before'  => $pageBreakBefore,
            'id'                 => $id,
            'v'                  => $currentVersionId
        ]);
    } else {
        $maxOrder = $db->query("SELECT MAX(sort_order) AS max_order FROM experiences WHERE cv_version_id = :v", ['v' => $currentVersionId])->fetch();
        $nextOrder = ($maxOrder['max_order'] ?? 0) + 1;

        $db->query("INSERT INTO experiences (company, position, city, start_date, end_date, description, tech_stack, page_break_before, sort_order, cv_version_id) VALUES (:company, :position, :city, :start_date, :end_date, :description, :tech_stack, :page_break_before, :sort_order, :v)", [
            'company'            => $company,
            'position'           => $position,
            'city'               => $city,
            'start_date'         => $startDate,
            'end_date'           => $endDate,
            'description'        => $description,
            'tech_stack'         => $techStack,
            'page_break_before'  => $pageBreakBefore,
            'sort_order'         => $nextOrder,
            'v'                  => $currentVersionId
        ]);
    }

    header('Location: ' . cvVersionLink('/admin/experiences.php', $currentVersionId));
    exit;
}

if (isset($_GET['delete'])) {
    $db->query("DELETE FROM experiences WHERE id = :id AND cv_version_id = :v", ['id' => $_GET['delete'], 'v' => $currentVersionId]);
    header('Location: ' . cvVersionLink('/admin/experiences.php', $currentVersionId));
    exit;
}

if (isset($_GET['toggle_page_break'])) {
    $db->query("UPDATE experiences SET page_break_before = 1 - page_break_before WHERE id = :id AND cv_version_id = :v", ['id' => $_GET['toggle_page_break'], 'v' => $currentVersionId]);
    header('Location: ' . cvVersionLink('/admin/experiences.php', $currentVersionId));
    exit;
}

if (isset($_GET['move'], $_GET['direction'])) {
    $current = $db->query("SELECT * FROM experiences WHERE id = :id AND cv_version_id = :v", ['id' => $_GET['move'], 'v' => $currentVersionId])->fetch();

    if ($current) {
        $neighbor = $_GET['direction'] === 'up'
            ? $db->query("SELECT * FROM experiences WHERE cv_version_id = :v AND sort_order < :order ORDER BY sort_order DESC LIMIT 1", ['v' => $currentVersionId, 'order' => $current['sort_order']])->fetch()
            : $db->query("SELECT * FROM experiences WHERE cv_version_id = :v AND sort_order > :order ORDER BY sort_order ASC LIMIT 1", ['v' => $currentVersionId, 'order' => $current['sort_order']])->fetch();

        if ($neighbor) {
            $db->query("UPDATE experiences SET sort_order = :order WHERE id = :id", ['order' => $neighbor['sort_order'], 'id' => $current['id']]);
            $db->query("UPDATE experiences SET sort_order = :order WHERE id = :id", ['order' => $current['sort_order'], 'id' => $neighbor['id']]);
        }
    }

    header('Location: ' . cvVersionLink('/admin/experiences.php', $currentVersionId));
    exit;
}

$experiences = $db->query("SELECT * FROM experiences WHERE cv_version_id = :v ORDER BY sort_order ASC", ['v' => $currentVersionId])->fetchAll();

$editingExperience = null;

if (isset($_GET['edit'])) {
    $editingExperience = $db->query("SELECT * FROM experiences WHERE id = :id AND cv_version_id = :v", ['id' => $_GET['edit'], 'v' => $currentVersionId])->fetch();
}

function formatMonthYear(?string $value): string
{
    if (!$value) {
        return '';
    }

    $date = DateTime::createFromFormat('Y-m', $value);

    if (!$date) {
        return $value;
    }

    $months = [1 => 'Jan', 'Fév', 'Mars', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Déc'];

    return $months[(int) $date->format('n')] . ' ' . $date->format('Y');
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

        .tech-stack-preview {
            margin-top: 0.5em;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .experience-card {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-left: 4px solid #222222;
            border-radius: 6px;
            padding: 18px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
            transition: box-shadow 0.2s;
        }

        .experience-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .experience-card .experience-position {
            font-size: 1.15em;
            color: #222222;
        }

        .experience-card .experience-company {
            color: #4F4F4F;
        }

        .experience-card .experience-dates {
            display: inline-block;
            background: #EFEFEF;
            color: #4F4F4F;
            border-radius: 4px;
            padding: 2px 10px;
            font-size: 0.85em;
            margin-top: 6px;
        }

        .experience-card .experience-actions {
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

    <h2><?= $editingExperience ? 'Modifier une expérience' : 'Expériences' ?></h2>
    <br />

    <form action="<?= cvVersionLink('/admin/experiences.php', $currentVersionId) ?>" method="post">
        <?php if ($editingExperience): ?>
            <input type="hidden" name="id" value="<?= $editingExperience['id'] ?>" />
        <?php endif; ?>

        <div class="row">
            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label for="position">Poste</label>
                    <input type="text" value="<?= htmlspecialchars($editingExperience['position'] ?? '') ?>" name="position" id="position" class="form-control" required />
                </div>

                <div class="mb-3">
                    <label for="company">Entreprise</label>
                    <input type="text" value="<?= htmlspecialchars($editingExperience['company'] ?? '') ?>" name="company" id="company" class="form-control" />
                </div>

                <div class="mb-3">
                    <label for="city">Ville</label>
                    <input type="text" value="<?= htmlspecialchars($editingExperience['city'] ?? '') ?>" name="city" id="city" class="form-control" />
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label for="start_date">Date de début</label>
                    <input type="month" value="<?= htmlspecialchars($editingExperience['start_date'] ?? '') ?>" name="start_date" id="start_date" class="form-control" />
                </div>

                <div class="mb-3">
                    <label for="end_date">Date de fin</label>
                    <input type="month" value="<?= htmlspecialchars($editingExperience['end_date'] ?? '') ?>" name="end_date" id="end_date" class="form-control" />
                </div>

                <div class="mb-3">
                    <label for="description">Description</label>
                    <div id="descriptionEditor"><?= $editingExperience['description'] ?? '' ?></div>
                    <textarea name="description" id="description" style="display: none;"></textarea>
                </div>

                <div class="mb-3">
                    <label for="tech_stack">Environnement technique</label>
                    <input type="text" value="<?= htmlspecialchars($editingExperience['tech_stack'] ?? '') ?>" name="tech_stack" id="tech_stack" class="form-control" placeholder="ex: PHP, MySQL, Docker" />
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" name="page_break_before" id="page_break_before" class="form-check-input" <?= !empty($editingExperience['page_break_before']) ? 'checked' : '' ?> />
                    <label for="page_break_before" class="form-check-label">Forcer un saut de page avant cette expérience</label>
                </div>
            </div>
        </div>

        <div>
            <input type="submit" value="<?= $editingExperience ? 'Enregistrer' : 'Ajouter' ?>" class="btn btn-primary" />
            <?php if ($editingExperience): ?>
                <a href="<?= cvVersionLink('/admin/experiences.php', $currentVersionId) ?>" class="btn btn-secondary">Annuler</a>
            <?php endif; ?>
        </div>
    </form>

    <hr />

    <div class="mt-4">
        <?php if (empty($experiences)): ?>
            <p>Aucune expérience enregistrée.</p>
        <?php else: ?>
            <?php foreach ($experiences as $index => $experience): ?>
                <div class="experience-card">
                    <div class="row align-items-start">
                        <div class="col-12 col-md-9">
                            <div class="experience-position"><strong><?= htmlspecialchars($experience['position']) ?></strong></div>
                            <div class="experience-company">
                                <?= htmlspecialchars($experience['company']) ?>
                                <?php if ($experience['city']): ?>
                                    — <?= htmlspecialchars($experience['city']) ?>
                                <?php endif; ?>
                            </div>
                            <span class="experience-dates"><?= htmlspecialchars(formatMonthYear($experience['start_date'])) ?> - <?= htmlspecialchars(formatMonthYear($experience['end_date'])) ?></span>
                            <?php if (!empty($experience['page_break_before'])): ?>
                                <span class="badge bg-warning text-dark">Saut de page avant</span>
                            <?php endif; ?>
                            <?php if ($experience['description']): ?>
                                <div class="description-preview"><?= $experience['description'] ?></div>
                            <?php endif; ?>
                            <?php if (!empty($experience['tech_stack'])): ?>
                                <div class="tech-stack-preview">
                                    <?php foreach (explode(', ', $experience['tech_stack']) as $tech): ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($tech) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-12 col-md-3 experience-actions mt-3 mt-md-0">
                            <a href="<?= cvVersionLink('/admin/experiences.php?move=' . $experience['id'] . '&direction=up', $currentVersionId) ?>" class="btn btn-secondary <?= $index === 0 ? 'disabled' : '' ?>">&uarr;</a>
                            <a href="<?= cvVersionLink('/admin/experiences.php?move=' . $experience['id'] . '&direction=down', $currentVersionId) ?>" class="btn btn-secondary <?= $index === count($experiences) - 1 ? 'disabled' : '' ?>">&darr;</a>
                            <a href="<?= cvVersionLink('/admin/experiences.php?toggle_page_break=' . $experience['id'], $currentVersionId) ?>" class="btn btn-secondary" title="Basculer le saut de page avant cette expérience">&#8676;|</a>
                            <a href="<?= cvVersionLink('/admin/experiences.php?edit=' . $experience['id'], $currentVersionId) ?>" class="btn btn-primary">Modifier</a>
                            <a href="<?= cvVersionLink('/admin/experiences.php?delete=' . $experience['id'], $currentVersionId) ?>" class="btn btn-danger" onclick="return confirm('Supprimer cette expérience ?');">Supprimer</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <br />

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
