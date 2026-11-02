<?php

const CV_TEMPLATES = [
    'default' => [
        'label' => 'Modèle par défaut',
        'columns' => 2, // encart latéral, passe en 1 colonne en mode ATS
        'file'  => __DIR__ . '/../templates/cv.php',
    ],
    'modern' => [
        'label' => 'Moderne (bandeau bleu)',
        'columns' => 1,
        'file'  => __DIR__ . '/../templates/cv-modern.php',
    ],
    'elegant' => [
        'label' => 'Élégant (serif & doré)',
        'columns' => 1,
        'file'  => __DIR__ . '/../templates/cv-elegant.php',
    ],
];

function cvTemplateResolveFile(string $key): string
{
    return CV_TEMPLATES[$key]['file'] ?? CV_TEMPLATES['default']['file'];
}

/** @return array<string, array{label: string, file: string, columns: int}> */
function cvTemplateOptions(): array
{
    return CV_TEMPLATES;
}
