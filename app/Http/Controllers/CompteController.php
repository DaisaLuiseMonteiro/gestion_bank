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
use Illuminate\Http\Request;

class CompteController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *   path="/monteiro.daisa/v1/comptes",
     *   summary="Lister les comptes",
     *   tags={"Comptes"},
     *   @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="type", in="query", required=false, @OA\Schema(type="string", enum={"cheque","epargne"})),
     *   @OA\Parameter(name="statut", in="query", required=false, @OA\Schema(type="string", enum={"actif","bloque","ferme"})),
     *   @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *   @OA\Parameter(name="sort", in="query", required=false, @OA\Schema(type="string", enum={"dateCreation","titulaire"})),
     *   @OA\Parameter(name="order", in="query", required=false, @OA\Schema(type="string", enum={"asc","desc"})),
     *   @OA\Response(response=200, description="Liste paginée des comptes")
     * )
     */
    public function index(ListComptesRequest $request, CompteService $service)
    {
        try {
            $filters = $request->filters();
            $paginator = $service->list($filters);

            $data = $paginator->getCollection()->map(fn($c) => $this->formatCompteData($c));

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'currentPage' => $paginator->currentPage(),
                    'totalPages' => $paginator->lastPage(),
                    'totalItems' => $paginator->total(),
                    'itemsPerPage' => $paginator->perPage(),
                    'hasNext' => $paginator->hasMorePages(),
                    'hasPrevious' => $paginator->currentPage() > 1,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des comptes: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la récupération des comptes', 500);
        }
    }

    private function formatCompteData(Compte $compte): array
    {
        return [
            'id' => $compte->id,
            'numeroCompte' => $compte->numero_compte,
            'type' => $compte->type,
            'solde' => $compte->solde,
            'devise' => $compte->devise,
            'statut' => $compte->statut,
            'dateCreation' => $compte->created_at->toDateTimeString(),
            'client' => [
                'id' => $compte->client->id,
                'nom' => $compte->client->nom,
                'prenom' => $compte->client->prenom,
                'email' => $compte->client->email
            ],
            'metadata' => $compte->metadata
        ];
    }

    /**
     * @OA\Get(
     *   path="/monteiro.daisa/v1/clients/{clientId}/comptes",
     *   summary="Lister les comptes d'un client",
     *   tags={"Comptes"},
     *   @OA\Parameter(
     *     name="clientId",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="string", format="uuid")
     *   ),
     *   @OA\Response(response=200, description="Liste des comptes du client"),
     *   @OA\Response(response=404, description="Client non trouvé")
     * )
     */
    public function byClient(string $clientId)
    {
        try {
            $client = Client::findOrFail($clientId);
            $comptes = $client->comptes()->get();
            
            return response()->json([
                'success' => true,
                'data' => $comptes->map(fn($c) => $this->formatCompteData($c))
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Client non trouvé', 404);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des comptes du client: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la récupération des comptes', 500);
        }
    }

    /**
     * @OA\Get(
     *   path="/monteiro.daisa/v1/comptes/{compteId}",
     *   summary="Récupérer un compte par son ID",
     *   tags={"Comptes"},
     *   @OA\Parameter(
     *     name="compteId",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="string", format="uuid")
     *   ),
     *   @OA\Response(response=200, description="Détails du compte"),
     *   @OA\Response(response=404, description="Compte non trouvé")
     * )
     */
    public function show(string $compteId)
    {
        try {
            $compte = Compte::findOrFail($compteId);
            return response()->json([
                'success' => true,
                'data' => $this->formatCompteData($compte)
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Compte non trouvé', 404);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du compte: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la récupération du compte', 500);
        }
    }

    /**
     * @OA\Patch(
     *   path="/monteiro.daisa/v1/comptes/{compteId}",
     *   summary="Mettre à jour les métadonnées d'un compte",
     *   tags={"Comptes"},
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
     *       @OA\Property(property="motifBlocage", type="string", nullable=true, example="Suspicion de fraude"),
     *       @OA\Property(property="metadata", type="object", example={})
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Compte mis à jour avec succès",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Compte mis à jour avec succès"),
     *       @OA\Property(property="data", type="object", ref="#/components/schemas/Compte")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Compte non trouvé",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Compte non trouvé")
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Données invalides",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="The given data was invalid."),
     *       @OA\Property(property="errors", type="object")
     *     )
     *   )
     * )
     */
    public function update(Request $request, string $compteId)
    {
        try {
            $compte = Compte::findOrFail($compteId);

            $validated = $request->validate([
                'motifBlocage' => 'sometimes|string|nullable',
                'metadata' => 'sometimes|array'
            ]);

            // Gestion du motif de blocage
            if (array_key_exists('motifBlocage', $validated)) {
                $metadata = $compte->metadata ?? [];
                if (!empty($validated['motifBlocage'])) {
                    $metadata['motifBlocage'] = $validated['motifBlocage'];
                } else {
                    unset($metadata['motifBlocage']);
                }
                $compte->metadata = $metadata;
            }

            // Mise à jour des métadonnées si fournies
            if (isset($validated['metadata'])) {
                $compte->metadata = array_merge(
                    $compte->metadata ?? [],
                    $validated['metadata']
                );
            }

            $compte->save();

            return response()->json([
                'success' => true,
                'message' => 'Compte mis à jour avec succès',
                'data' => $this->formatCompteData($compte)
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Compte non trouvé', 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du compte: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la mise à jour du compte', 500);
        }
    }

    /**
     * @OA\Delete(
     *   path="/monteiro.daisa/v1/comptes/{compteId}",
     *   summary="Supprimer un compte (soft delete)",
     *   tags={"Comptes"},
     *   @OA\Parameter(
     *     name="compteId",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="string", format="uuid")
     *   ),
     *   @OA\Response(response=204, description="Compte supprimé avec succès"),
     *   @OA\Response(response=404, description="Compte non trouvé")
     * )
     */
    public function destroy(string $compteId)
    {
        try {
            $compte = Compte::findOrFail($compteId);
            $compte->delete();
            
            return response()->noContent();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Compte non trouvé', 404);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du compte: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la suppression du compte', 500);
        }
    }

    /**
     * @OA\Patch(
     *   path="/monteiro.daisa/v1/comptes/{compteId}",
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
    /**
     * @OA\Patch(
     *   path="/monteiro.daisa/v1/comptes/{compteId}",
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
