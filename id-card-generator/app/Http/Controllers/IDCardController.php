<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\IDCardGeneratorService;

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
            'user_image' => 'required|image',
            'qr_code' => 'nullable|image'
        ]);

        $result = $this->cardGenerator->generateCard($data);

        return response()->json([
            'success' => true,
            'front_image' => $result['front_image'],
            'back_image' => $result['back_image']
        ]);
    }

    public function gallery()
    {
        $cards = $this->cardGenerator->getGeneratedCards();
        return view('gallery', compact('cards'));
    }

    public function destroy($id)
    {
        $this->cardGenerator->deleteCard($id);
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
        $vCard .= "END:VCARD";
    
        $qrCode = new QRCode;
        $qrPath = public_path('qrcodes/' . time() . '.png');
        $qrCode->render($vCard, $qrPath);
    
        return response()->json([
            'success' => true,
            'qr_url' => asset('qrcodes/' . basename($qrPath))
        ]);
    }
    
}
