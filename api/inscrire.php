
<?php

// Empêcher les sorties accidentelles (warnings, espaces des inclusions) de
// corrompre la réponse JSON attendue par le navigateur.
ob_start();

// Réponse au format JSON
header('Content-Type: application/json; charset=utf-8');

// Charger les bibliothèques et la connexion MySQL
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

use Cloudinary\Cloudinary;

try {

    // 1. Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception("Méthode non autorisée");
    }

    // 2. Récupérer les données du formulaire
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $ville = trim($_POST['ville'] ?? '');

    // Vérifier les champs
    if ($nom === '' || $prenom === '' || $ville === '') {
        http_response_code(400);
        throw new Exception("Tous les champs sont obligatoires");
    }

    // 3. Vérifier la photo
    if (
        !isset($_FILES['photo']) ||
        $_FILES['photo']['error'] !== UPLOAD_ERR_OK
    ) {
        http_response_code(400);
        throw new Exception("Veuillez sélectionner une photo valide");
    }

    $photo = $_FILES['photo'];

    // Limiter la taille à 5 Mo
    if ($photo['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        throw new Exception("La photo ne doit pas dépasser 5 Mo");
    }

    // Vérifier le type réel du fichier
    $mime = (new finfo(FILEINFO_MIME_TYPE))
        ->file($photo['tmp_name']);

    $typesAutorises = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    if (!in_array($mime, $typesAutorises, true)) {
        http_response_code(400);
        throw new Exception("Format JPG, PNG ou WebP uniquement");
    }

    // 4. Connexion à Cloudinary
    $cloudinaryUrl = getenv('CLOUDINARY_URL');

    if (!$cloudinaryUrl) {
        throw new Exception("Configuration Cloudinary manquante");
    }

    $cloudinary = new Cloudinary($cloudinaryUrl);

    // Envoyer la photo à Cloudinary
    $resultat = $cloudinary->uploadApi()->upload(
        $photo['tmp_name'],
        [
            'folder' => 'atelier6-stagiaires',
            'resource_type' => 'image'
        ]
    );

    // Récupérer l'URL sécurisée
    $photoUrl = $resultat['secure_url'];

    // 5. Connexion à MySQL Aiven
    $pdo = getDatabase();

    // Préparer la requête SQL
    $sql = "INSERT INTO stagiaires
            (nom, prenom, ville, photo)
            VALUES (?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);

    // Exécuter la requête
    $stmt->execute([
        $nom,
        $prenom,
        $ville,
        $photoUrl
    ]);

    // 6. Réponse de succès
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Stagiaire inscrit avec succès'
    ]);

} catch (Throwable $e) {

    if (http_response_code() === 200) {
        http_response_code(500);
    }

    error_log($e->getMessage());

    if (ob_get_level() > 0) {
        ob_clean();
    }

    echo json_encode([
        'success' => false,
        'message' => 'Inscription impossible. Vérifiez les données ou la configuration du serveur.'
    ]);
}
