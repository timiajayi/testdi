<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M');

// Handle photo upload
$photoData = $_POST['image'];
$qrData = $_POST['qrimage'];
$employeeName = $_POST['employeeName'];
$employeeId = $_POST['employeeId'];

// Process photo
list($type, $photoData) = explode(';', $photoData);
list(, $photoData) = explode(',', $photoData);
$photoData = base64_decode($photoData);
$photoName = 'photo_' . time() . '.png';

// Create directory if it doesn't exist
$uploadDir = 'NYM_images';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

file_put_contents($uploadDir . DIRECTORY_SEPARATOR . $photoName, $photoData);

// Process QR code
list($type, $qrData) = explode(';', $qrData);
list(, $qrData) = explode(',', $qrData);
$qrData = base64_decode($qrData);
$qrName = 'qr_' . time() . '.png';
file_put_contents($uploadDir . DIRECTORY_SEPARATOR . $qrName, $qrData);

function createImageInstantly($photoName, $qrName, $employeeName, $employeeId)
{
    $x = 900;
    $y = 1050;
    $targetFolder = 'NYM_images';
    $templatesFolder = 'templates';
    
    // Use directory separator for cross-platform compatibility
    $targetPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . $targetFolder . DIRECTORY_SEPARATOR;
    $templatePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . $templatesFolder . DIRECTORY_SEPARATOR;

    // Front side
    $outputImage = imagecreatetruecolor($x, $y);
    imagealphablending($outputImage, true);
    imagesavealpha($outputImage, true);
    
    $frontTemplate = imagecreatefrompng($templatePath . 'front_template.png');
    $photo = imagecreatefrompng($targetPath . $photoName);

    // Photo placement coordinates
    $photoX = 250;
    $photoY = 338;
    $photoWidth = 510;
    $photoHeight = 510;

    // Copy and resize images
    imagecopyresampled($outputImage, $frontTemplate, 0, 0, 0, 0, $x, $y, $x, $y);
    imagecopyresampled($outputImage, $photo, $photoX, $photoY, 0, 0, $photoWidth, $photoHeight, imagesx($photo), imagesy($photo));

    // Add name text
    $white = imagecolorallocate($outputImage, 255, 255, 255);
    $black = imagecolorallocate($outputImage, 0, 0, 0);
    
    // Text positioning
    $fontSize = 32;
    $textX = 450;
    $textY = 900;
    
    // Add text shadow for better visibility
    imagettftext($outputImage, $fontSize, 0, $textX+1, $textY+1, $black, __DIR__ . '/arial.ttf', $employeeName);
    imagettftext($outputImage, $fontSize, 0, $textX, $textY, $white, __DIR__ . '/arial.ttf', $employeeName);

    $frontImage = 'front_' . time() . '.png';
    imagepng($outputImage, $targetPath . $frontImage);

    // Back side
    $backOutput = imagecreatetruecolor($x, $y);
    imagealphablending($backOutput, true);
    imagesavealpha($backOutput, true);
    
    $backTemplate = imagecreatefrompng($templatePath . 'back_template.png');
    $qrCode = imagecreatefrompng($targetPath . $qrName);

    imagecopyresampled($backOutput, $backTemplate, 0, 0, 0, 0, $x, $y, $x, $y);
    
    // QR code placement
    $qrX = 350;
    $qrY = 400;
    $qrSize = 200;
    
    imagecopyresampled($backOutput, $qrCode, $qrX, $qrY, 0, 0, $qrSize, $qrSize, imagesx($qrCode), imagesy($qrCode));
    
    // Add employee ID vertically
    imagettftext($backOutput, 24, 90, 800, 500, $white, __DIR__ . '/arial.ttf', $employeeId);

    $backImage = 'back_' . time() . '.png';
    imagepng($backOutput, $targetPath . $backImage);

    // Cleanup
    imagedestroy($outputImage);
    imagedestroy($backOutput);
    imagedestroy($frontTemplate);
    imagedestroy($backTemplate);
    imagedestroy($photo);
    imagedestroy($qrCode);
    
    unlink($targetPath . $photoName);
    unlink($targetPath . $qrName);

    return [
        'front' => $targetFolder . '/' . $frontImage,
        'back' => $targetFolder . '/' . $backImage
    ];
}

$result = createImageInstantly($photoName, $qrName, $employeeName, $employeeId);

header('Content-type: application/json');
echo json_encode($result);
exit;
