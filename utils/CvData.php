<?php

function loadCvData(Database $db, int $versionId): array
{
    return [
        'information'     => $db->query("SELECT * FROM information WHERE cv_version_id = :v", ['v' => $versionId])->fetch(),
        'address'         => $db->query("SELECT * FROM address WHERE cv_version_id = :v", ['v' => $versionId])->fetch(),
        'hobbies'         => $db->query("SELECT * FROM hobbies WHERE cv_version_id = :v AND displayed = 1 ORDER BY label", ['v' => $versionId])->fetchAll(),
        'formations'      => $db->query("SELECT * FROM formations WHERE cv_version_id = :v ORDER BY start_date DESC", ['v' => $versionId])->fetchAll(),
        'experiences'     => $db->query("SELECT * FROM experiences WHERE cv_version_id = :v ORDER BY sort_order ASC", ['v' => $versionId])->fetchAll(),
        'certifications'  => $db->query("SELECT * FROM certifications WHERE cv_version_id = :v ORDER BY sort_order ASC", ['v' => $versionId])->fetchAll(),
        'skills'          => $db->query("SELECT * FROM skills WHERE cv_version_id = :v ORDER BY sort_order ASC", ['v' => $versionId])->fetchAll(),
    ];
}

function cvImageToBase64(string $path): string
{
    if (!is_file($path)) {
        return '';
    }

    $mimeType = mime_content_type($path) ?: 'image/png';

    return 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($path));
}

function cvFormatFullDate(?string $value): string
{
    if (!$value) {
        return '';
    }

    $date = DateTime::createFromFormat('Y-m-d', $value);

    if (!$date) {
        return $value;
    }

    $months = [
        1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'
    ];

    return $date->format('j') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y');
}

function cvFormatMonthYear(?string $value): string
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
