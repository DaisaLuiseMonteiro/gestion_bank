<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Http\Requests\BlockerCompteRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Swagger\Schemas;

class CompteBloqueController extends Controller
{
    /**
     * @OA\Post(
     *     path="/monteiro.daisa/v1/comptes/{compteId}/bloquer",
     *     summary="Bloquer un compte épargne",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         required=true,
     *         description="ID du compte à bloquer",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"motif","duree","unite"},
     *             @OA\Property(property="motif", type="string", example="Activité suspecte détectée"),
     *             @OA\Property(property="duree", type="integer", example=30),
     *             @OA\Property(property="unite", type="string", enum={"jours","semaines","mois","annees"}, example="jours")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte bloqué avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/CompteBloque")
     *     ),
     *     @OA\Response(response=400, description="Requête invalide"),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=404, description="Compte non trouvé")
     * )
     */
    public function bloquer(BlockerCompteRequest $request, $compteId): JsonResponse
    {
        $compte = Compte::findOrFail($compteId);
        
        if ($compte->statut !== 'actif') {
            return response()->json([
                'success' => false,
                'message' => 'Seul un compte actif peut être bloqué'
            ], 400);
        }

        $dateDebut = now();
        $dateFin = $this->calculerDateFin($dateDebut, $request->duree, $request->unite);

        $compte->update([
            'statut' => 'bloque',
            'metadata' => array_merge($compte->metadata ?? [], [
                'motifBlocage' => $request->motif,
                'dateDebutBlocage' => $dateDebut,
                'dateFinBlocage' => $dateFin,
                'dureeBlocage' => $request->duree,
                'uniteDuree' => $request->unite
            ])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Compte bloqué avec succès',
            'data' => [
                'id' => $compte->id,
                'statut' => $compte->statut,
                'motifBlocage' => $request->motif,
                'dateBlocage' => $dateDebut->toIso8601String(),
                'dateDeblocagePrevue' => $dateFin->toIso8601String()
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/monteiro.daisa/v1/comptes/{compteId}/debloquer",
     *     summary="Débloquer un compte épargne",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         required=true,
     *         description="ID du compte à débloquer",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte débloqué avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/CompteDebloque")
     *     ),
     *     @OA\Response(response=400, description="Requête invalide"),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=404, description="Compte non trouvé")
     * )
     */
    public function debloquer($compteId): JsonResponse
    {
        $compte = Compte::findOrFail($compteId);
        
        if ($compte->statut !== 'bloque') {
            return response()->json([
                'success' => false,
                'message' => 'Le compte doit être bloqué pour être débloqué'
            ], 400);
        }

        $metadata = $compte->metadata ?? [];
        $metadata['dateDeblocage'] = now();
        unset($metadata['motifBlocage']);
        unset($metadata['dateDebutBlocage']);
        unset($metadata['dateFinBlocage']);
        unset($metadata['dureeBlocage']);
        unset($metadata['uniteDuree']);

        $compte->update([
            'statut' => 'actif',
            'metadata' => $metadata
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Compte débloqué avec succès',
            'data' => [
                'id' => $compte->id,
                'statut' => 'actif',
                'dateDeblocage' => now()->toIso8601String()
            ]
        ]);
    }

    private function calculerDateFin(Carbon $dateDebut, int $duree, string $unite): Carbon
    {
        return match($unite) {
            'jours' => $dateDebut->copy()->addDays($duree),
            'semaines' => $dateDebut->copy()->addWeeks($duree),
            'mois' => $dateDebut->copy()->addMonths($duree),
            'annees' => $dateDebut->copy()->addYears($duree),
            default => $dateDebut->copy()->addMonth()
        };
    }
}
