<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\IDCardGeneratorService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class IDCardController extends Controller
{
    protected $cardGenerator;

    public function __construct(IDCardGeneratorService $cardGenerator)
    {
        $this->cardGenerator = $cardGenerator;
    }

    public function index()
    {
        return view('home');
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'template_type' => 'required',
            'full_name' => 'required',
            'id_number' => 'nullable',
            'department' => 'nullable',
            'region' => 'required',
            'user_image' => 'required|image',
            'qr_code' => 'nullable|image'
        ]);
    
        $result = $this->cardGenerator->generateCard($data);
    
        return response()->json([
            'success' => true,
            'front_image' => asset($result['front_image']),
            'back_image' => asset($result['back_image'])
        ]);
    } 

    public function gallery()
    {
        $cards = $this->cardGenerator->getGeneratedCards();
        $perPage = 12; // Number of cards per page
        $currentPage = request()->get('page', 1);
        $pagedData = new \Illuminate\Pagination\LengthAwarePaginator(
            array_slice($cards, ($currentPage - 1) * $perPage, $perPage),
            count($cards),
            $perPage,
            $currentPage,
            ['path' => request()->url()]
        );
        
        return view('gallery', ['cards' => $pagedData]);
    }    

    public function destroy($filename)
    {
        $this->cardGenerator->deleteCard($filename);
        return response()->json(['success' => true]);
    }   

    public function generateQR(Request $request)
    {
        $vCard = "BEGIN:VCARD\r\n";
        $vCard .= "VERSION:3.0\r\n";
        $vCard .= "N:{$request->lastName};{$request->firstName};;;\r\n";
        $vCard .= "FN:{$request->firstName} {$request->lastName}\r\n";
        $vCard .= "ORG:{$request->company}\r\n";
        $vCard .= "TITLE:{$request->jobTitle}\r\n";
        $vCard .= "TEL;TYPE=CELL:{$request->mobile}\r\n";
        $vCard .= "TEL;TYPE=WORK:{$request->phone}\r\n";
        $vCard .= "EMAIL:{$request->email}\r\n";
        $vCard .= "URL:{$request->website}\r\n";
        $vCard .= "ADR;TYPE=WORK:;;{$request->street};{$request->city};{$request->state};;{$request->country}\r\n";
        $vCard .= "BDAY:{$request->birthday}\r\n";
        $vCard .= "END:VCARD";
    
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_L,
            'scale' => 10,
        ]);
    
        $qrcode = new QRCode($options);
        $qrPath = 'qrcodes/' . time() . '.png';
        $qrcode->render($vCard, public_path($qrPath));
    
        return response()->json([
            'success' => true,
            'qr_url' => asset($qrPath)
        ]);
    }
    
    
}
