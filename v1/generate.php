<?php
require_once 'IdCardGenerator.php';

// Set up error logging
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);
ini_set('error_log', 'id_card_errors.log');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        if (empty($_POST['full_name'])) {
            throw new Exception('Full name is required');
        }

        if (!isset($_FILES['user_image']) || $_FILES['user_image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Valid profile image is required');
        }

        // Validate image type
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($_FILES['user_image']['type'], $allowed_types)) {
            throw new Exception('Invalid image format. Please use JPG or PNG');
        }

        // Log successful upload
        error_log("Processing ID card for: " . $_POST['full_name']);

        $user_image = $_FILES['user_image']['tmp_name'];
        $qr_code = isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] === UPLOAD_ERR_OK ? 
                   $_FILES['qr_code']['tmp_name'] : null;
        
        $data = [
            'full_name' => $_POST['full_name'],
            'id_number' => $_POST['id_number'] ?? '',
            'department' => $_POST['department'] ?? '',
            'user_image' => $user_image,
            'qr_code' => $qr_code
        ];
        
        $generator = new IdCardGenerator($_POST['template_type']);
        $filename = $generator->generateCard($data);
        
        // Log successful generation
        error_log("ID card generated successfully: {$filename}");

        echo json_encode([
            'success' => true,
            'front_image' => "generated/{$filename}_front.jpg",
            'back_image' => "generated/{$filename}_back.jpg"
        ]);
    } catch (Exception $e) {
        // Log the error
        error_log("Error generating ID card: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
}
