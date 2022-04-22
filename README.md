# Snowtrick
## Un projet réalisé par Edouard Plantevin

## Prérequis

1) Avoir un serveur local (example: MAMP)
2) Yarn
3) Composer

## Installation

Démarrez votre serveur local

Clonez le projet Github

```sh
git clone https://github.com/EdouardPlantevin/Snowtrick.git
```

Une fois téléchargé

```sh
cd Snowtrick
composer install
yarn install
yarn build
```

## Base de donnée

1) Je vous invite maintenant à modifier le fichier .env, modifier les variables ["MAILER_DSN", "DATABASE_URL"] selon votre choix d'envoie d'email et votre environnement

2) Créer une base de donnée

```sh
php bin/console doctrine:database:create 
```

3) Lancer une migration

```sh
php bin/console doctrine:migrations:migrate 
```

4) Remplir la base de donnée
```sh
php bin/console doctrine:fixture:load
```


Et voilà

## Quelques informations 

Voici un compte:

    - username: Edouard
    - mot de passe: password


