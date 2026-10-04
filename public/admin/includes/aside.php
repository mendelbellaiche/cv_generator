<?php
    require_once __DIR__ . '/../../utils/CvVersion.php';
    global $currentVersionId;

    $currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $navActive = fn(string $script) => $script === $currentScript ? ' active' : '';
?>
<aside>
    <h1 class="text-white">Admin</h1>
    <ul class="p-0">
        <li><a href="<?= cvVersionLink('/admin/information.php', $currentVersionId) ?>" class="text-white<?= $navActive('information.php') ?>"><img src="/admin/assets/icons/person.png" alt=""><span class="nav-label">Informations</span></a></li>
        <li><a href="<?= cvVersionLink('/admin/skills.php', $currentVersionId) ?>" class="text-white<?= $navActive('skills.php') ?>"><img src="/admin/assets/icons/competences.png" alt=""><span class="nav-label">Compétences</span></a></li>
        <li><a href="<?= cvVersionLink('/admin/formations.php', $currentVersionId) ?>" class="text-white<?= $navActive('formations.php') ?>"><img src="/admin/assets/icons/livre.png" alt=""><span class="nav-label">Formations</span></a></li>
        <li><a href="<?= cvVersionLink('/admin/certifications.php', $currentVersionId) ?>" class="text-white<?= $navActive('certifications.php') ?>"><img src="/admin/assets/icons/certification.png" alt=""><span class="nav-label">Certifications</span></a></li>
        <li><a href="<?= cvVersionLink('/admin/experiences.php', $currentVersionId) ?>" class="text-white<?= $navActive('experiences.php') ?>"><img src="/admin/assets/icons/sac.png" alt=""><span class="nav-label">Expériences</span></a></li>
        <li><a href="<?= cvVersionLink('/admin/hobbies.php', $currentVersionId) ?>" class="text-white<?= $navActive('hobbies.php') ?>"><img src="/admin/assets/icons/hobbies.png" alt=""><span class="nav-label">Hobbies</span></a></li>
        <li><a href="<?= cvVersionLink('/admin/preview.php', $currentVersionId) ?>" class="text-white<?= $navActive('preview.php') ?>"><img src="/admin/assets/icons/oeil.png" alt=""><span class="nav-label">Preview</span></a></li>
        <li><a href="<?= cvVersionLink('/admin/ats-score.php', $currentVersionId) ?>" class="text-white<?= $navActive('ats-score.php') ?>"><img src="/admin/assets/icons/certification.png" alt=""><span class="nav-label">Score ATS</span></a></li>
        <li><a href="/admin/index.php" class="text-white<?= $navActive('index.php') ?>"><img src="/admin/assets/icons/person.png" alt=""><span class="nav-label">Mes CV</span></a></li>
        <li><a href="/admin/auth.php?logout=1" class="text-white"><img src="/admin/assets/icons/logout.png" alt=""><span class="nav-label">Déconnexion</span></a></li>
    </ul>
</aside>
