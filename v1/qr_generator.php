<?php
require_once 'vendor/autoload.php';
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'];
    $filename = preg_replace('/[^A-Za-z0-9_-]/', '_', $_POST['filename']);
    
    $path = 'qrcodes/';
    if (!file_exists($path)) {
        mkdir($path);
    }
    
    try {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_L,
            'scale' => 10,
            'imageBase64' => false,
            'imageTransparent' => false,
        ]);
        
        $qrcode = new QRCode($options);
        $file = $path . $filename . '.png';
        
        // Generate QR code as PNG image
        $image = $qrcode->render($content);
        file_put_contents($file, $image);
        
        echo json_encode([
            'success' => true,
            'qr_file' => $file
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}
?>
