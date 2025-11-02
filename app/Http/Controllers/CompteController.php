<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\Client;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use App\Http\Requests\Compte\ListComptesRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Services\CompteService;
use Illuminate\Support\Facades\Log;

class CompteController extends Controller
{
    use ApiResponseTrait;

    // ... (le reste du code reste inchangé jusqu'à la méthode updateClient)

    /**
     * @OA\Patch(
     *   path="/monteiro.daisa/v1/comptes/{compteId}/client",
     *   summary="Mettre à jour les informations du titulaire d'un compte",
     *   tags={"Comptes"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="compteId",
     *     in="path",
     *     required=true,
     *     description="ID du compte à mettre à jour",
     *     @OA\Schema(type="string", format="uuid")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="nom", type="string", example="Diallo"),
     *       @OA\Property(property="prenom", type="string", example="Amadou"),
     *       @OA\Property(property="email", type="string", format="email", example="amadou.diallo@example.com"),
     *       @OA\Property(property="telephone", type="string", example="+221771234568"),
     *       @OA\Property(property="password", type="string", example="nouveaumotdepasse")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Informations mises à jour avec succès",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Informations du client mises à jour avec succès"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="id", type="string", format="uuid"),
     *         @OA\Property(property="nom", type="string"),
     *         @OA\Property(property="prenom", type="string"),
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="telephone", type="string"),
     *         @OA\Property(property="updated_at", type="string", format="date-time")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=404, description="Compte non trouvé"),
     *   @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function updateClient($compteId, UpdateClientRequest $request)
    {
        try {
            $compte = Compte::findOrFail($compteId);
            $client = $compte->client;
            
            $client->update($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Informations du client mises à jour avec succès',
                'data' => $client->only(['id', 'nom', 'prenom', 'email', 'telephone', 'updated_at'])
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour du client: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la mise à jour des informations du client'
            ], 500);
        }
    }
}