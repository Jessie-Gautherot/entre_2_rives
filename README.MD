# Entre 2 Rives

## Présentation

Entre 2 Rives est une application Symfony réalisée pour le passage du titre professionnel Développeur Web et Web Mobile (DWWM).

Il s’agit d’une plateforme de location de bateaux électriques sans permis permettant aux utilisateurs de consulter les bateaux, rechercher une disponibilité, puis effectuer une réservation en ligne.

L’application utilise une base de données MySQL, des fixtures pour générer des données de démonstration ainsi qu’un paiement via Stripe en mode test.

## 1. Prérequis

Avant de commencer, assurez-vous d’avoir installé :

- PHP 8.2.12 ou supérieur
- Symfony Framework 7.4
- Symfony CLI (v5.16.1)
- Composer 2.9.5
- MySQL Community Server 8.0.46
- Stripe CLI
- Un navigateur web
- Un terminal compatible bash

## 2. Cloner le projet

```bash
git clone https://github.com/Jessie-Gautherot/entre_2_rives

cd entre_2_rives
```

## 3. Configuration des environnements

Le projet utilise deux environnements :

- un environnement de développement : `.env.local`
- un environnement de test : `.env.test`

Chaque environnement utilise sa propre base de données.

### 3.1 Environnement de développement

À partir du fichier `.env`, créer un fichier `.env.local`.

Adapter la valeur de `DATABASE_URL` selon votre configuration MySQL locale.

Configurer les variables nécessaires à l'envoi des e-mails :

- `MAILER_FROM` : adresse utilisée comme expéditeur des e-mails
- `CONTACT_EMAIL` : adresse qui reçoit les messages envoyés depuis le formulaire de contact
- `MAILER_DSN` : configuration du service d'envoi d'e-mails

Configurer vos propres clés Stripe en mode test :

- `STRIPE_SECRET_KEY`
- `STRIPE_PUBLISHABLE_KEY`
- `STRIPE_WEBHOOK_SECRET`

Le secret `STRIPE_WEBHOOK_SECRET` sera obtenu lors du lancement de Stripe CLI.

Les clés Stripe utilisées doivent être des clés de test.

### 3.2 Environnement de test

Le fichier `.env.test` contient une configuration dédiée aux tests.

Adapter la valeur de `DATABASE_URL` selon votre configuration MySQL locale.

L'environnement de test utilise une base de données dédiée.

Les services externes, notamment l'envoi d'e-mails et Stripe, y utilisent des valeurs de test et ne nécessitent pas de clés réelles.

La préparation de la base de données et l'exécution des tests sont détaillées dans la section **7. Lancer les tests**.

## 4. Installation du projet

### 4.1 Installer les dépendances Composer

```bash
composer install
```

### 4.2 Créer la base de données de développement

```bash
php bin/console doctrine:database:create
```

### 4.3 Exécuter les migrations

```bash
php bin/console doctrine:migrations:migrate
```

Répondre `yes` si Symfony demande une confirmation.

### 4.4 Charger les fixtures

```bash
php bin/console doctrine:fixtures:load
```

Répondre `yes` si Symfony demande une confirmation.

Les fixtures créent automatiquement les utilisateurs, les modèles de bateaux, les bateaux et les formules de location nécessaires au fonctionnement de l’application.

## 5. Lancer le projet

Démarrer le serveur Symfony :

```bash
symfony server:start
```

L'application est accessible à l'adresse :

http://127.0.0.1:8000


Pour arrêter le serveur :

```bash
symfony server:stop
```

## 6. Prise en main de l’application

### 6.1 Inscription et activation du compte

Créez un compte utilisateur via le formulaire d'inscription.

Après l'inscription, un e-mail d'activation est envoyé à l'adresse renseignée.

Le compte doit être activé à l'aide du lien contenu dans cet e-mail avant de pouvoir effectuer une réservation.

> **Attention :**
> L'application fonctionnant en environnement local, le lien d'activation doit être ouvert depuis l'ordinateur sur lequel le serveur Symfony est lancé.

### 6.2 Comptes de démonstration

Les comptes de démonstration sont créés automatiquement lors du chargement des fixtures.

#### Compte Administrateur

- Email : `admin@entre2rives.fr`
- Mot de passe : `Admin123!`

Ce compte permet d'accéder au back-office et à la gestion des réservations, des modèles de bateaux, des bateaux, des clients ainsi que des formules de location (créneaux horaires, durées et tarifs).

#### Compte Client

- Email : `alice@test.fr`
- Mot de passe : `Client123!`

Ce compte permet de tester le parcours de réservation et l’espace « Mon compte ».

### 6.3 Effectuer une réservation

Une réservation peut être initiée depuis le catalogue, la fiche d’un bateau ou directement depuis la page de réservation.

Selon le parcours choisi, certains critères peuvent être présélectionnés.

Pour effectuer une réservation :

1. Sélectionner une date et indiquer le nombre de passagers.
2. Choisir un modèle de bateau disponible.
3. Choisir une formule de location disponible. Une formule comprend un créneau horaire, une durée et un tarif.
4. Valider la réservation.
5. Procéder au paiement via Stripe en mode test.
6. Consulter la réservation depuis la page « Mon compte ».

Une réservation confirmée peut être annulée par le client jusqu’à 48 heures avant le départ.

### 6.4 Paiement Stripe en mode test

Les paiements sont réalisés avec Stripe en mode test.

Pour recevoir les événements Stripe en environnement local, ouvrir un second terminal PowerShell pendant que le serveur Symfony est en cours d'exécution.

Récupérer la clé secrète Stripe configurée dans `.env.local` :

```powershell
$key = (Get-Content .env.local | Select-String '^STRIPE_SECRET_KEY=').ToString().Split('=', 2)[1]
```

Puis démarrer l'écoute des événements Stripe avec Stripe CLI :

```powershell
stripe listen --api-key $key --forward-to http://127.0.0.1:8000/stripe/webhook
```

Stripe CLI affiche alors un secret de webhook commençant par `whsec_`.

Reporter cette valeur dans le fichier `.env.local` :

```env
STRIPE_WEBHOOK_SECRET=whsec_...
```

Conserver le terminal Stripe CLI ouvert pendant les tests de paiement.

### 6.5 Effectuer un paiement de test

Utiliser la carte de test Stripe suivante :

- Email : au choix
- Numéro : `4242 4242 4242 4242`
- Date d’expiration : n’importe quelle date future
- CVC : 3 chiffres au choix
- Nom du titulaire : au choix

Après validation du paiement, la réservation apparaît comme confirmée dans l’espace « Mon compte ».

## 7. Lancer les tests

### Environnement de test

Le fichier `.env.test` contient la configuration utilisée lors de l'exécution des tests automatisés.

Adapter la valeur de `DATABASE_URL` selon votre configuration MySQL locale.

L'environnement de test utilise une base de données dédiée.

L'envoi d'e-mails est désactivé pendant les tests et les variables Stripe utilisent des valeurs factices. Aucune clé Stripe réelle ni configuration de service de messagerie n'est nécessaire pour exécuter les tests.

### 7.1 Créer la base de données de test

```bash
php bin/console doctrine:database:create --env=test
```

### 7.2 Exécuter les migrations sur la base de test

```bash
php bin/console doctrine:migrations:migrate --env=test
```

Répondre `yes` si Symfony demande une confirmation.

### 7.3 Charger les fixtures dans la base de test

```bash
php bin/console doctrine:fixtures:load --env=test
```

Répondre `yes` si Symfony demande une confirmation.

### 7.4 Exécuter les tests PHPUnit

```bash
php bin/phpunit
```

## 8. Architecture du projet

L'application suit l'architecture MVC proposée par Symfony.

Les principales responsabilités sont réparties de la manière suivante :

- `src/Controller/` : gestion des requêtes HTTP et des réponses.
- `src/Entity/` : entités Doctrine représentant les données de l'application.
- `src/Repository/` : accès aux données et requêtes spécifiques.
- `src/Service/` : logique métier de l'application.
- `src/Form/` : formulaires Symfony.
- `templates/` : vues Twig.
- `public/css/` : feuilles de style.
- `public/js/` : scripts JavaScript.
- `tests/` : tests automatisés.

La logique métier principale est séparée des contrôleurs, notamment pour
la gestion des disponibilités, des réservations, des annulations et des
paiements Stripe.