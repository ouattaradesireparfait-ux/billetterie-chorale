# Billetterie de la chorale

Application Laravel autonome pour les billets du concert. Billets Standard à 2 000 FCFA et VIP à 5 000 FCFA. L’application enregistre les commandes publiques et ventes sur place, confirme manuellement le paiement, émet des billets avec numéro et jeton QR uniques, envoie les billets par e-mail, permet l’impression et contrôle chaque entrée une seule fois.

Le paiement en ligne automatique n’est pas encore intégré : le prestataire reste à choisir.

Format des billets : VIP-A0A1-001 ou STD-B2C3-002. Le bloc alphanumerique est aleatoire et la numerotation va de 001 a 999 separement pour chaque categorie.

## Installation

Prérequis : PHP 8.2+, Composer, MySQL et les extensions Laravel usuelles (PDO MySQL, OpenSSL, Mbstring, Fileinfo, XML). Copier .env.example vers .env, renseigner la base, SMTP, les détails du concert, ADMIN_EMAIL et un ADMIN_PASSWORD fort. Puis lancer :

composer install
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache

## cPanel

Créer une base MySQL et son utilisateur. Placer le code hors de public_html et pointer le sous-domaine vers le dossier public. Ne pas exposer .env, vendor ou storage. Activer HTTPS (requis pour l’accès à la caméra) et vérifier les droits d’écriture de storage et bootstrap/cache. Configurer SMTP pour les e-mails. Le QR et le scanner utilisent des bibliothèques depuis des CDN; prévoir Internet sur le téléphone. Une saisie manuelle du jeton reste disponible.

Configurer EVENT_NAME, EVENT_SUBTITLE, EVENT_DATE, EVENT_TIME, EVENT_VENUE et PAYMENT_INSTRUCTIONS dans .env. Si cPanel impose public_html, adapter public/index.php avec les chemins réels indiqués par l’hébergeur.
