# Application de sondage - Laravel & Vue.js

Projet réalisé dans le cadre du module Web & Mobile UI à la HEIG-VD.

L’application permet à une personne authentifiée de créer et gérer des sondages, puis de partager un lien public permettant à d’autres utilisateurs authentifiés de voter et consulter les résultats.

---

## Fonctionnalités principales

* Création, modification et suppression de sondages
* Gestion des brouillons
* Lancement d’un sondage
* Choix simple ou multiple
* Résultats publics ou privés
* Durée de disponibilité d’un sondage
* Génération d’un lien de partage avec token unique
* Vote via lien public
* Mise à jour des résultats en temps réel via polling
* Aperçu graphique des résultats
* Interface responsive (mobile first)

---

## Technologies utilisées

### Backend

* Laravel 12
* API JSON versionnée
* Laravel Sanctum
* SQLite

### Frontend

* Vue.js 3
* Composition API
* Fetch API
* Vite

---

## Pré-requis

* PHP >= 8.2
* Composer
* Node.js et npm
* SQLite (ou autre base compatible Laravel)

---

## Installation du projet

### 1. Cloner le dépôt

```bash
git clone <url-du-repo>
```

### 2. Installer les dépendances

```bash
composer install
npm install
```

### 3. Configurer l’environnement

Copier le fichier `.env.example` :

```bash
cp .env.example .env
```

Puis générer la clé Laravel :

```bash
php artisan key:generate
```

### 4. Créer le lien de stockage

```bash
php artisan storage:link
```

### 5. Migrer la base de données

```bash
php artisan migrate
```

### 6. Lancer le projet

```bash
composer run dev
```

Le projet sera accessible à l’adresse :

```txt
http://127.0.0.1:8000
```

---

## Routes principales

### Dashboard

```txt
/polls/dashboard
```

### Création d’un sondage

```txt
/polls/dashboard/create
```

### Page publique d’un sondage

```txt
/polls/{token}
```

---

## Architecture générale

Le frontend Vue.js communique avec une API Laravel via des endpoints JSON.

L’application utilise :

* des composants Vue réutilisables ;
* un store centralisé pour la gestion des sondages ;
* du polling pour la mise à jour automatique des résultats ;
* des tokens uniques pour le partage public des sondages ;
* une séparation entre logique backend Laravel et interface frontend Vue.js.

---

## Remarques

* Un sondage lancé ne peut plus être modifié.
* Les brouillons ne peuvent pas être votés.
* Les résultats privés sont uniquement visibles par le propriétaire du sondage.
* Les votes sont limités à un vote par utilisateur.
* Les résultats sont mis à jour automatiquement sans rechargement de page grâce au polling.