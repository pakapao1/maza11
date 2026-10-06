FROM php:8.2-apache

# Salin semua fail ke web root Apache
COPY . /var/www/html/

# Port lalai Apache
EXPOSE 80
