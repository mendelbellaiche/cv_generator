# mon_cv

Initialisation du projet :

```
$ composer init
$ composer require pompdf/pompdf
$ composer require twig/twig
```

## Identifiants admin

Pour définir ou modifier l'identifiant et le mot de passe de l'admin :

```
php bin/admin-credentials.php
```

Le script demande l'identifiant puis le mot de passe (saisie masquée, 8 caractères minimum, confirmation) et écrit `ADMIN_USERNAME` et `ADMIN_PASSWORD_HASH` dans le `.env` à la racine du projet (lu par `public/admin/auth.php`). Les autres variables du `.env` sont conservées.

Voir [DEPLOY.md](DEPLOY.md#3-configurer-ladmin) pour la configuration sur le serveur.

## Dépendences

Si sqlite n'est pas installé (exemple avec php 8.4): 

```
sudo apt update
sudo apt install php8.4-sqlite3
```

```
sudo phpenmod -v 8.4 pdo_sqlite
sudo phpenmod -v 8.4 sqlite3
```

```
sudo systemctl restart php8.4-fpm
sudo systemctl restart apache2
```

Pour vérifier si sqlite est bien installé:
```
php -m | grep -i sqlite
```

Tu devrais voir pdo_sqlite et sqlite3 apparaître.

## Migrations

Il n'y a pas de runner de migrations : les scripts SQL dans `public/migrations` se lancent à la main, une seule fois, dans l'ordre :

```
cp moncv.sqlite moncv_template.sqlite
sqlite3 moncv.sqlite < migrations/001_add_cv_versions.sql
```

- `001_add_cv_versions.sql` : ajoute la table `cv_versions` (gestion de plusieurs versions de CV) et la colonne `cv_version_id` sur les tables de contenu. Le CV existant devient la "version 1".