<?php
class IdCardGenerator {
    private $template_path;
    private $output_path;
    private $image_quality = 100;
    private $font_path_bold = __DIR__ . '/fonts/arial-bold.ttf';
    private $font_path_regular = __DIR__ . '/fonts/arial.ttf';
    
    public function __construct($template_type) {
        $this->template_path = "templates/{$template_type}/";
        $this->output_path = "generated/";
    }
    
    public function generateCard($data) {
        // Load template images
        $front_template = imagecreatefromjpeg($this->template_path . "front.jpg");
        $back_template = imagecreatefromjpeg($this->template_path . "back.jpg");
        
        // Process front side
        $front_card = $this->processFrontSide($front_template, $data);
        
        // Process back side
        $back_card = $this->processBackSide($back_template, $data);
        
        // Generate filename using full name
        $sanitized_name = preg_replace('/[^A-Za-z0-9]/', '_', $data['full_name']);
        $filename = strtolower($sanitized_name) . '_' . date('Ymd_His');
        
        // Save generated cards
        imagejpeg($front_card, $this->output_path . $filename . "_front.jpg", $this->image_quality);
        imagejpeg($back_card, $this->output_path . $filename . "_back.jpg", $this->image_quality);
        
        // Clean up
        imagedestroy($front_card);
        imagedestroy($back_card);
        
        return $filename;
    }
    
    
    private function wrapText($image, $text, $font, $size, $color, $x, $y, $maxWidth, $angle = 0) {
        $currentSize = $size;
        
        do {
            $bbox = imagettfbbox($currentSize, $angle, $font, $text);
            
            // For vertical text (90 degrees), we need to measure height instead of width
            if ($angle == 90) {
                $textWidth = abs($bbox[3] - $bbox[1]); // Vertical height
            } else {
                $textWidth = abs($bbox[2] - $bbox[0]); // Horizontal width
            }
            
            if ($textWidth <= $maxWidth) {
                break;
            }
            
            $currentSize--;
        } while ($currentSize > 8);
        
        // Center the text
        if ($angle == 90) {
            $bbox = imagettfbbox($currentSize, $angle, $font, $text);
            $textHeight = abs($bbox[2] - $bbox[0]);
            $lineX = $x;
            $lineY = $y + ($maxWidth - $textHeight) / 2;
        } else {
            $lineX = $x + ($maxWidth - $textWidth) / 2;
            $lineY = $y;
        }
        
        imagettftext($image, $currentSize, $angle, $lineX, $lineY, $color, $font, $text);
    }
    
     
    
    private function processFrontSide($template, $data) {
        // Create circular profile photo
        $user_image = $this->createCircularImage($data['user_image'], 307.5);
        
        // Adjust position for profile photo (x, y coordinates)
        imagecopy($template, $user_image, 171.5, 410, 0, 0, imagesx($user_image), imagesy($user_image));

         // Create green color for name
        $green_color = imagecolorallocate($template, 11, 135, 9); // RGB for green
        
        // Create red color for department
        $red_color = imagecolorallocate($template, 255, 0, 0); // RGB for red
        
        // Add name text with bold font
        $text_color = imagecolorallocate($template, 0, 0, 0);
        $this->wrapText($template, $data['full_name'], $this->font_path_bold, 24, $green_color, 130, 900, 400);

        
        // Add department text if provided
        if (!empty($data['department'])) {
            $this->wrapText($template, $data['department'], $this->font_path_regular, 20, $red_color, 130, 940, 400);
        }
        
        return $template;
    }
    
    private function processBackSide($template, $data) {
        // Add ID number with custom positioning
        if (!empty($data['id_number'])) {
            $text_color = imagecolorallocate($template, 0, 0, 0);
            // imagettftext($template, 36, 90, 550, 350, $text_color, $this->font_path_bold, $data['id_number']);
            $this->wrapText($template, $data['id_number'], $this->font_path_bold, 60, $text_color, 550, 300, 150, 90);
        }
        
        // Add QR code if provided with specific positioning
        if (isset($data['qr_code'])) {
            $qr_image = $this->resizeImage($data['qr_code'], 350, 350);
            imagecopy($template, $qr_image, 100, 60, 0, 0, imagesx($qr_image), imagesy($qr_image));
        }
        
        return $template;
    }

    private function createCircularImage($image_path, $diameter) {
        // Create source image
        $source = imagecreatefromstring(file_get_contents($image_path));
        $width = imagesx($source);
        $height = imagesy($source);
        
        // Calculate crop dimensions
        $size = min($width, $height);
        $x = ($width - $size) / 2;
        $y = ($height - $size) / 2;
        
        // Create transparent output image
        $output = imagecreatetruecolor($diameter, $diameter);
        imagealphablending($output, false);
        imagesavealpha($output, true);
        
        // Fill with complete transparency
        $transparent = imagecolorallocatealpha($output, 0, 0, 0, 127);
        imagefilledrectangle($output, 0, 0, $diameter, $diameter, $transparent);
        
        // Enable alpha blending for proper transparency handling
        imagealphablending($output, true);
        
        // Create a circular mask
        $mask = imagecreatetruecolor($diameter, $diameter);
        $black = imagecolorallocate($mask, 0, 0, 0);
        $white = imagecolorallocate($mask, 255, 255, 255);
        
        // Fill mask with black (transparent) background
        imagefilledrectangle($mask, 0, 0, $diameter, $diameter, $black);
        
        // Draw white (visible) circle
        imagefilledellipse($mask, $diameter/2, $diameter/2, $diameter, $diameter, $white);
        
        // Copy and resize source image
        imagecopyresampled($output, $source, 0, 0, $x, $y, $diameter, $diameter, $size, $size);
        
        // Create final output with transparency
        $final = imagecreatetruecolor($diameter, $diameter);
        imagealphablending($final, false);
        imagesavealpha($final, true);
        imagefilledrectangle($final, 0, 0, $diameter, $diameter, $transparent);
        
        // Apply circular mask
        for($x = 0; $x < $diameter; $x++) {
            for($y = 0; $y < $diameter; $y++) {
                $maskColor = imagecolorat($mask, $x, $y);
                if($maskColor == $white) {
                    $color = imagecolorat($output, $x, $y);
                    imagesetpixel($final, $x, $y, $color);
                }
            }
        }
        
        // Cleanup
        imagedestroy($source);
        imagedestroy($mask);
        imagedestroy($output);
        
        return $final;
    }
    
    private function resizeImage($image_path, $width, $height) {
        $original = imagecreatefromstring(file_get_contents($image_path));
        $resized = imagecreatetruecolor($width, $height);
        
        imagecopyresampled($resized, $original, 0, 0, 0, 0, $width, $height, imagesx($original), imagesy($original));
        
        imagedestroy($original);
        
        return $resized;
    }
}
