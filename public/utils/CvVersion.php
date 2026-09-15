<?php

/**
 * Résout la version de CV "active" pour la requête admin en cours.
 * Ordre de priorité : ?v= (query param) > session > première version disponible.
 * Retourne null si aucune version n'existe.
 */
function cvVersionResolveCurrent(Database $db): ?int
{
    $requested = isset($_GET['v']) ? (int) $_GET['v'] : null;

    if ($requested && cvVersionExists($db, $requested)) {
        $_SESSION['cv_version_id'] = $requested;
        return $requested;
    }

    $sessionVersion = $_SESSION['cv_version_id'] ?? null;
    if ($sessionVersion && cvVersionExists($db, (int) $sessionVersion)) {
        return (int) $sessionVersion;
    }

    $first = $db->query("SELECT id FROM cv_versions ORDER BY id ASC LIMIT 1", [])->fetch();
    if ($first) {
        $_SESSION['cv_version_id'] = (int) $first['id'];
        return (int) $first['id'];
    }

    return null;
}

function cvVersionExists(Database $db, int $id): bool
{
    return (bool) $db->query("SELECT id FROM cv_versions WHERE id = :id", ['id' => $id])->fetch();
}

/** Construit un lien vers une page admin en y ajoutant la version courante. */
function cvVersionLink(string $path, int $versionId): string
{
    $separator = str_contains($path, '?') ? '&' : '?';
    return $path . $separator . 'v=' . $versionId;
}
