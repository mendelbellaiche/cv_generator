#!/usr/bin/env php
<?php

/**
 * Crée une nouvelle version de CV en ligne de commande.
 *
 * Demande le nom de la version, le template, puis les informations de base
 * (toutes facultatives : Entrée pour laisser vide) et les hobbies séparés par
 * des virgules. Les expériences et projets/freelance se gèrent ensuite dans l'admin.
 *
 * Usage : php bin/create-cv-version.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Ce script doit être lancé en ligne de commande.\n");
    exit(1);
}

$publicDir = dirname(__DIR__) . '/public';

require $publicDir . '/utils/Database.php';
require $publicDir . '/utils/CvTemplates.php';

function prompt(string $label): string
{
    fwrite(STDOUT, $label);
    $line = fgets(STDIN);

    if ($line === false) {
        fwrite(STDERR, "\nSaisie interrompue.\n");
        exit(1);
    }

    return trim($line);
}

/** Champ facultatif : retourne null si laissé vide. */
function promptOptional(string $label): ?string
{
    $value = prompt($label . ' (facultatif) : ');
    return $value === '' ? null : $value;
}

// Nom de la version
do {
    $name = prompt('Nom de la version : ');
    if ($name === '') {
        fwrite(STDERR, "Le nom est obligatoire.\n");
    }
} while ($name === '');

// Template
$templates = cvTemplateOptions();
$templateKeys = array_values(array_filter(
    ['default', 'elegant', 'modern'],
    fn (string $key) => isset($templates[$key])
));
// Templates ajoutés plus tard dans CvTemplates.php
foreach (array_keys($templates) as $key) {
    if (!in_array($key, $templateKeys, true)) {
        $templateKeys[] = $key;
    }
}

fwrite(STDOUT, "\nTemplate :\n");
foreach ($templateKeys as $i => $key) {
    fwrite(STDOUT, sprintf("  %d) cv %s - %s\n", $i, $key, $templates[$key]['label']));
}

while (true) {
    $choice = prompt('Choix [0] : ');
    if ($choice === '') {
        $choice = '0';
    }
    if (ctype_digit($choice) && isset($templateKeys[(int) $choice])) {
        $templateKey = $templateKeys[(int) $choice];
        break;
    }
    fwrite(STDERR, "Choix invalide.\n");
}

$atsAnswer = strtolower(prompt('Version optimisée ATS ? (o/N) : '));
$atsFriendly = in_array($atsAnswer, ['o', 'oui', 'y', 'yes'], true) ? 1 : 0;

// Informations
fwrite(STDOUT, "\nInformations (Entrée pour laisser vide)\n");
$information = [
    'firstname'    => promptOptional('Prénom'),
    'lastname'     => promptOptional('Nom'),
    'job_title'    => promptOptional('Intitulé du poste'),
    'email'        => promptOptional('Email'),
    'phone'        => promptOptional('Téléphone'),
    'birthdate'    => promptOptional('Date de naissance'),
    'summary'      => promptOptional('Résumé (2-3 lignes)'),
    'linkedin_url' => promptOptional('Lien LinkedIn'),
    'github_url'   => promptOptional('Lien GitHub'),
];

fwrite(STDOUT, "\nAdresse\n");
$address = [
    'line'    => promptOptional('Adresse (rue)'),
    'zipcode' => promptOptional('Code postal'),
    'city'    => promptOptional('Ville'),
    'country' => promptOptional('Pays'),
];

fwrite(STDOUT, "\n");
$hobbiesInput = promptOptional('Hobbies, séparés par des virgules');
$hobbies = $hobbiesInput === null
    ? []
    : array_values(array_filter(array_map('trim', explode(',', $hobbiesInput)), fn ($h) => $h !== ''));

// Enregistrement
$db = Database::getInstance($publicDir . '/moncv.sqlite');
$pdo = $db->getPdo();

try {
    $pdo->beginTransaction();

    $db->query("INSERT INTO cv_versions (name, template_key, ats_friendly) VALUES (:name, :template_key, :ats_friendly)", [
        'name'         => $name,
        'template_key' => $templateKey,
        'ats_friendly' => $atsFriendly,
    ]);
    $versionId = (int) $pdo->lastInsertId();

    $db->query(
        "INSERT INTO information (firstname, lastname, job_title, email, phone, birthdate, summary, linkedin_url, github_url, cv_version_id)
         VALUES (:firstname, :lastname, :job_title, :email, :phone, :birthdate, :summary, :linkedin_url, :github_url, :v)",
        $information + ['v' => $versionId]
    );

    $db->query(
        "INSERT INTO address (line, zipcode, city, country, cv_version_id) VALUES (:line, :zipcode, :city, :country, :v)",
        $address + ['v' => $versionId]
    );

    foreach ($hobbies as $label) {
        $db->query("INSERT INTO hobbies (label, cv_version_id) VALUES (:label, :v)", ['label' => $label, 'v' => $versionId]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Erreur lors de la création : " . $e->getMessage() . "\n");
    exit(1);
}

fwrite(STDOUT, sprintf(
    "\nVersion #%d \"%s\" créée (template : %s%s, %d hobby(s)).\n",
    $versionId,
    $name,
    $templateKey,
    $atsFriendly ? ', ATS' : '',
    count($hobbies)
));
fwrite(STDOUT, "Expériences, formations, compétences et certifications : /admin/experiences.php?v=$versionId\n");
