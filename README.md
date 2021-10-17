# HelloDoli

Hellodoli permet de faire l'interface entre Hello Asso et Dolibarr. Les transactions enregistrées dans HelloAsso sont créées dans Dolibarr : les adhérents sont créés avec leur adhésion. Les transactions sont ajoutées sur le compte en banque dans Dolibarr.

Hellodoli est développé avec Symfony et nécessite un serveur Web Apache + PHP 7.4 ou +, composer, ainsi qu'une base mysql ou mariadb.

# Pré-requis

Vous devez disposer d'un compte Hello Asso ainsi que d'une installation Dolibarr déjà configurée (type d'adhésion créé, compte ne banque créé etc...).
Les modules Dolibarr suivants doivent être activés et configurés : Adhérents, Dons, Banques et caisses, API REST.
Vous devez créé une base mariadb ou mysql avec un compte utilisateur associé

# 1. Configuration de hellodoli 

Il faut configurer le fichier .env situé à la racine du répertoire avec les paramètres suivants : 

APP_ENV=dev 
--> mettre dev si vous êtes en environnement de dev ou prod pour l'environnement de production

DATABASE_URL="mysql://dbUser:DbPassword@127.0.0.1:3306/dbName?serverVersion=mariadb-10.3.28"
--> remplacer dbUser par le nom de l'utilisateur ayant les droits de lecture / écriture sur la base de données
--> remplacer dbPassword par le mot de passe de l'utilisateur
--> remplacer dbName par le nom de la base de données
--> serverVersion=mariadb-10.3.28 : indiquer la version de la base de données mysql ou mariadb

DOLIBARR_KEY=YOUR_DOLIBARR_API_KEY
--> indiquer ici votre clé d'API dolibarr. Vous pouvez la générer depuis la configuration du module API REST.

DOLIBARR_URL=http://localhost/dolibarr/htdocs/api/index.php/
--> indiquer ici l'url de l'api Dolibarr. Vous pouvez la trouver depuis la configuration du module API REST.

DOLIBARR_ACC_ID=1
--> indiquer ici l'identifiant Dolibarr du compte en banque sur lequel vous voulez référencer les transactions HelloAsso.

DOLIBARR_ADH_START_DATE='1st May'
--> date du premier jour de l'adhésion (permet à hellodoli de créer l'adhésion pour chaque nouvel adhérent enregistré).

DOLIBARR_ADH_END_DATE="30th April"
--> date du dernier jour de l'adhésion.

Une fois cette configuration terminée lancer les commandes suivantes : 

composer install
php bin/console doctrine:migrations:migrate

se rendre ensuite sur http://votreURL/api

Vous devez voir une page de ce type (page standard API Platform) : 

![image](https://user-images.githubusercontent.com/10023914/137630477-606c924a-baa1-4d0d-94d3-f77b5d00bf01.png)

# 2. Configuration Hello Asso

Il faut configurer l'URL de callback de Hello Asso pour lui indiquer l'adresse à laquelle Hello Asso doit envoyer ses requêtes. Pour cela rendez-vous dans votre interface de gestion Hello Asso, rubrique "Mon compte" puis "Intégrations et API". Dans la rubrique "Notifications" vous pouvez renseigner une URL de callback de la façon suivante : 

https://URL_DE_VOTRE_SERVEUR/api/notifications

# 3. Configuration CRON

hellodoli utilise le composant messenger de symfony qui doit être ordonnancé avec CRON. Pour cela vous pouvez créé un CRON pour exécuter la commande suivante : 

/path/to/php/usr/bin/php /path/to/hellodolifolder/hellodoli/bin/console messenger:consume async -vv --time-limit 55 >/path/to/logs/hellodoli.log 2>&1

Cette commande permet de lancer un worker qui scrutera pendant 55 secondes l'arrivée de nouvelles notifications Hello Asso. Vous pouvez configurer un cron qui se lancera toutes les minutes pour être certain que les notifications soient prises en charge rapidement.
Sinon il est aussi possible de lancer la commande "à la main".



