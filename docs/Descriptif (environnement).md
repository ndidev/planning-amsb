# Descriptif du planning (environnement)

1. [Généralités](#généralités)
2. [Machine virtuelle](#machine-virtuelle)
3. [Docker](#docker)
4. [Technologies/Logiciels](#technologieslogiciels)
5. [Arborescence de la VM](#arborescence-de-la-vm)

## Généralités

Le planning est une application web entièrement isolée (aucun lien avec une API externe, aucune
connexion à un stockage extérieur, etc.).

L'application est composée de :

- front end
- back end :
  - API
  - base de données

Le rendu se fait totalement côté client : l'API récupère les informations sur la base de données et
les transmet au format JSON au client, qui se charge de les mettre en forme et les afficher.

Docker est utilisé pour l'ensemble des logiciels servant à l'application.

## Machine virtuelle

Le code est stocké sur la VM hébergée chez Axiom (Debian 10), dans le dossier `home/` de
l'utilisateur **webmaster**.

Il y a un dossier `prod/` et un dossier `dev/`, qui hébergent respectivement le planning de
production et celui qui sert aux tests.

Un crontab (utilisateur **webmaster**) permet de sauvgarder la BDD quotidiennement, ainsi que
d'envoyer quotidiennement le planning par e-mail à certains clients.

## Docker

Le serveur web, PHP, le gestionnaire de BDD ainsi que phpMyAdmin sont lancés dans des conteneurs
Docker.

Chaque dossier `prod/` et `dev/` contient un dossier `docker/` avec la configuration qui lui est
propre (il y a quelques différences entre prod et dev).

Des scripts Bash (`.sh`) sont disponibles pour faciliter la gestion des ressources Docker
(conteneurs et volumes), notamment le fichier `reup.sh` qui permet, après un changement de
configuration, de supprimer les conteneurs existants, reconstruire les images et relancer les
nouveaux conteneurs automatiquement (sans grosse modification, environ 10 secondes en tout).

## Technologies/logiciels

Interface utilisateur : HTML/JavaScript/CSS  
API : PHP  
Base de données : MariaDB

### Front end

La partie client de l'application est écrite en HTML/JavaScript/CSS (pas de TypeScript, SCSS,
etc.).  
Aucun framework n'est utilisé (React, Vue, etc.).

Le gestionnaire de paquets NPM est utilisé pour les quelques librairies utilisées (voir le fichier
`package.json`).

Le bundler [Parcel](https://parceljs.org) est utilisé avec une configuration très minimale.  
Le code servi au client (qu'il faut uploader sur le serveur) se trouve dans le dossier `dist/` après
avoir effectué la commande `npm run build`.

Un fichier `.env` permet de stocker quelques variables d'environnement utiles lors de la
compilation.

### Back end

#### Serveur

Le serveur web est Apache (2.4.52 au moment de l'écriture de cette documentation).

La configuration est disponible dans les fichiers `docker/apache/000-default.conf` et
`docker/apache/default-ssl.conf`.

**Important** : il faut conserver la version HTTP car la version HTTPS repose sur un certificat
self-signed que les navigateurs n'acceptent qu'après validation de l'utilisateur. Or ceci est
compliqué sur les TV de l'agence qui sont rattachées à des Raspberri Pi (pas de clavier ni souris
sur les RPi).

Les logs Apache sont disponibles avec la commande `docker logs planning-prod-www` (ou
`planning-dev-www`).  
Les logs PHP sont dans le fichier `/var/log/apache2/php.log` du conteneur, accessible via la
commande `docker exec -it planning-prod-www tail -f /var/log/apache2/php.log`. Ils sont produits par
le fichier `api/utils/functions/error_logger.php` et sont censés être suffisamment explicites pour
débugger.

#### API

L'API est écrite intégralement en PHP (>= 8.1).

Le gestionnaire de paquets [Composer](https://getcomposer.org) est utilisé.

#### Base de données

La base de données est gérée par MariaDB (version 10.6.4).

Elle est stockée dans un volume Docker sur la VM (`mysql-data-prod` et `mysql-data-dev`).

Une interface phpMyAdmin est accessible sur le port **8001** (version prod) et **8081** (version
dev) de la VM, afin de gérer plus facilement la BDD.

Deux sauvegardes de la BDD sont effectuées chaque jour (cron job) :

- une sauvgarde (issue de `mysqldump`) est envoyée par mail (voir fichier
  `/home/webmaster/prod/docker/db/email_db_backup.sh`)
- l'autre sauvgarde (totalité du volume Docker) est enregistrée dans
  `/home/webmaster/prod/docker/db/backup/db_volume.tar.gz` et `/home/webmaster/db/db_volume.tar.gz`

## Arborescence de la VM

```text
/home/webmaster
├── db
│   ├── db_volume.tar.gz
│   └── README
│
├── dev
│   ├── docker
│   │   ├── apache
│   │   ├── cleanup.sh
│   │   ├── db
│   │   ├── docker-compose.yml
│   │   ├── Dockerfile.db
│   │   ├── Dockerfile.web
│   │   ├── php.ini
│   │   └── reup.sh
│   └── www
│       ├── api
│       └── html
│
├── logs
│   ├── backup_db.log
│   └── envoi_pdf.log
│
└── prod
    ├── docker
    │   ├── apache
    │   ├── cleanup.sh
    │   ├── db
    │   ├── docker-compose.yml
    │   ├── Dockerfile.db
    │   ├── Dockerfile.web
    │   ├── php.ini
    │   └── reup.sh
    └── www
        ├── api
        └── html
```
