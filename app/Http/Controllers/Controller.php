<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Gestion Bank API",
 *     version="1.0.0",
 *     description="API pour la gestion bancaire"
 * )
 *
 * @OA\Server(
 *     url="/",
 *     description="Serveur local"
 * )
 *
 * @OA\SecurityScheme(
 *     type="http",
 *     scheme="bearer",
 *     securityScheme="bearerAuth",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Tag(
 *     name="Comptes",
 *     description="Opérations sur les comptes bancaires"
 * )
 * 
 * @OA\Tag(
 *     name="Clients",
 *     description="Gestion des clients"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
