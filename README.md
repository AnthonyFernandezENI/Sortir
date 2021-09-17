Après hébergement du site:

Création de la base de données "sortirdb" (passer en root dans le fichier .env)
Création d'un utilisateur avec les droits uniquement sur cette base de données avec les identifiants suivant : 
	-id : TCE 
	-mdp : pr0j€tRGAFASALL

Changer les identifiants dans le fichier .env
Lancer la commande composer install
Lancer la commande php bin/console doctrine:migrations:migrate

Enfin, lancer le serveur avec la commande : php bin/console server:run
