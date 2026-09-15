# mon_cv

Initialisation du projet :

```
$ composer init
$ composer require pompdf/pompdf
$ composer require twig/twig
```

pour modifier le mot de passe dans **admin/.env** :

```
php -r "echo password_hash('ton_mdp', PASSWORD_DEFAULT);"
```

et stocker le mot de passe affiché.

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