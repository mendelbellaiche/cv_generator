#!/usr/bin/env php
<?php

/**
 * Définit l'identifiant et le mot de passe de l'admin.
 *
 * Demande l'identifiant et le mot de passe (saisie masquée + confirmation),
 * puis écrit ADMIN_USERNAME et ADMIN_PASSWORD_HASH dans le .env à la racine du projet
 * (celui lu par public/admin/auth.php). Les autres variables du .env sont conservées.
 *
 * Usage : php bin/admin-credentials.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Ce script doit être lancé en ligne de commande.\n");
    exit(1);
}

$envPath = dirname(__DIR__) . '/.env';

function prompt(string $label): string
{
    fwrite(STDOUT, $label);
    $line = fgets(STDIN);

    if ($line === false) {
        fwrite(STDERR, "\nSaisie interrompue.\n");
        exit(1);
    }

    return rtrim($line, "\r\n");
}

function promptHidden(string $label): string
{
    $canHide = DIRECTORY_SEPARATOR === '/' && stream_isatty(STDIN);

    if ($canHide) {
        shell_exec('stty -echo');
    }

    try {
        $value = prompt($label);
    } finally {
        if ($canHide) {
            shell_exec('stty echo');
            fwrite(STDOUT, "\n");
        }
    }

    return $value;
}

// Identifiant
do {
    $username = trim(prompt('Identifiant admin : '));

    if ($username === '') {
        fwrite(STDERR, "L'identifiant ne peut pas être vide.\n");
    } elseif (preg_match('/\s/', $username)) {
        fwrite(STDERR, "L'identifiant ne doit pas contenir d'espaces.\n");
        $username = '';
    }
} while ($username === '');

// Mot de passe
while (true) {
    $password = promptHidden('Mot de passe : ');

    if (strlen($password) < 8) {
        fwrite(STDERR, "Le mot de passe doit faire au moins 8 caractères.\n");
        continue;
    }

    if (promptHidden('Confirmer le mot de passe : ') !== $password) {
        fwrite(STDERR, "Les mots de passe ne correspondent pas.\n");
        continue;
    }

    break;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

// Mise à jour du .env en conservant les autres lignes
$lines = is_file($envPath) ? file($envPath, FILE_IGNORE_NEW_LINES) : [];
$values = [
    'ADMIN_USERNAME' => $username,
    'ADMIN_PASSWORD_HASH' => $hash,
];

foreach ($lines as $i => $line) {
    if (!str_contains($line, '=')) {
        continue;
    }

    $key = trim(explode('=', $line, 2)[0]);

    if (array_key_exists($key, $values)) {
        $lines[$i] = $key . '=' . $values[$key];
        unset($values[$key]);
    }
}

foreach ($values as $key => $value) {
    $lines[] = $key . '=' . $value;
}

if (file_put_contents($envPath, implode("\n", $lines) . "\n") === false) {
    fwrite(STDERR, "Impossible d'écrire dans $envPath\n");
    exit(1);
}

fwrite(STDOUT, "Identifiants admin enregistrés dans $envPath\n");
