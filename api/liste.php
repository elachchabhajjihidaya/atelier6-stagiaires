<?php

// Retourner les données au format JSON
header('Content-Type: application/json; charset=utf-8');

// Importer la connexion MySQL
require_once __DIR__ . '/../config/db.php';

try {
    // Se connecter à la base de données
    $pdo = getDatabase();

    // Récupérer tous les stagiaires
    $sql = "SELECT * FROM stagiaires ORDER BY id DESC";

    // Exécuter la requête
    $stmt = $pdo->query($sql);

    // Récupérer les résultats
    $stagiaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Retourner les données en JSON
    echo json_encode([
        'success' => true,
        'stagiaires' => $stagiaires
    ]);

} catch (Throwable $e) {

    http_response_code(500);
    error_log($e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du chargement des stagiaires'
    ]);
}
