# Déploiement

Application PHP simple (pas de framework, pas de build front) : PHP + SQLite + Composer (dompdf, twig).

## Prérequis serveur

- PHP >= 8.1 avec les extensions : `pdo`, `pdo_sqlite`, `sqlite3`, `mbstring`, `gd` (utilisée par dompdf pour les images)
- Composer (pour installer les dépendances, en local ou sur le serveur)
- Un serveur web (Apache ou Nginx) avec le document root pointant sur la racine du projet

Vérifier que sqlite est bien activé :

```
php -m | grep -i sqlite
```

Si absent (exemple PHP 8.4 sur Debian/Ubuntu) :

```
sudo apt update
sudo apt install php8.4-sqlite3
sudo phpenmod -v 8.4 pdo_sqlite
sudo phpenmod -v 8.4 sqlite3
sudo systemctl restart php8.4-fpm
sudo systemctl restart apache2
```

## 1. Récupérer le code

Sur le serveur, le projet est cloné dans `/var/www` et le document root du serveur web pointe sur `/var/www/html` (= le dossier `public/` du repo).

```
git clone <repo> /var/www/cv_generator
ln -s /var/www/cv_generator/public /var/www/html
cd /var/www/cv_generator
```

(Adapter selon l'installation existante : `/var/www/html` peut être directement le dossier `public/` copié ou lié en symlink, l'essentiel étant que le document root serve son contenu.)

## 2. Installer les dépendances PHP

```
composer install --no-dev --optimize-autoloader
```

## 3. Configurer l'admin

Le fichier `.env` n'est **pas** versionné (voir `.gitignore`). Il est chargé depuis la racine du projet, deux niveaux au-dessus de `public/admin` (voir `public/admin/auth.php`), soit `/var/www/.env` puisque `public/` = `/var/www/html`. Le créer sur le serveur :

```
touch /var/www/.env
```

Contenu attendu :

```
ADMIN_USERNAME=admin
ADMIN_PASSWORD_HASH=<hash>
```

Générer le hash du mot de passe choisi :

```
php -r "echo password_hash('ton_mdp', PASSWORD_DEFAULT);"
```

Copier la sortie dans `ADMIN_PASSWORD_HASH`.

## 4. Base de données SQLite

`public/moncv.sqlite` (donc `/var/www/html/moncv.sqlite`) n'est pas non plus versionné. Partir du template fourni :

```
cd /var/www/html
cp moncv_template.sqlite moncv.sqlite
```

Le template contient déjà le schéma (`000_schema.sql`), inutile de le rejouer. Appliquer seulement les migrations suivantes, dans l'ordre, à la main (il n'y a pas de runner) :

```
sqlite3 moncv.sqlite < migrations/001_add_cv_versions.sql
sqlite3 moncv.sqlite < migrations/002_add_primary_version.sql
```

> Si `public/moncv.sqlite` existe déjà en production, ne rejouer que les migrations pas encore appliquées.

## 5. Permissions

Le fichier SQLite et son dossier parent doivent être accessibles en écriture par l'utilisateur du serveur web :

```
chown www-data:www-data moncv.sqlite
chmod 664 moncv.sqlite
```

## 6. Configuration du serveur web

Document root = `/var/www/html`, qui correspond au dossier `public/` du projet (`public/index.php` sert la page publique, `public/admin/index.php` l'espace admin). Le fichier `.env` reste hors du document root, dans `/var/www/.env`, donc jamais accessible via le serveur web.

Exemple Apache (vhost) :

```apache
<VirtualHost *:80>
    ServerName moncv.example.com
    DocumentRoot /var/www/html

    <Directory /var/www/html>
        AllowOverride All
        Require all granted
    </Directory>

    # Empêcher l'accès direct aux fichiers sensibles
    <FilesMatch "\.(sqlite|env)$">
        Require all denied
    </FilesMatch>
</VirtualHost>
```

Exemple Nginx + PHP-FPM :

```nginx
server {
    listen 80;
    server_name moncv.example.com;
    root /var/www/html;
    index index.php;

    location ~ \.(sqlite|env)$ {
        deny all;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## 7. Vérification

- `https://<domaine>/` : page d'accueil avec lien vers le CV et l'espace admin
- `https://<domaine>/admin/auth.php` : connexion avec `ADMIN_USERNAME` / mot de passe choisi
- `https://<domaine>/cv.php?version=<id>` : génération du PDF du CV

## Mise à jour d'un déploiement existant

```
git pull
composer install --no-dev --optimize-autoloader
```

Rejouer les nouvelles migrations SQL ajoutées dans `public/migrations` (comparer avec ce qui a déjà été appliqué en prod), puis vérifier `/var/www/.env` si de nouvelles clés sont apparues.
