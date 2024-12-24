#!/bin/bash

# Obtenez le chemin absolu du répertoire contenant le script
script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Créer la base de données à partir du fichier SQL
mysql < "$script_dir/bddexe.sql"

# Changer de répertoire
cd "$script_dir"

# Démarrer le serveur PHP
php -S localhost:8888 &

# Attendre quelques secondes pour que le serveur se lance complètement
sleep 2

# Ouvrir Google Chrome avec l'URL de l'application
google-chrome http://localhost:8888/