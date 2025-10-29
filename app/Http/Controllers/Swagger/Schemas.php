<?php

namespace App\Http\Controllers\Swagger;

/**
 * @OA\Schema(
 *     schema="CompteBloque",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Compte bloqué avec succès"),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="id", type="string", format="uuid"),
 *         @OA\Property(property="statut", type="string", example="bloque"),
 *         @OA\Property(property="motifBlocage", type="string"),
 *         @OA\Property(property="dateBlocage", type="string", format="date-time"),
 *         @OA\Property(property="dateDeblocagePrevue", type="string", format="date-time")
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="CompteDebloque",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Compte débloqué avec succès"),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="id", type="string", format="uuid"),
 *         @OA\Property(property="statut", type="string", example="actif"),
 *         @OA\Property(property="dateDeblocage", type="string", format="date-time")
 *     )
 * )
 */
class Schemas
{
    // Cette classe sert uniquement de conteneur pour la documentation Swagger
}
