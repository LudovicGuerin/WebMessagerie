# Utiliser une image de base PHP avec Apache
FROM php:8.0-apache

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql

# Copier le code de l'application dans le conteneur
COPY . /var/www/html/

# Exposer le port 80
EXPOSE 80