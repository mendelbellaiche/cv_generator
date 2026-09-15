<?php
/**
 * Template CV partagé entre la preview admin (admin/preview.php) et la génération PDF (cv.php).
 * Variables attendues : $information, $address, $hobbies, $experiences, $certifications, $skills,
 * $iconsPath (dossier disque des icônes), $profileImagePath (chemin disque de la photo de profil),
 * $renderTarget ('pdf' ou 'html' — Dompdf répète les éléments "fixed" sur chaque page, un navigateur
 * les fige par rapport à la fenêtre : il faut donc "absolute" pour le rendu navigateur).
 */

$renderTarget ??= 'pdf';
$sidebarBackgroundPosition = 'fixed'; // $renderTarget === 'pdf' ? 'fixed' : 'absolute';

$logoMeBase64 = cvImageToBase64($profileImagePath);
$logoLetterBase64 = cvImageToBase64($iconsPath . 'letter.png');
$logoTelBase64 = cvImageToBase64($iconsPath . 'tel.png');
$logoPinBase64 = cvImageToBase64($iconsPath . 'pin.png');
$logoCakeBase64 = cvImageToBase64($iconsPath . 'cake.png');
$logoLinkedinBase64 = cvImageToBase64($iconsPath . 'linkedinIcon.png');

$fullName = trim(($information['firstname'] ?? '') . ' ' . strtoupper($information['lastname'] ?? ''));

$hobbyChunks = array_chunk($hobbies, (int) ceil(count($hobbies) / 2) ?: 1);
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
        @page {
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Trebuchet MS', sans-serif;
            font-size: 11px;
            padding-top: 40px;
            padding-bottom: 40px;
        }

        .cv-sidebar-background {
            position: <?= $sidebarBackgroundPosition ?>;
            background: #EDEDED;
            top: 0;
            bottom: 0;
            left: 0;
            width: 260px;
            z-index: -1;
        }

        .cv-sidebar {
            padding: 20px;
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 220px;
        }

        .cv-content {
            margin-left: 290px;
            width: calc(100% - 370px);
            padding: 20px;
        }

        .cv-section-title {
            font-size: 2em;
            font-style: italic;
            margin-bottom: 20px;
        }

        .cv-divider {
            background: #DFDFDF;
            height: 1px;
            width: 100%;
            margin-bottom: 20px;
        }

        .cv-entry-description ul,
        .cv-entry-description ol {
            padding-left: 18px;
            margin-top: 4px;
        }

        .cv-entry-description li {
            text-align: justify;
            padding-bottom: 5px;
        }

        .cv-interests ul {
            list-style-type: square;
            padding-left: 16px;
        }

        .cv-interests li {
            padding-bottom: 8px;
            font-size: 1.1em;
        }
    </style>
</head>
<body style="position: relative;">

<div class="cv-sidebar-background"></div>

<aside class="cv-sidebar">

    <?php if ($logoMeBase64): ?>
        <p><img src="<?= $logoMeBase64 ?>" alt="Photo de profile" style="width: 60%; margin-left: 20%; margin-bottom: 20px; display: block; border-radius: 50%;"></p>
    <?php endif; ?>

    <h1><?= htmlspecialchars($fullName) ?></h1>
    <?php if (!empty($information['job_title'])): ?>
        <h2 style="font-size: 1.2em; margin-bottom: 30px;"><?= htmlspecialchars($information['job_title']) ?></h2>
    <?php endif; ?>

    <h3 style="border-bottom: 2px solid #6A6A6A; padding-bottom: 2px; margin-bottom: 20px;">Détails</h3>

    <?php if (!empty($information['email'])): ?>
        <p style="margin-bottom: 8px;"><img src="<?= $logoLetterBase64 ?>" alt="logo email" style="width: 16px; margin-right: 2px; position: relative; top: 4px;" /> <span><?= htmlspecialchars($information['email']) ?></span></p>
    <?php endif; ?>

    <?php if (!empty($information['phone'])): ?>
        <p style="margin-bottom: 8px;"><img src="<?= $logoTelBase64 ?>" alt="logo telephone" style="width: 16px; margin-right: 2px; position: relative; top: 4px;" /> <span><?= htmlspecialchars($information['phone']) ?></span></p>
    <?php endif; ?>

    <?php if (!empty($address['line']) || !empty($address['city'])): ?>
        <p style="margin-bottom: 8px;"><img src="<?= $logoPinBase64 ?>" alt="logo adresse" style="width: 16px; margin-right: 2px; position: relative; top: 4px;" /> <span><?= htmlspecialchars($address['line'] ?? '') ?>, <?= htmlspecialchars($address['zipcode'] ?? '') ?>, <?= htmlspecialchars($address['city'] ?? '') ?></span></p>
    <?php endif; ?>

    <?php if (!empty($information['birthdate'])): ?>
        <p style="margin-bottom: 8px;"><img src="<?= $logoCakeBase64 ?>" alt="logo date de naissance" style="width: 20px; margin-right: 0; position: relative; top: 4px;" /> <span><?= htmlspecialchars(cvFormatFullDate($information['birthdate'])) ?></span></p>
    <?php endif; ?>

    <?php if (!empty($information['linkedin_url'])): ?>
        <p style="margin-bottom: 8px;"><a href="<?= htmlspecialchars($information['linkedin_url']) ?>" target="_blank"><img src="<?= $logoLinkedinBase64 ?>" alt="logo linkedin" style="width: 20px; margin-right: 0; position: relative; top: 4px;" /></a></p>
    <?php endif; ?>

    <?php if (!empty($skills)): ?>
        <h3 style="border-bottom: 2px solid #6A6A6A; padding-bottom: 2px; margin-top: 20px; margin-bottom: 20px;">Compétences téchniques</h3>

        <?php foreach ($skills as $skill): ?>
            <p style="margin-bottom: 8px;"><b><?= htmlspecialchars($skill['category']) ?></b>:<br /><?= htmlspecialchars($skill['items']) ?></p>
        <?php endforeach; ?>
    <?php endif; ?>
</aside>

<div class="cv-content">

    <?php if (!empty($experiences)): ?>
        <p class="cv-section-title" style="margin-bottom: 40px;">Expériences professionnelles</p>

        <?php foreach ($experiences as $experience): ?>
            <?php if (!empty($experience['page_break_before'])): ?>
                <?php if ($renderTarget === 'pdf'): ?>
                    <div style="page-break-before: always;"></div>
                <?php else: ?>
                    <div style="border-top: 2px dashed #999999; text-align: center; color: #999999; margin: 30px 0; padding-top: 8px; font-size: 0.85em; text-transform: uppercase;">— Nouvelle page —</div>
                <?php endif; ?>
            <?php endif; ?>
            <table style="margin-bottom: 20px; width: 100%;">
                <tr>
                    <td><b><?= htmlspecialchars($experience['position']) ?></b></td>
                    <td style="text-align: right;"><b><?= htmlspecialchars(cvFormatMonthYear($experience['start_date'])) ?> - <?= htmlspecialchars(cvFormatMonthYear($experience['end_date'])) ?></b></td>
                </tr>
                <tr>
                    <td colspan="2" style="color: #8E8E8E; padding-bottom: 10px;">
                        <?= htmlspecialchars($experience['company']) ?><?php if (!empty($experience['city'])): ?>, <?= htmlspecialchars($experience['city']) ?><?php endif; ?>
                    </td>
                </tr>
                <?php if (!empty($experience['description'])): ?>
                    <tr>
                        <td colspan="2" class="cv-entry-description" style="padding-bottom: 10px;">
                            <?= $experience['description'] ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php if (!empty($experience['tech_stack'])): ?>
                    <tr>
                        <td colspan="2">
                            <b>Environment technique</b>:<br />
                            <?= htmlspecialchars($experience['tech_stack']) ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>

            <div class="cv-divider"></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($formations)): ?>
        <p class="cv-section-title" style="margin-bottom: 40px;">Formations</p>

        <?php foreach ($formations as $formation): ?>
            <table style="margin-bottom: 20px; width: 100%;">
                <tr>
                    <td><b><?= htmlspecialchars($formation['degree']) ?></b></td>
                    <td style="text-align: right;"><b><?= htmlspecialchars($formation['start_date']) ?> - <?= htmlspecialchars($formation['end_date']) ?></b></td>
                </tr>
                <tr>
                    <td colspan="2" style="color: #8E8E8E; padding-bottom: 10px;">
                        <?= htmlspecialchars($formation['school']) ?><?php if (!empty($formation['city'])): ?>, <?= htmlspecialchars($formation['city']) ?><?php endif; ?>
                    </td>
                </tr>
                <?php if (!empty($formation['description'])): ?>
                    <tr>
                        <td colspan="2" class="cv-entry-description" style="padding-bottom: 10px;">
                            <?= $formation['description'] ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>

            <div class="cv-divider"></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($certifications)): ?>
        <p class="cv-section-title">Certifications</p>

        <?php foreach ($certifications as $certification): ?>
            <p style="margin-bottom: 20px;">
                <b><?= htmlspecialchars($certification['title']) ?></b><br />
                <?php if (!empty($certification['exam_version'])): ?>
                    Exam Version: <b><?= htmlspecialchars($certification['exam_version']) ?></b><br />
                <?php endif; ?>
                <?= htmlspecialchars($certification['year'] ?? '') ?>
            </p>
        <?php endforeach; ?>

        <div class="cv-divider"></div>
    <?php endif; ?>

    <?php if (!empty($hobbies)): ?>
        <p class="cv-section-title">Centres d'intérêt</p>

        <table class="cv-interests" style="width: 100%;">
            <tr>
                <?php foreach ($hobbyChunks as $chunk): ?>
                    <td style="width: 50%; vertical-align: top;">
                        <ul>
                            <?php foreach ($chunk as $hobby): ?>
                                <li><?= htmlspecialchars($hobby['label']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </td>
                <?php endforeach; ?>
            </tr>
        </table>
    <?php endif; ?>

</div>

</body>
</html>
