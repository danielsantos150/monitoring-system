FROM php:8.2-apache

# Atualiza os pacotes e instala o OpenSSH Client
RUN apt-get update && apt-get install -y openssh-client

# Instala extensões necessárias do PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli

# Habilita mod_rewrite (caso esteja usando Apache)
RUN a2enmod rewrite

# Define o diretório de trabalho
WORKDIR /var/www/html

# Copia os arquivos do projeto para o diretório do container
COPY . /var/www/html
