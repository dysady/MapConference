<?php


$host = 'localhost;port=5433'; 
$dbname = 'postgres'; 
$user = 'postgres';
$password = 'Angely29*';

try {
    // Créer une instance de la classe PDO
    $db = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);

    // Définir le mode d'erreur de PDO sur Exception
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // En cas d'erreur de connexion
    echo "Erreur de connexion t: " . $e->getMessage();
}
?>