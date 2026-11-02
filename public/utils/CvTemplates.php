<?php

const CV_TEMPLATES = [
    'default' => [
        'label' => 'Modèle par défaut',
        'file'  => __DIR__ . '/../templates/cv.php',
    ],
    'modern' => [
        'label' => 'Moderne (bandeau bleu)',
        'file'  => __DIR__ . '/../templates/cv-modern.php',
    ],
    'elegant' => [
        'label' => 'Élégant (serif & doré)',
        'file'  => __DIR__ . '/../templates/cv-elegant.php',
    ],
];

function cvTemplateResolveFile(string $key): string
{
    return CV_TEMPLATES[$key]['file'] ?? CV_TEMPLATES['default']['file'];
}

/** @return array<string, array{label: string, file: string}> */
function cvTemplateOptions(): array
{
    return CV_TEMPLATES;
}
