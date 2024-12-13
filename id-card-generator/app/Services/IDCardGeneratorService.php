<?php

namespace App\Services;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class IDCardGeneratorService
{
    private $template_path;
    private $output_path;
    private $image_quality = 100;
    private $font_path_bold;
    private $font_path_regular;
    
    public function __construct()
    {
        $this->font_path_bold = public_path('fonts/arial-bold.ttf');
        $this->font_path_regular = public_path('fonts/arial.ttf');
    }
    
    public function generateCard($data)
    {
        $this->template_path = "templates/{$data['template_type']}/";
        $this->output_path = "generated/";
        
        $front_template = imagecreatefromjpeg(public_path($this->template_path . "front.jpg"));
        $back_template = imagecreatefromjpeg(public_path($this->template_path . "back.jpg"));
        
        $front_card = $this->processFrontSide($front_template, $data);
        $back_card = $this->processBackSide($back_template, $data);
        
        $sanitized_name = preg_replace('/[^A-Za-z0-9]/', '_', $data['full_name']);
        $filename = strtolower($sanitized_name) . '_' . date('Ymd_His');
        
        $front_path = public_path($this->output_path . $filename . "_front.jpg");
        $back_path = public_path($this->output_path . $filename . "_back.jpg");
        
        imagejpeg($front_card, $front_path, $this->image_quality);
        imagejpeg($back_card, $back_path, $this->image_quality);
        
        imagedestroy($front_card);
        imagedestroy($back_card);
        
        return [
            'front_image' => $this->output_path . $filename . "_front.jpg",
            'back_image' => $this->output_path . $filename . "_back.jpg"
        ];
    }

    private function wrapText($image, $text, $font, $size, $color, $x, $y, $maxWidth, $angle = 0) 
    {
        $currentSize = $size;
        
        do {
            $bbox = imagettfbbox($currentSize, $angle, $font, $text);
            
            if ($angle == 90) {
                $textWidth = abs($bbox[3] - $bbox[1]);
            } else {
                $textWidth = abs($bbox[2] - $bbox[0]);
            }
            
            if ($textWidth <= $maxWidth) {
                break;
            }
            
            $currentSize--;
        } while ($currentSize > 8);
        
        if ($angle == 90) {
            $bbox = imagettfbbox($currentSize, $angle, $font, $text);
            $textHeight = abs($bbox[2] - $bbox[0]);
            $lineX = $x;
            $lineY = round($y + ($maxWidth - $textHeight) / 2);
        } else {
            $lineX = round($x + ($maxWidth - $textWidth) / 2);
            $lineY = $y;
        }
        
        imagettftext($image, $currentSize, $angle, $lineX, $lineY, $color, $font, $text);
    }
    
    private function processFrontSide($template, $data) 
    {
        $user_image = $this->createCircularImage($data['user_image'], 307);
        imagecopy($template, $user_image, 171, 410, 0, 0, imagesx($user_image), imagesy($user_image));

        $green_color = imagecolorallocate($template, 11, 135, 9);
        $red_color = imagecolorallocate($template, 255, 0, 0);
        
        $this->wrapText($template, $data['full_name'], $this->font_path_bold, 24, $green_color, 130, 900, 400);
        
        if (!empty($data['department'])) {
            $this->wrapText($template, $data['department'], $this->font_path_regular, 20, $red_color, 130, 940, 400);
        }
        
        return $template;
    }
    
    private function processBackSide($template, $data)
    {
        if (!empty($data['id_number'])) {
            $text_color = imagecolorallocate($template, 0, 0, 0);
            $this->wrapText($template, $data['id_number'], $this->font_path_bold, 60, $text_color, 550, 300, 150, 90);
        }
    
        // Handle QR code from uploaded file
        if (isset($data['qr_code'])) {
            $qr_image = $this->resizeImage($data['qr_code'], 350, 350);
            imagecopy($template, $qr_image, 100, 60, 0, 0, imagesx($qr_image), imagesy($qr_image));
        }
        // Generate QR code from vCard data
        elseif (!empty($data['qr_content'])) {
            $options = new QROptions([
                'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                'eccLevel' => QRCode::ECC_L,
                'scale' => 10,
                'imageBase64' => false,
            ]);
    
            $qrcode = new QRCode($options);
            $tempFile = tempnam(sys_get_temp_dir(), 'qr_');
            $qrcode->render($data['qr_content'], $tempFile);
    
            $qr_image = $this->resizeImage($tempFile, 350, 350);
            imagecopy($template, $qr_image, 100, 60, 0, 0, imagesx($qr_image), imagesy($qr_image));
            unlink($tempFile);
        }
    
        return $template;
    }
    

    private function createCircularImage($image_path, $diameter) 
    {
        $diameter = (int)$diameter;
        $source = imagecreatefromstring(file_get_contents($image_path));
        $width = imagesx($source);
        $height = imagesy($source);
        
        $size = min($width, $height);
        $x = (int)(($width - $size) / 2);
        $y = (int)(($height - $size) / 2);
        
        $output = imagecreatetruecolor($diameter, $diameter);
        imagealphablending($output, false);
        imagesavealpha($output, true);
        
        $transparent = imagecolorallocatealpha($output, 0, 0, 0, 127);
        imagefilledrectangle($output, 0, 0, $diameter-1, $diameter-1, $transparent);
        
        imagealphablending($output, true);
        
        $mask = imagecreatetruecolor($diameter, $diameter);
        $black = imagecolorallocate($mask, 0, 0, 0);
        $white = imagecolorallocate($mask, 255, 255, 255);
        
        imagefilledrectangle($mask, 0, 0, $diameter-1, $diameter-1, $black);
        imagefilledellipse($mask, $diameter/2, $diameter/2, $diameter-1, $diameter-1, $white);
        
        imagecopyresampled($output, $source, 0, 0, $x, $y, $diameter-1, $diameter-1, $size, $size);
        
        $final = imagecreatetruecolor($diameter, $diameter);
        imagealphablending($final, false);
        imagesavealpha($final, true);
        imagefilledrectangle($final, 0, 0, $diameter-1, $diameter-1, $transparent);
        
        for($x = 0; $x < $diameter-1; $x++) {
            for($y = 0; $y < $diameter-1; $y++) {
                $maskColor = imagecolorat($mask, $x, $y);
                if($maskColor == $white) {
                    $color = imagecolorat($output, $x, $y);
                    imagesetpixel($final, $x, $y, $color);
                }
            }
        }
        
        imagedestroy($source);
        imagedestroy($mask);
        imagedestroy($output);
        
        return $final;
    }
    
    private function resizeImage($image_path, $width, $height) 
    {
        $original = imagecreatefromstring(file_get_contents($image_path));
        $resized = imagecreatetruecolor($width, $height);
        
        imagecopyresampled($resized, $original, 0, 0, 0, 0, $width, $height, imagesx($original), imagesy($original));
        
        imagedestroy($original);
        
        return $resized;
    }

    public function getGeneratedCards()
    {
        $cards = [];
        $files = glob(public_path('generated/*_front.jpg'));
        
        foreach ($files as $file) {
            $filename = basename($file);
            $back_image = str_replace('_front.jpg', '_back.jpg', $filename);
            
            preg_match('/(\d{8}_\d{6})/', $filename, $matches);
            $date = isset($matches[1]) ? date('Y-m-d H:i:s', strtotime(str_replace('_', ' ', $matches[1]))) : '';
            
            $parts = explode('_', $filename);
            $name = ucwords(str_replace('_', ' ', $parts[0]));
            
            $cards[] = [
                'name' => $name,
                'date' => $date,
                'front_image' => 'generated/' . $filename,
                'back_image' => 'generated/' . $back_image
            ];
        }
        
        usort($cards, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return $cards;
    }

    public function deleteCard($filename)
    {
        $front_path = public_path($filename);
        $back_path = public_path(str_replace('_front.jpg', '_back.jpg', $filename));
        
        if (file_exists($front_path)) {
            unlink($front_path);
        }
        if (file_exists($back_path)) {
            unlink($back_path);
        }
    }

    private function generateQRCode($content, $size = 350)
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_L,
            'scale' => 10,
            'imageBase64' => false,
        ]);
    
        $qrcode = new QRCode($options);
        $tempFile = tempnam(sys_get_temp_dir(), 'qr_');
        $qrcode->render($content, $tempFile);
    
        return $tempFile;
    }
}
