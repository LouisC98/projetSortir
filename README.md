# README

## Présentation du projet

Ce site est une application web développée avec Symfony. Il permet aux utilisateurs de s'inscrire à des sorties, de gérer des groupes, de commenter, de noter et d'organiser des événements. Les principales fonctionnalités incluent :
- Inscription et gestion des utilisateurs
- Création et gestion de sorties (événements)
- Système de commentaires et de notation
- Gestion des groupes d'utilisateurs
- Export de données
- Système de messagerie interne

## Prérequis
- PHP >= 8.1
- Composer
- Node.js & npm
- Une base de données compatible (ex : SQLite, MySQL)

## Installation

1. **Cloner le dépôt**
   ```cmd
   git clone <url-du-repo>
   cd projetSortir
   ```

2. **Installer les dépendances PHP**
   ```cmd
   composer install
   ```

3. **Installer les dépendances JavaScript**
   ```cmd
   npm install
   ```

4. **Configurer la base de données**
   - Copier le fichier `.env` ou `.env.local` et renseigner les paramètres de connexion à la base de données.

5. **Créer la base de données et exécuter les migrations**
   ```cmd
   php bin\console doctrine:database:create
   php bin\console doctrine:migrations:migrate
   ```

6. **(Optionnel) Charger les fixtures pour des données de test**
   ```cmd
   php bin\console doctrine:fixtures:load
   ```

7. **Compiler les assets**
   ```cmd
   npm run build
   ```

## Lancement du serveur

1. **Démarrer le serveur Symfony**
   ```cmd
   php bin\console server:run
   ```
   ou
   ```cmd
   symfony serve
   ```

2. **Accéder au site**
   Ouvrez votre navigateur à l'adresse indiquée (par défaut : http://localhost:8000).

## Fonctionnalités principales

### Gestion des sorties
- Créez des événements (sorties) avec une date, un lieu, une description et un organisateur.
- Modifiez ou annulez vos propres sorties.
- Inscrivez-vous ou désinscrivez-vous aux sorties proposées par d'autres membres.
- Visualisez la liste des sorties à venir ou passées.
- Filtrez les sorties par groupe, date ou organisateur.

### Groupes
- Créez des groupes d'utilisateurs pour organiser des sorties privées ou thématiques.
- Gérez les membres d'un groupe (ajout, suppression, invitation).
- Consultez les sorties réservées à un groupe spécifique.

### Commentaires et notes
- Commentez chaque sortie pour partager votre avis ou poser des questions.
- Notez les sorties auxquelles vous avez participé.
- Consultez la moyenne des notes et les retours des autres participants.

### Export
- Exportez la liste des utilisateurs ou des sorties au format CSV pour une utilisation externe.
- Téléchargez les exports directement depuis l'interface d'administration.

### Messagerie
- Utilisez le chat interne pour échanger avec les autres membres du site.
- Envoyez des messages privés ou discutez en groupe.
- Recevez des notifications en cas de nouveau message ou d'invitation à une sortie.

### Gestion du profil
- Modifiez vos informations personnelles (nom, email, mot de passe, photo).
- Consultez l'historique de vos inscriptions et de vos sorties organisées.
- Gérez vos préférences de notification et de confidentialité.

## Tests
Pour lancer les tests :
```cmd
php bin\phpunit
```

## Support
Pour toute question ou problème, veuillez contacter l'administrateur du projet.
