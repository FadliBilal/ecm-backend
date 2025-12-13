<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RajaOngkirService;

class RajaOngkirController extends Controller
{
    protected $rajaOngkir;

    public function __construct(RajaOngkirService $rajaOngkir)
    {
        $this->rajaOngkir = $rajaOngkir;
    }

    /**
     * @OA\Get(
     * path="/locations",
     * tags={"RajaOngkir"},
     * summary="Cari Lokasi (Autocomplete)",
     * description="Endpoint untuk fitur pencarian alamat di Frontend. Ketik nama kecamatan/kota, sistem akan mengembalikan list lokasi beserta ID-nya.",
     * @OA\Parameter(
     * name="search",
     * in="query",
     * required=true,
     * description="Minimal 3 karakter. Contoh: 'Gubeng'",
     * @OA\Schema(type="string", example="Gubeng")
     * ),
     * @OA\Response(
     * response=200,
     * description="Berhasil mengambil data lokasi",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(
     * @OA\Property(property="id", type="integer", example=69335, description="SIMPAN ID INI untuk Register"),
     * @OA\Property(property="label", type="string", example="TAMBAKSARI, SURABAYA, JAWA TIMUR..."),
     * @OA\Property(property="city_name", type="string", example="SURABAYA")
     * )
     * )
     * ),
     * @OA\Response(response=422, description="Validasi Gagal (Search kurang dari 3 huruf)")
     * )
     */
    public function searchLocation(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:3'
        ]);

        try {
            $data = $this->rajaOngkir->searchLocation($request->search);
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil data lokasi'], 500);
        }
    }

    /**
     * @OA\Post(
     * path="/check-ongkir",
     * tags={"RajaOngkir"},
     * summary="Cek Biaya Ongkir (Untuk Menu Pilihan Kurir)",
     * description="Frontend mengirim Origin (Lokasi Seller), Destination (Lokasi Buyer), Berat, dan Kurir. API akan mengembalikan daftar layanan (REG, BEST, YES) beserta harganya.",
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="origin", type="integer", example=444, description="ID Lokasi Pengirim (Seller)"),
     * @OA\Property(property="destination", type="integer", example=69335, description="ID Lokasi Penerima (User yang sedang login)"),
     * @OA\Property(property="weight", type="integer", example=1000, description="Berat dalam Gram"),
     * @OA\Property(property="courier", type="string", example="jne", description="Kode kurir: jne, sicepat, pos, jnt")
     * )
     * ),
     * @OA\Response(response=200, description="List opsi pengiriman berhasil didapat")
     * )
     */
    public function checkOngkir(Request $request)
    {
        // VALIDASI LEBIH KETAT
        $request->validate([
            'origin' => 'required|integer',      
            'destination' => 'required|integer', 
            'weight' => 'required|integer|min:1',      
            'courier' => 'required|string',
        ]);

        try {
            $costs = $this->rajaOngkir->getCost(
                $request->origin,
                $request->destination,
                $request->weight,
                $request->courier
            );
            return response()->json($costs);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}