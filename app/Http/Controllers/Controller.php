<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 * version="1.0.0",
 * title="Dokumentasi API E-Commerce UAS",
 * description="Dokumentasi lengkap API untuk integrasi Frontend Mobile (Flutter).",
 * @OA\Contact(
 * email="hifadlibilal@sistekin.com"
 * )
 * )
 *
 * @OA\Server(
 * url=L5_SWAGGER_CONST_HOST,
 * description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 * type="http",
 * description="Masukkan Token Bearer di sini untuk mengakses endpoint yang dilindungi.",
 * name="Authorization",
 * in="header",
 * scheme="bearer",
 * bearerFormat="JWT",
 * securityScheme="bearerAuth",
 * )
 */
abstract class Controller
{
    //
}