# Mise en ligne sur o2switch (hébergement mutualisé)

Guide pas à pas pour héberger **Pétanque Manager** sur o2switch, avec le **live**
qui fonctionne (via Pusher, gratuit) et un upload par **FileZilla (SFTP)**.

> Le temps réel n'utilise **pas** Reverb en production (impossible en mutualisé :
> pas de processus permanent). On le remplace par **Pusher**, un service WebSocket
> hébergé avec une offre gratuite. C'est déjà branché dans le code.

---

## 0. Comptes à créer (une fois)

1. **o2switch** — l'hébergement (~7 €/mois, offre unique illimitée).
2. **Pusher** — https://pusher.com → offre **gratuite** (Sandbox : 200 000 messages/jour, 100 connexions simultanées). Largement suffisant pour des concours.
3. Un **nom de domaine** (souvent inclus/gratuit la 1re année chez o2switch).

---

## 1. Créer l'app Pusher (le WebSocket du live)

1. Dans Pusher → **Channels** → *Create app*.
2. Cluster : choisis **eu** (Europe).
3. Note les **4 valeurs** (onglet *App Keys*) : `app_id`, `key`, `secret`, `cluster`.

Ce sont elles qui iront dans le `.env` de prod (`PUSHER_*`).

---

## 2. Préparer o2switch (cPanel)

1. **Version PHP** : *Sélecteur de version PHP* → choisir **PHP 8.3**. Activer les
   extensions habituelles (mbstring, pdo_mysql, openssl, ctype, curl, fileinfo, bcmath).
2. **Base de données** : *MySQL® Databases* → créer une base + un utilisateur, lui
   donner **tous les privilèges**. Note `base`, `utilisateur`, `mot de passe`.
3. **SSH** : activer/récupérer l'accès SSH (*Accès SSH* dans cPanel). On en a besoin
   une seule fois pour l'installation.
4. **Domaine** : rattacher ton domaine. **Important** : la racine web (*document root*)
   doit pointer vers le dossier **`public/`** de l'app (voir étape 4).

---

## 3. Construire le front en local

Sur ton poste, dans le projet :

```bash
npm ci
npm run build          # génère public/build (les assets compilés)
```

> Le build a besoin des clés Pusher pour le navigateur. Le plus simple : mets
> `VITE_PUSHER_APP_KEY` et `VITE_PUSHER_APP_CLUSTER` dans un `.env` local **avant**
> le `npm run build` (mêmes valeurs que Pusher). Sinon le live ne se connectera pas.

---

## 4. Envoyer les fichiers (FileZilla / SFTP)

1. Connecte-toi à o2switch en **SFTP** (hôte, identifiant, mot de passe SSH, port 22).
2. Place le projet dans un dossier, par ex. `~/petanque` (PAS directement dans `public_html`).
3. **À envoyer** : tout le projet **SAUF** `node_modules/`, `vendor/`, `.git/`, `.env`.
   (On installera `vendor/` sur le serveur ; `public/build` compilé, lui, doit bien être envoyé.)
4. **Racine du domaine** : fais pointer le *document root* de ton domaine vers
   `~/petanque/public`. Sur o2switch, ça se règle en rattachant le domaine à ce
   sous-dossier `public` (support o2switch très réactif si besoin).

---

## 5. Installation (SSH, une seule fois)

Connecte-toi en SSH, puis dans le dossier de l'app :

```bash
cd ~/petanque

# Dépendances PHP (sans les paquets de dev, autoloader optimisé)
composer install --no-dev --optimize-autoloader

# Fichier d'environnement de prod
cp .env.production.example .env
nano .env        # remplir : APP_URL, DB_*, PUSHER_*, VITE_PUSHER_*  (voir le fichier)

php artisan key:generate            # génère APP_KEY
php artisan migrate --force         # crée les tables
php artisan storage:link

# Caches de prod (perfs)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Droits d'écriture (si besoin) :

```bash
chmod -R ug+rw storage bootstrap/cache
```

Ouvre `https://ton-domaine.fr` → l'app doit s'afficher, et le **live** doit
fonctionner (ouvre le pilotage d'un concours sur deux onglets pour tester).

---

## 6. Mettre à jour la prod (redéploiement)

À chaque nouvelle version :

```bash
# En local
npm run build                       # si le front a changé
# → envoyer par SFTP les fichiers modifiés (dont public/build)

# En SSH sur le serveur
cd ~/petanque
composer install --no-dev --optimize-autoloader   # si des paquets PHP ont changé
php artisan migrate --force                        # si nouvelles migrations
php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
```

> **Auto-deploy sur `git push main`** : possible via un GitHub Action qui se
> connecte en SSH à o2switch et rejoue ces commandes. À mettre en place dans un
> second temps si tu le souhaites.

---

## Notes

- **Files d'attente** : `QUEUE_CONNECTION=sync` (dans le `.env` de prod) → les
  diffusions temps réel partent pendant la requête, **aucun worker à faire tourner**.
- **E-mails** : `MAIL_MAILER=log` par défaut (aucun envoi). Pour de vrais e-mails
  (réinitialisation de mot de passe), configurer un SMTP (o2switch en fournit un).
- **Sauvegardes** : pense à activer les sauvegardes automatiques de la base MySQL
  côté o2switch.
