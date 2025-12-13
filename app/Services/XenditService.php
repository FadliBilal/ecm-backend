<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class XenditService
{
    protected $secretKey;

    public function __construct()
    {
        $this->secretKey = env('XENDIT_SECRET_KEY');
    }

    public function createInvoice($externalId, $amount, $payerEmail, $description)
    {
        // Xendit butuh Auth Basic (Username=SecretKey, Password=Kosong)
        $response = Http::withBasicAuth($this->secretKey, '')
            ->post('https://api.xendit.co/v2/invoices', [
                'external_id' => $externalId,
                'amount' => (int) $amount, // Pastikan integer
                'payer_email' => $payerEmail,
                'description' => $description,
                'invoice_duration' => 86400, // 24 jam
                'currency' => 'IDR'
            ]);

        if ($response->failed()) {
            throw new \Exception('Xendit Error: ' . $response->body());
        }

        return $response->json();
    }
}