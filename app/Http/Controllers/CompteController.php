<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\Client;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use App\Http\Requests\Compte\ListComptesRequest;
use App\Services\CompteService;
use App\Http\Requests\Compte\DeleteCompteRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CompteController extends Controller
{
    use ApiResponseTrait;

    // ... (autres méthodes inchangées)

    /**
     * @OA\Delete(
     *     path="/monteiro.daisa/v1/comptes/{compteId}",
     *     summary="Fermer un compte",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         required=true,
     *         description="ID du compte à fermer",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"motif_fermeture"},
     *             @OA\Property(property="motif_fermeture", type="string", example="Fermeture à la demande du client")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte fermé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte fermé avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="numeroCompte", type="string"),
     *                 @OA\Property(property="statut", type="string", enum={"actif","bloque","ferme"}),
     *                 @OA\Property(property="dateFermeture", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé"
     *     )
     * )
     */
    public function destroy(DeleteCompteRequest $request, string $compteId)
    {
        try {
            $compte = Compte::find($compteId);
            
            if (!$compte) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'COMPTE_NOT_FOUND',
                        'message' => 'Le compte spécifié est introuvable',
                        'details' => [
                            'compteId' => $compteId,
                        ],
                    ],
                ], 404);
            }

            // Utilisation de la méthode du modèle pour fermer le compte
            $compte->fermerCompte($request->input('motif_fermeture'));

            return response()->json([
                'success' => true,
                'message' => 'Compte fermé avec succès',
                'data' => [
                    'id' => $compte->id,
                    'numeroCompte' => $compte->numeroCompte,
                    'statut' => $compte->statut,
                    'dateFermeture' => $compte->deleted_at->toIso8601String(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la fermeture du compte', [
                'compteId' => $compteId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CLOSE_ACCOUNT_ERROR',
                    'message' => 'Une erreur est survenue lors de la fermeture du compte',
                    'details' => [
                        'error' => $e->getMessage(),
                    ],
                ],
            ], 500);
        }
    }

    // ... (autres méthodes inchangées)
}