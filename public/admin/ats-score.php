<?php
session_start();

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: auth.php');
    exit;
}

require __DIR__.'/../utils/Database.php';
require __DIR__.'/../utils/CvVersion.php';
require __DIR__.'/../utils/CvData.php';
require __DIR__.'/../utils/CvTemplates.php';

$db = Database::getInstance(__DIR__ . '/../moncv.sqlite');

$currentVersionId = cvVersionResolveCurrent($db);
if ($currentVersionId === null) {
    header('Location: /admin/index.php');
    exit;
}

$cv = loadCvData($db, $currentVersionId);
$information = $cv['information'] ?? [];
$address = $cv['address'] ?? [];

$version = $db->query("SELECT * FROM cv_versions WHERE id = :id", ['id' => $currentVersionId])->fetch();

function atsWordCount(string $html): int
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)));
    return $text === '' ? 0 : count(explode(' ', $text));
}

/**
 * Rend réellement le template HTML pour cette version (avec le réglage
 * "ATS-friendly" tel qu'il est enregistré) afin que les critères de mise en
 * page portent sur le rendu effectif plutôt que sur le code source brut.
 */
function atsRenderedHtml(Database $db, int $versionId, array $version): string
{
    extract(loadCvData($db, $versionId));

    $iconsPath = __DIR__ . '/../images/';
    $profileImagePath = __DIR__ . '/../images/custom/' . ($information['image_path'] ?? '');
    $renderTarget = 'html';
    $atsFriendly = (bool) ($version['ats_friendly'] ?? false);

    ob_start();
    require cvTemplateResolveFile($version['template_key'] ?? 'default');
    return ob_get_clean();
}

$renderedHtml = atsRenderedHtml($db, $currentVersionId, $version ?: []);

/**
 * Checklist de compatibilité ATS.
 * Chaque règle a un poids ; le score final est la somme des poids validés
 * rapportée au total. Les règles "template" concernent la mise en page
 * commune à toutes les versions (mise en page 2 colonnes + icônes images),
 * les autres dépendent du contenu renseigné pour la version de CV en cours.
 */
$checks = [];

$checks[] = [
    'label'  => 'Prénom et nom renseignés',
    'pass'   => !empty($information['firstname']) && !empty($information['lastname']),
    'weight' => 10,
    'tip'    => 'Renseignez le prénom et le nom dans "Informations" : sans identité claire, un ATS peut rejeter le CV.',
];

$checks[] = [
    'label'  => 'Adresse email renseignée',
    'pass'   => !empty($information['email']),
    'weight' => 10,
    'tip'    => 'Ajoutez une adresse email : c\'est le champ que les ATS extraient le plus souvent pour créer le profil candidat.',
];

$checks[] = [
    'label'  => 'Numéro de téléphone renseigné',
    'pass'   => !empty($information['phone']),
    'weight' => 8,
    'tip'    => 'Ajoutez un numéro de téléphone pour permettre l\'extraction automatique des coordonnées.',
];

$checks[] = [
    'label'  => 'Intitulé de poste renseigné',
    'pass'   => !empty($information['job_title']),
    'weight' => 8,
    'tip'    => 'Renseignez un intitulé de poste : il aide l\'ATS à faire correspondre le CV à l\'offre (matching de mots-clés).',
];

$checks[] = [
    'label'  => 'Au moins une expérience professionnelle',
    'pass'   => !empty($cv['experiences']),
    'weight' => 12,
    'tip'    => 'Ajoutez au moins une expérience professionnelle : la plupart des ATS notent très bas les CV sans historique.',
];

$experienceDescriptions = array_filter($cv['experiences'], fn($e) => trim(strip_tags($e['description'] ?? '')) !== '');
$checks[] = [
    'label'  => 'Descriptions détaillées pour les expériences',
    'pass'   => !empty($cv['experiences']) && count($experienceDescriptions) === count($cv['experiences']),
    'weight' => 10,
    'tip'    => 'Décrivez chaque expérience avec des phrases riches en mots-clés métier : l\'ATS s\'appuie sur ce texte pour le scoring.',
];

$checks[] = [
    'label'  => 'Compétences techniques renseignées',
    'pass'   => !empty($cv['skills']) && count(array_filter($cv['skills'], fn($s) => trim($s['items'] ?? '') !== '')) > 0,
    'weight' => 12,
    'tip'    => 'Listez vos compétences sous forme de texte simple (mots séparés par des virgules) dans "Compétences".',
];

$checks[] = [
    'label'  => 'Au moins une formation renseignée',
    'pass'   => !empty($cv['formations']),
    'weight' => 6,
    'tip'    => 'Ajoutez votre parcours de formation : de nombreux ATS filtrent selon le niveau de diplôme.',
];

$totalWords = atsWordCount($information['job_title'] ?? '')
    + array_sum(array_map(fn($e) => atsWordCount($e['description'] ?? ''), $cv['experiences']))
    + array_sum(array_map(fn($f) => atsWordCount($f['description'] ?? ''), $cv['formations']));
$checks[] = [
    'label'  => 'Contenu suffisamment détaillé (mots-clés)',
    'pass'   => $totalWords >= 80,
    'weight' => 8,
    'tip'    => 'Le contenu texte est très court (' . $totalWords . ' mots). Détaillez vos missions pour augmenter les correspondances de mots-clés.',
];

// Règles liées à la mise en page réelle du rendu (dépendent du réglage "ATS-friendly" de la version).
$checks[] = [
    'label'  => 'Mise en page en une seule colonne',
    'pass'   => str_contains($renderedHtml, 'ats-mode'),
    'weight' => 12,
    'tip'    => 'Ce CV utilise une mise en page en 2 colonnes (encart latéral + contenu). De nombreux ATS lisent le PDF colonne par colonne et peuvent mélanger les informations. Activez le mode "CV ATS-friendly" dans "Mes CV".',
];

$remainingImgTags = preg_match_all('/<img\b/i', $renderedHtml);
$checks[] = [
    'label'  => 'Coordonnées en texte pur (sans icônes image)',
    'pass'   => $remainingImgTags === 0,
    'weight' => 6,
    'tip'    => 'Ce CV affiche encore ' . $remainingImgTags . ' image(s) (photo, pictogrammes...). Certains ATS n\'extraient pas le texte situé près d\'images. Activez le mode "CV ATS-friendly" dans "Mes CV".',
];

$remainingTableTags = preg_match_all('/<table\b/i', $renderedHtml);
$checks[] = [
    'label'  => 'Structure sans tableaux de mise en page',
    'pass'   => $remainingTableTags === 0,
    'weight' => 8,
    'tip'    => 'Ce CV utilise ' . $remainingTableTags . ' balise(s) <table> pour aligner le contenu. Les tableaux sont une cause fréquente de contenu mal ordonné par les ATS. Activez le mode "CV ATS-friendly" dans "Mes CV".',
];

$totalWeight = array_sum(array_column($checks, 'weight'));
$score = array_sum(array_map(fn($c) => $c['pass'] ? $c['weight'] : 0, $checks));
$percentage = $totalWeight > 0 ? (int) round($score / $totalWeight * 100) : 0;

if ($percentage >= 80) {
    $scoreColor = '#2e7d32';
    $scoreLabel = 'Bonne compatibilité ATS';
} elseif ($percentage >= 50) {
    $scoreColor = '#e6a700';
    $scoreLabel = 'Compatibilité ATS moyenne';
} else {
    $scoreColor = '#c62828';
    $scoreLabel = 'Faible compatibilité ATS';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin - Score ATS</title>
    <link rel="stylesheet" type="text/css" href="/admin/assets/css/styles.css" />
    <link rel="stylesheet" type="text/css" href="/admin/assets/css/bootstrap.css" />

    <style>
        .ats-score-card {
            display: flex;
            align-items: center;
            gap: 30px;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
        }

        .ats-score-gauge {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
            font-weight: bold;
            color: #ffffff;
            flex-shrink: 0;
        }

        .ats-check-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .ats-check-item {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-left: 4px solid #cccccc;
            border-radius: 6px;
            padding: 14px 18px;
            margin-bottom: 12px;
        }

        .ats-check-item.pass {
            border-left-color: #2e7d32;
        }

        .ats-check-item.fail {
            border-left-color: #c62828;
        }

        .ats-check-title {
            font-weight: bold;
        }

        .ats-check-title .ats-badge {
            display: inline-block;
            margin-right: 8px;
        }

        .ats-check-tip {
            color: #6a6a6a;
            font-size: 0.9em;
            margin-top: 6px;
        }
    </style>
</head>
<body>
<?php require_once("includes/nav.php"); ?>
<?php require_once("includes/aside.php"); ?>

<main>
    <h2>Score de compatibilité ATS</h2>
    <p class="text-muted">Évalue les chances que ce CV soit correctement lu par un logiciel de tri automatique de candidatures (ATS).</p>
    <br />

    <div class="ats-score-card">
        <div class="ats-score-gauge" style="background: <?= $scoreColor ?>;"><?= $percentage ?>%</div>
        <div>
            <div style="font-size: 1.2em; font-weight: bold; color: <?= $scoreColor ?>;"><?= htmlspecialchars($scoreLabel) ?></div>
            <div class="text-muted"><?= array_sum(array_map(fn($c) => $c['pass'] ? 1 : 0, $checks)) ?> critère(s) validé(s) sur <?= count($checks) ?>.</div>
        </div>
    </div>

    <h3 class="mb-3">Détail des critères</h3>
    <ul class="ats-check-list">

        <?php foreach ($checks as $check): ?>
            <li class="ats-check-item <?= $check['pass'] ? 'pass' : 'fail' ?>">
                <div class="ats-check-title">
                    <span class="ats-badge"><?= $check['pass'] ? '✅' : '❌' ?></span>
                    <?= htmlspecialchars($check['label']) ?>
                </div>
                <?php if (!$check['pass']): ?>
                    <div class="ats-check-tip"><?= htmlspecialchars($check['tip']) ?></div>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</main>

<script src="assets/js/scripts.js?time=<?= time(); ?>"></script>
</body>
</html>
