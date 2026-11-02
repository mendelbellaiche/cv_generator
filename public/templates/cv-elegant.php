<?php
/**
 * Template CV "Élégant" : typographie serif, en-tête centré, filets dorés, une colonne.
 * Mêmes variables que templates/cv.php. Compatible Dompdf (blocs + floats, pas de flex/grid).
 * $atsFriendly : retire photo et couleurs, garde une structure linéaire en noir et blanc.
 */

$renderTarget ??= 'pdf';
$atsFriendly ??= false;

$accent = $atsFriendly ? '#000000' : '#B08D57';
$ink = '#1E1E1E';
$muted = '#6E6E6E';

$logoMeBase64 = $atsFriendly ? null : cvImageToBase64($profileImagePath);
$fullName = trim(($information['firstname'] ?? '') . ' ' . strtoupper($information['lastname'] ?? ''));

$addrLine = trim($address['line'] ?? '');
$addrZip = trim($address['zipcode'] ?? '');
$addrCity = trim($address['city'] ?? '');
$addressText = $addrLine === ''
    ? $addrCity . ($addrZip !== '' ? ' (' . $addrZip . ')' : '')
    : implode(', ', array_filter([$addrLine, $addrZip, $addrCity], 'strlen'));

$shortUrl = static fn(string $url): string => preg_replace('#^https?://(www\.)?#', '', rtrim($url, '/'));

$contacts = array_filter([
    $information['email'] ?? '',
    $information['phone'] ?? '',
    $addressText,
    !empty($information['birthdate']) ? cvFormatFullDate($information['birthdate']) : '',
    !empty($information['linkedin_url']) ? $shortUrl($information['linkedin_url']) : '',
    !empty($information['github_url']) ? $shortUrl($information['github_url']) : '',
], 'strlen');

$experienceSections = [
    'Expériences professionnelles'   => array_filter($experiences ?? [], fn($e) => ($e['category'] ?? 'pro') !== 'freelance'),
    'Freelance & projets personnels' => array_filter($experiences ?? [], fn($e) => ($e['category'] ?? 'pro') === 'freelance'),
];

$period = static fn(?string $start, ?string $end): string =>
    implode(' — ', array_filter([trim((string) $start), trim((string) $end)], 'strlen'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php if ($renderTarget === 'html'): ?>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php endif; ?>
    <title>CV - <?= htmlspecialchars($fullName) ?></title>
    <style>
        @page { margin: 0; }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Times New Roman', 'Georgia', serif;
            font-size: 11px;
            color: <?= $ink ?>;
            line-height: 1.45;
            padding: 34px 50px 30px 50px;
        }

        .header { text-align: center; }

        .header-photo {
            width: 78px;
            height: 78px;
            border-radius: 50%;
            border: 2px solid <?= $accent ?>;
            margin-bottom: 8px;
        }

        .header h1 {
            font-size: 28px;
            font-weight: normal;
            letter-spacing: 4px;
            text-transform: uppercase;
        }

        .header .job-title {
            font-size: 12px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: <?= $accent ?>;
            margin-top: 4px;
        }

        .ornament {
            width: 60px;
            height: 1px;
            background: <?= $accent ?>;
            margin: 10px auto;
        }

        .contacts {
            font-size: 10px;
            color: <?= $muted ?>;
        }

        .contacts .sep { color: <?= $accent ?>; margin: 0 6px; }

        .summary {
            margin: 14px auto 0 auto;
            font-style: italic;
            text-align: center;
            color: #3A3A3A;
            max-width: 90%;
        }

        .section { margin-top: 18px; }

        .section-title {
            text-align: center;
            font-size: 12px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: <?= $ink ?>;
            margin-bottom: 10px;
            padding: 3px 0;
            border-top: 1px solid <?= $accent ?>;
            border-bottom: 1px solid <?= $accent ?>;
        }

        .entry {
            margin-bottom: 10px;
            page-break-inside: avoid;
        }

        .entry-date {
            float: right;
            font-style: italic;
            color: <?= $muted ?>;
            font-size: 10px;
        }

        .entry-title {
            font-size: 12px;
            font-weight: bold;
        }

        .entry-sub {
            color: <?= $accent ?>;
            font-size: 10.5px;
            margin-bottom: 3px;
        }

        .entry-description ul,
        .entry-description ol { padding-left: 16px; margin-top: 2px; }
        .entry-description li { text-align: justify; padding-bottom: 1px; }

        .entry-stack {
            margin-top: 3px;
            font-size: 10px;
            color: #444444;
        }

        .entry-stack i { color: <?= $accent ?>; }

        .skills-table { width: 100%; border-collapse: collapse; }
        .skills-table td { padding: 2px 0; vertical-align: top; }
        .skills-table td.skill-cat {
            width: 150px;
            font-weight: bold;
            color: <?= $ink ?>;
            padding-right: 10px;
        }

        .hobbies { text-align: center; }
        .hobbies .sep { color: <?= $accent ?>; margin: 0 6px; }

        .page-break-preview {
            border-top: 1px dashed #999999;
            text-align: center;
            color: #999999;
            margin: 24px 0;
            padding-top: 6px;
            font-size: 0.85em;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

<div class="header">
    <?php if ($logoMeBase64): ?>
        <img class="header-photo" src="<?= $logoMeBase64 ?>" alt="Photo de profil"><br />
    <?php endif; ?>
    <h1><?= htmlspecialchars($fullName) ?></h1>
    <?php if (!empty($information['job_title'])): ?>
        <div class="job-title"><?= htmlspecialchars($information['job_title']) ?></div>
    <?php endif; ?>
    <div class="ornament"></div>
    <?php if ($contacts): ?>
        <div class="contacts">
            <?= implode('<span class="sep">·</span>', array_map('htmlspecialchars', $contacts)) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($information['summary'])): ?>
        <p class="summary"><?= nl2br(htmlspecialchars($information['summary'])) ?></p>
    <?php endif; ?>
</div>

<?php foreach ($experienceSections as $sectionTitle => $sectionExperiences): ?>
    <?php if (!empty($sectionExperiences)): ?>
        <div class="section">
            <div class="section-title"><?= htmlspecialchars($sectionTitle) ?></div>

            <?php foreach ($sectionExperiences as $experience): ?>
                <?php if (!empty($experience['page_break_before'])): ?>
                    <?php if ($renderTarget === 'pdf'): ?>
                        <div style="page-break-before: always;"></div>
                    <?php else: ?>
                        <div class="page-break-preview">— Nouvelle page —</div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="entry">
                    <?php $dates = $period(cvFormatMonthYear($experience['start_date']), cvFormatMonthYear($experience['end_date'])); ?>
                    <?php if ($dates !== ''): ?><span class="entry-date"><?= htmlspecialchars($dates) ?></span><?php endif; ?>
                    <div class="entry-title"><?= htmlspecialchars($experience['position']) ?></div>
                    <div class="entry-sub">
                        <?= htmlspecialchars($experience['company']) ?><?php if (!empty($experience['city'])): ?>, <?= htmlspecialchars($experience['city']) ?><?php endif; ?>
                    </div>
                    <?php if (!empty($experience['description'])): ?>
                        <div class="entry-description"><?= $experience['description'] ?></div>
                    <?php endif; ?>
                    <?php if (!empty($experience['tech_stack'])): ?>
                        <div class="entry-stack"><i>Environnement technique :</i> <?= htmlspecialchars($experience['tech_stack']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php if (!empty($skills)): ?>
    <div class="section">
        <div class="section-title">Compétences</div>
        <table class="skills-table">
            <?php foreach ($skills as $skill): ?>
                <tr>
                    <td class="skill-cat"><?= htmlspecialchars($skill['category']) ?></td>
                    <td><?= htmlspecialchars($skill['items']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>

<?php if (!empty($formations)): ?>
    <div class="section">
        <div class="section-title">Formations</div>
        <?php foreach ($formations as $formation): ?>
            <div class="entry">
                <?php $dates = $period($formation['start_date'] ?? '', $formation['end_date'] ?? ''); ?>
                <?php if ($dates !== ''): ?><span class="entry-date"><?= htmlspecialchars($dates) ?></span><?php endif; ?>
                <div class="entry-title"><?= htmlspecialchars($formation['degree']) ?></div>
                <div class="entry-sub">
                    <?= htmlspecialchars($formation['school']) ?><?php if (!empty($formation['city'])): ?>, <?= htmlspecialchars($formation['city']) ?><?php endif; ?>
                </div>
                <?php if (!empty($formation['description'])): ?>
                    <div class="entry-description"><?= $formation['description'] ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($certifications)): ?>
    <div class="section">
        <div class="section-title">Certifications</div>
        <?php foreach ($certifications as $certification): ?>
            <div class="entry">
                <?php if (!empty($certification['year'])): ?><span class="entry-date"><?= htmlspecialchars($certification['year']) ?></span><?php endif; ?>
                <div class="entry-title"><?= htmlspecialchars($certification['title']) ?></div>
                <?php if (!empty($certification['exam_version'])): ?>
                    <div class="entry-sub">Exam Version : <?= htmlspecialchars($certification['exam_version']) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($hobbies)): ?>
    <div class="section">
        <div class="section-title">Centres d'intérêt</div>
        <p class="hobbies">
            <?= implode($atsFriendly ? ', ' : '<span class="sep">·</span>', array_map('htmlspecialchars', array_column($hobbies, 'label'))) ?>
        </p>
    </div>
<?php endif; ?>

</body>
</html>
