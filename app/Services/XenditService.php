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

    public function createInvoice($externalId, $amount, $payerEmail, $description, $items = [])
    {
        // Xendit butuh Auth Basic (Username=SecretKey, Password=Kosong)
        $response = Http::withBasicAuth($this->secretKey, '')
            ->post('https://api.xendit.co/v2/invoices', [
                'external_id' => $externalId,
                'amount' => (int) $amount,
                'payer_email' => $payerEmail,
                'description' => $description,
                'invoice_duration' => 86400,
                'currency' => 'IDR',
                'items' => $items 
            ]);

        if ($response->failed()) {
            throw new \Exception('Xendit Error: ' . $response->body());
        }

        return $response->json();
    }

    public function getInvoice($invoiceId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->secretKey . ':')
            ])->get('https://api.xendit.co/v2/invoices/' . $invoiceId);

            return $response->json();
        } catch (\Exception $e) {
            throw new \Exception("Gagal cek status Xendit: " . $e->getMessage());
        }
    }
}