<?php
    require_once __DIR__ . '/../../utils/CvVersion.php';
    global $currentVersionId;
?>
<aside>
    <h1 class="text-white">Admin</h1>
    <ul class="p-0">
        <li><a href="<?= cvVersionLink('/admin/information.php', $currentVersionId) ?>" class="text-white"><img src="/admin/assets/icons/person.png" alt="">Informations</a></li>
        <li><a href="<?= cvVersionLink('/admin/skills.php', $currentVersionId) ?>" class="text-white"><img src="/admin/assets/icons/competences.png" alt="">Compétences</a></li>
        <li><a href="<?= cvVersionLink('/admin/formations.php', $currentVersionId) ?>" class="text-white"><img src="/admin/assets/icons/livre.png" alt="">Formations</a></li>
        <li><a href="<?= cvVersionLink('/admin/certifications.php', $currentVersionId) ?>" class="text-white"><img src="/admin/assets/icons/certification.png" alt="">Certifications</a></li>
        <li><a href="<?= cvVersionLink('/admin/experiences.php', $currentVersionId) ?>" class="text-white"><img src="/admin/assets/icons/sac.png" alt="">Expériences</a></li>
        <li><a href="<?= cvVersionLink('/admin/hobbies.php', $currentVersionId) ?>" class="text-white"><img src="/admin/assets/icons/hobbies.png" alt="">Hobbies</a></li>
        <li><a href="<?= cvVersionLink('/admin/preview.php', $currentVersionId) ?>" class="text-white"><img src="/admin/assets/icons/oeil.png" alt="">Preview</a></li>
        <li><a href="<?= cvVersionLink('/admin/ats-score.php', $currentVersionId) ?>" class="text-white"><img src="/admin/assets/icons/certification.png" alt="">Score ATS</a></li>
        <li><a href="/admin/index.php" class="text-white"><img src="/admin/assets/icons/person.png" alt=""> Mes CV</a></li>
        <li><a href="/admin/auth.php?logout=1" class="text-white"><img src="/admin/assets/icons/logout.png" alt=""> Déconnexion</a></li>
    </ul>
</aside>
