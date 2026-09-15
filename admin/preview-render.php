<?php
session_start();

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: auth.php');
    exit;
}

require __DIR__.'/../utils/Database.php';
require __DIR__.'/../utils/CvData.php';
require __DIR__.'/../utils/CvVersion.php';
require __DIR__.'/../utils/CvTemplates.php';

$db = Database::getInstance(__DIR__ . '/../moncv.sqlite');

$versionId = isset($_GET['v']) ? (int) $_GET['v'] : null;
if (!$versionId || !cvVersionExists($db, $versionId)) {
    http_response_code(400);
    exit('Version de CV invalide.');
}

$version = $db->query("SELECT * FROM cv_versions WHERE id = :id", ['id' => $versionId])->fetch();

extract(loadCvData($db, $versionId));

$iconsPath = __DIR__ . '/../images/';
$profileImagePath = __DIR__ . '/../images/custom/' . ($information['image_path'] ?? '');
$renderTarget = 'html';

require cvTemplateResolveFile($version['template_key']);
