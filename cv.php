<?php

require_once __DIR__ . "/vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;

require __DIR__.'/utils/Database.php';
require __DIR__.'/utils/CvData.php';
require __DIR__.'/utils/CvTemplates.php';

$db = Database::getInstance(__DIR__ . '/moncv.sqlite');

$versionId = isset($_GET['version']) ? (int) $_GET['version'] : null;
$version = $versionId ? $db->query("SELECT * FROM cv_versions WHERE id = :id", ['id' => $versionId])->fetch() : null;

if (!$version) {
    http_response_code(404);
    exit('CV introuvable.');
}

extract(loadCvData($db, $versionId));

$iconsPath = __DIR__ . '/images/';
$profileImagePath = __DIR__ . '/images/custom/' . ($information['image_path'] ?? '');
$renderTarget = 'pdf';

ob_start();
require cvTemplateResolveFile($version['template_key']);
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$fileName = 'CV-' . ($information['firstname'] ?? 'cv') . '-' . ($information['lastname'] ?? '') . '.pdf';

$dompdf->stream($fileName, ['Attachment' => false]);
