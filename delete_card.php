<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filename'])) {
    $front_image = $_POST['filename'];
    $back_image = str_replace('_front.jpg', '_back.jpg', $front_image);
    
    if (file_exists($front_image)) {
        unlink($front_image);
    }
    if (file_exists($back_image)) {
        unlink($back_image);
    }
    
    echo json_encode(['success' => true]);
}
