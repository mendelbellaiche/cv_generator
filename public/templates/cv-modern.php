<?php
/**
 * Template CV "Moderne" : une colonne, bandeau d'en-tête coloré, sections en timeline.
 * Mêmes variables que templates/cv.php. Compatible Dompdf (pas de flex/grid : floats et blocs).
 * $atsFriendly : retire photo, icônes et couleurs de fond pour une lecture ATS optimale.
 */

$renderTarget ??= 'pdf';
$atsFriendly ??= false;

$accent = '#1F4E79';
$accentLight = '#E8EFF7';

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
    implode(' – ', array_filter([trim((string) $start), trim((string) $end)], 'strlen'));
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
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10.5px;
            color: #2B2B2B;
            line-height: 1.4;
        }

        .header {
            background: <?= $atsFriendly ? '#FFFFFF' : $accent ?>;
            color: <?= $atsFriendly ? '#000000' : '#FFFFFF' ?>;
            padding: 28px 40px 22px 40px;
        }

        .header-photo {
            float: right;
            width: 86px;
            height: 86px;
            border-radius: 50%;
            border: 3px solid #FFFFFF;
        }

        .header h1 {
            font-size: 26px;
            letter-spacing: 1px;
        }

        .header .job-title {
            font-size: 14px;
            margin-top: 2px;
            <?= $atsFriendly ? '' : 'color: #CFE0F1;' ?>
        }

        .header .summary {
            margin-top: 10px;
            font-size: 10.5px;
            max-width: <?= $logoMeBase64 ? '480px' : '100%' ?>;
        }

        .contacts {
            background: <?= $atsFriendly ? '#FFFFFF' : $accentLight ?>;
            padding: 8px 40px;
            font-size: 10px;
            color: #333333;
            <?= $atsFriendly ? 'border-bottom: 1px solid #CCCCCC;' : '' ?>
        }

        .contacts span { margin-right: 14px; }
        .contacts span.sep { margin-right: 14px; color: #9AAFC5; }

        .content { padding: 18px 40px 30px 40px; }

        .section { margin-top: 14px; }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: <?= $atsFriendly ? '#000000' : $accent ?>;
            border-bottom: 2px solid <?= $atsFriendly ? '#000000' : $accent ?>;
            padding-bottom: 3px;
            margin-bottom: 10px;
        }

        .entry {
            position: relative;
            padding-left: <?= $atsFriendly ? '0' : '14px' ?>;
            margin-bottom: 11px;
            <?= $atsFriendly ? '' : 'border-left: 2px solid ' . $accentLight . ';' ?>
            page-break-inside: avoid;
        }

        .entry-dot {
            position: absolute;
            left: -5px;
            top: 3px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: <?= $accent ?>;
        }

        .entry-date {
            float: right;
            font-size: 9.5px;
            font-weight: bold;
            color: <?= $atsFriendly ? '#000000' : $accent ?>;
            <?= $atsFriendly ? '' : 'background: ' . $accentLight . '; padding: 1px 7px; border-radius: 8px;' ?>
        }

        .entry-title { font-size: 11.5px; font-weight: bold; }

        .entry-sub {
            color: #6B6B6B;
            font-style: italic;
            margin-bottom: 3px;
        }

        .entry-description ul,
        .entry-description ol { padding-left: 16px; margin-top: 2px; }
        .entry-description li { text-align: justify; padding-bottom: 1px; }

        .entry-stack { margin-top: 3px; font-size: 9.5px; color: #444444; }
        .entry-stack b { color: <?= $atsFriendly ? '#000000' : $accent ?>; }

        .skill-row { margin-bottom: 4px; }
        .skill-row b { color: <?= $atsFriendly ? '#000000' : $accent ?>; }

        .tag {
            display: inline-block;
            margin: 0 4px 4px 0;
            padding: 2px 8px;
            border-radius: 9px;
            background: <?= $atsFriendly ? '#FFFFFF' : $accentLight ?>;
            color: #333333;
            <?= $atsFriendly ? 'border: 1px solid #999999;' : '' ?>
        }

        .page-break-preview {
            border-top: 2px dashed #999999;
            text-align: center;
            color: #999999;
            margin: 24px 0;
            padding-top: 6px;
            font-size: 0.85em;
            text-transform: uppercase;
        }

        .clear { clear: both; }
    </style>
</head>
<body>

<div class="header">
    <?php if ($logoMeBase64): ?>
        <img class="header-photo" src="<?= $logoMeBase64 ?>" alt="Photo de profil">
    <?php endif; ?>
    <h1><?= htmlspecialchars($fullName) ?></h1>
    <?php if (!empty($information['job_title'])): ?>
        <div class="job-title"><?= htmlspecialchars($information['job_title']) ?></div>
    <?php endif; ?>
    <?php if (!empty($information['summary'])): ?>
        <p class="summary"><?= nl2br(htmlspecialchars($information['summary'])) ?></p>
    <?php endif; ?>
    <div class="clear"></div>
</div>

<?php if ($contacts): ?>
    <div class="contacts">
        <?php $i = 0; foreach ($contacts as $contact): ?>
            <?php if ($i++ > 0): ?><span class="sep">|</span><?php endif; ?>
            <span><?= htmlspecialchars($contact) ?></span>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="content">

    <?php if (!empty($skills)): ?>
        <div class="section">
            <div class="section-title">Compétences techniques</div>
            <?php foreach ($skills as $skill): ?>
                <div class="skill-row"><b><?= htmlspecialchars($skill['category']) ?> :</b> <?= htmlspecialchars($skill['items']) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

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
                        <?php if (!$atsFriendly): ?><div class="entry-dot"></div><?php endif; ?>
                        <?php $dates = $period(cvFormatMonthYear($experience['start_date']), cvFormatMonthYear($experience['end_date'])); ?>
                        <?php if ($dates !== ''): ?><span class="entry-date"><?= htmlspecialchars($dates) ?></span><?php endif; ?>
                        <div class="entry-title"><?= htmlspecialchars($experience['position']) ?></div>
                        <div class="entry-sub">
                            <?= htmlspecialchars($experience['company']) ?><?php if (!empty($experience['city'])): ?> · <?= htmlspecialchars($experience['city']) ?><?php endif; ?>
                        </div>
                        <?php if (!empty($experience['description'])): ?>
                            <div class="entry-description"><?= $experience['description'] ?></div>
                        <?php endif; ?>
                        <?php if (!empty($experience['tech_stack'])): ?>
                            <div class="entry-stack"><b>Environnement technique :</b> <?= htmlspecialchars($experience['tech_stack']) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if (!empty($formations)): ?>
        <div class="section">
            <div class="section-title">Formations</div>
            <?php foreach ($formations as $formation): ?>
                <div class="entry">
                    <?php if (!$atsFriendly): ?><div class="entry-dot"></div><?php endif; ?>
                    <?php $dates = $period($formation['start_date'] ?? '', $formation['end_date'] ?? ''); ?>
                    <?php if ($dates !== ''): ?><span class="entry-date"><?= htmlspecialchars($dates) ?></span><?php endif; ?>
                    <div class="entry-title"><?= htmlspecialchars($formation['degree']) ?></div>
                    <div class="entry-sub">
                        <?= htmlspecialchars($formation['school']) ?><?php if (!empty($formation['city'])): ?> · <?= htmlspecialchars($formation['city']) ?><?php endif; ?>
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
                    <?php if (!$atsFriendly): ?><div class="entry-dot"></div><?php endif; ?>
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
            <?php if ($atsFriendly): ?>
                <p><?= htmlspecialchars(implode(', ', array_column($hobbies, 'label'))) ?></p>
            <?php else: ?>
                <?php foreach ($hobbies as $hobby): ?>
                    <span class="tag"><?= htmlspecialchars($hobby['label']) ?></span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
