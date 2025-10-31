<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\Client;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use App\Http\Requests\Compte\ListComptesRequest;
use App\Services\CompteService;
use Illuminate\Support\Facades\Log;

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
    // GET monteiro.daisa/v1/comptes
    public function index(ListComptesRequest $request, CompteService $service)
    {
        try {
            $filters = $request->filters();
            $paginator = $service->list($filters);

            $data = $paginator->getCollection()->map(fn($c) => $this->formatCompteData($c));

            $response = [
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'currentPage' => $paginator->currentPage(),
                    'totalPages' => $paginator->lastPage(),
                    'totalItems' => $paginator->total(),
                    'itemsPerPage' => $paginator->perPage(),
                    'hasNext' => $paginator->hasMorePages(),
                    'hasPrevious' => $paginator->currentPage() > 1,
                ],
                'links' => [
                    'self' => url()->current() . '?' . http_build_query(['page' => $paginator->currentPage(), 'limit' => $paginator->perPage()]),
                    'next' => $paginator->hasMorePages() ? $paginator->url($paginator->currentPage() + 1) : null,
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                ],
            ];

            return response()->json($response);
        } catch (\Throwable $e) {
            Log::error('Comptes.index error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Erreur interne'], 500);
        }
    }

    private function formatCompteData(Compte $c): array
    {
        return [
            'id' => $c->id,
            'numeroCompte' => $c->numeroCompte,
            'titulaire' => $c->titulaire,
            'type' => $c->type,
            'devise' => $c->devise,
            'dateCreation' => $c->dateCreation instanceof \Illuminate\Support\Carbon ? $c->dateCreation->toDateString() : (string) $c->dateCreation,
            'statut' => $c->statut,
            'client_id' => $c->client_id,
            'created_at' => optional($c->created_at)->toDateTimeString(),
            'updated_at' => optional($c->updated_at)->toDateTimeString(),
        ];
    }

    /**
     * @OA\Get(
     *   path="/monteiro.daisa/v1/clients/{clientId}/comptes",
     *   summary="Lister les comptes d'un client (création si aucun)",
     *   tags={"Comptes"},
     *   @OA\Parameter(
     *     name="clientId",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="string", format="uuid")
     *   ),
     *   @OA\Response(response=200, description="Liste des comptes du client")
     * )
     */
    // GET monteiro.daisa/v1/clients/{clientId}/comptes
    public function byClient(Request $request, string $clientId)
    {
        try {
            $client = Client::find($clientId);
            if (!$client) {
                return $this->errorResponse('Client introuvable', 404);
            }

            $comptes = $client->comptes()->get();

            // Si aucun compte, en créer un par défaut selon la règle exprimée
            if ($comptes->isEmpty()) {
                $compte = Compte::create([
                    'client_id' => $client->id,
                    'titulaire' => $client->prenom . ' ' . $client->nom,
                    'type' => 'epargne',
                    'devise' => 'FCFA',
                    'statut' => 'actif',
                    'metadata' => ['version' => 1],
                ]);
                $comptes = collect([$compte]);
            }

            $data = $comptes->map(fn($c) => $this->formatCompteData($c));
            return $this->successResponse($data);
        } catch (\Throwable $e) {
            Log::error('Comptes.byClient error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Erreur interne'], 500);
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
     *   @OA\Response(response=200, description="Compte trouvé"),
     *   @OA\Response(response=404, description="Compte introuvable")
     * )
     */
    // GET monteiro.daisa/v1/comptes/{compteId}
    public function show(string $compteId)
    {
        try {
            $compte = Compte::find($compteId);
            if (!$compte) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'COMPTE_NOT_FOUND',
                        'message' => "Le compte avec l'ID spécifié n'existe pas",
                        'details' => [
                            'compteId' => $compteId,
                        ],
                    ],
                ], 404);
            }

            $dateCreation = $compte->dateCreation instanceof \Illuminate\Support\Carbon
                ? $compte->dateCreation->toISOString()
                : (string) $compte->dateCreation;

            $data = [
                'id' => $compte->id,
                'numeroCompte' => $compte->numeroCompte,
                'titulaire' => $compte->titulaire,
                'type' => $compte->type,
                'solde' => $compte->getSolde(),
                'devise' => $compte->devise,
                'dateCreation' => $dateCreation,
                'statut' => $compte->statut,
                'motifBlocage' => data_get($compte->metadata, 'motifBlocage'),
                'metadata' => $compte->metadata,
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::error('Comptes.show error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Erreur interne'], 500);
        }
    }

    /**
     * @OA\Patch(
     *   path="/monteiro.daisa/v1/comptes/{compteId}",
     *   summary="Mettre à jour un compte (y compris le blocage/déblocage)",
     *   description="Permet de mettre à jour le statut d'un compte, y compris le blocage et le déblocage.\n\n## Blocage d'un compte\nPour bloquer un compte, définissez `statut` à 'bloque' et fournissez un `motifBlocage`.\n\n## Déblocage d'un compte\nPour débloquer un compte, définissez `statut` à 'actif'. Le `motifBlocage` sera automatiquement supprimé.",
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
     *     description="Données de mise à jour du compte",
     *     @OA\JsonContent(
     *       type="object",
     *       required={"statut"},
     *       @OA\Property(
     *         property="statut", 
     *         type="string", 
     *         enum={"actif","bloque","ferme"},
     *         description="Nouveau statut du compte. 'bloque' pour bloquer, 'actif' pour débloquer"
     *       ),
     *       @OA\Property(
     *         property="motifBlocage", 
     *         type="string", 
     *         nullable=true,
     *         description="Obligatoire si statut='bloque'. Raison du blocage du compte"
     *       ),
     *       @OA\Property(
     *         property="metadata", 
     *         type="object", 
     *         nullable=true,
     *         description="Métadonnées supplémentaires du compte"
     *       )
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
     *     response=400,
     *     description="Requête invalide",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Le motif de blocage est requis")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Compte non trouvé",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
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
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Erreur interne du serveur",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Erreur lors de la mise à jour du compte")
     *     )
     *   )
     * )
     */
    // PATCH monteiro.daisa/v1/comptes/{compteId}
    public function update(Request $request, string $compteId)
    {
        try {
            $compte = Compte::find($compteId);

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
 *   @OA\Response(response=200, description="Compte trouvé"),
 *   @OA\Response(response=404, description="Compte introuvable")
 * )
 */
// GET monteiro.daisa/v1/comptes/{compteId}
public function show(string $compteId)
{
    try {
        $compte = Compte::find($compteId);
        if (!$compte) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'COMPTE_NOT_FOUND',
                    'message' => "Le compte avec l'ID spécifié n'existe pas",
                    'details' => [
                        'compteId' => $compteId,
                    ],
                ],
            ], 404);
        }

        $dateCreation = $compte->dateCreation instanceof \Illuminate\Support\Carbon
            ? $compte->dateCreation->toISOString()
            : (string) $compte->dateCreation;

        $data = [
            'id' => $compte->id,
            'numeroCompte' => $compte->numeroCompte,
            'titulaire' => $compte->titulaire,
            'type' => $compte->type,
            'solde' => $compte->getSolde(),
            'devise' => $compte->devise,
            'dateCreation' => $dateCreation,
            'statut' => $compte->statut,
            'motifBlocage' => data_get($compte->metadata, 'motifBlocage'),
            'metadata' => $compte->metadata,
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    } catch (\Throwable $e) {
        Log::error('Comptes.show error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json(['success' => false, 'message' => 'Erreur interne'], 500);
    }
}

/**
 * @OA\Patch(
 *   path="/monteiro.daisa/v1/comptes/{compteId}",
 *   summary="Mettre à jour un compte (y compris le blocage/déblocage)",
 *   description="Permet de mettre à jour le statut d'un compte, y compris le blocage et le déblocage.\n\n## Blocage d'un compte\nPour bloquer un compte, définissez `statut` à 'bloque' et fournissez un `motifBlocage`.\n\n## Déblocage d'un compte\nPour débloquer un compte, définissez `statut` à 'actif'. Le `motifBlocage` sera automatiquement supprimé.",
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
 *     description="Données de mise à jour du compte",
 *     @OA\JsonContent(
 *       type="object",
 *       required={"statut"},
 *       @OA\Property(
 *         property="statut", 
 *         type="string", 
 *         enum={"actif","bloque","ferme"},
 *         description="Nouveau statut du compte. 'bloque' pour bloquer, 'actif' pour débloquer"
 *       ),
 *       @OA\Property(
 *         property="motifBlocage", 
 *         type="string", 
 *         nullable=true,
 *         description="Obligatoire si statut='bloque'. Raison du blocage du compte"
 *       ),
 *       @OA\Property(
 *         property="metadata", 
 *         type="object", 
 *         nullable=true,
 *         description="Métadonnées supplémentaires du compte"
 *       )
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
 *     response=400,
 *     description="Requête invalide",
 *     @OA\JsonContent(
 *       @OA\Property(property="success", type="boolean", example=false),
 *       @OA\Property(property="message", type="string", example="Le motif de blocage est requis")
 *     )
 *   ),
 *   @OA\Response(
 *     response=404,
 *     description="Compte non trouvé",
 *     @OA\JsonContent(
 *       @OA\Property(property="success", type="boolean", example=false),
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
 *   ),
 *   @OA\Response(
 *     response=500,
 *     description="Erreur interne du serveur",
 *     @OA\JsonContent(
 *       @OA\Property(property="success", type="boolean", example=false),
 * )
 */
// PATCH monteiro.daisa/v1/comptes/{compteId}
public function update(Request $request, string $compteId)
{
    try {
        $compte = Compte::find($compteId);
        if (!$compte) {
            return $this->errorResponse('Compte non trouvé', 404);
        }

        $validated = $request->validate([
            'titulaire' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:cheque,epargne',
            'devise' => 'sometimes|string|max:10',
            'dateCreation' => 'sometimes|date',
            'client_id' => 'sometimes|string|uuid|exists:clients,id',
            'metadata' => 'sometimes|array',
            'motifBlocage' => 'sometimes|string|nullable',
        ]);

        // Liste des champs autorisés à être modifiés
        $updatableFields = [
            'titulaire', 'type', 'devise', 'dateCreation', 'client_id', 'metadata'
        ];

        // Mise à jour des champs autorisés
        foreach ($updatableFields as $field) {
            if (array_key_exists($field, $validated)) {
                $compte->$field = $validated[$field];
            }
        }

        // Gestion spécifique des métadonnées
        if (isset($validated['metadata'])) {
            $compte->metadata = array_merge(
                $compte->metadata ?? [],
                $validated['metadata']
            );
        }

        // Gestion du motif de blocage
        if (isset($validated['motifBlocage'])) {
            $metadata = $compte->metadata ?? [];
            if (!empty($validated['motifBlocage'])) {
                $metadata['motifBlocage'] = $validated['motifBlocage'];
            } else {
                unset($metadata['motifBlocage']);
            }
            $compte->metadata = $metadata;
        }

        $compte->save();

        return response()->json([
            'success' => true,
            'message' => 'Compte mis à jour avec succès',
            'data' => $this->formatCompteData($compte)
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        Log::error('Erreur lors de la mise à jour du compte: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'compteId' => $compteId
        ]);
        return $this->errorResponse('Erreur lors de la mise à jour du compte: ' . $e->getMessage(), 500);
    }
}

/**
 * @OA\Delete(
 *   path="/monteiro.daisa/v1/comptes/{compteId}",
 *   summary="Supprimer un compte (soft delete)",
 *   tags={"Comptes"},
 *   security={{"bearerAuth": {}}},
 *   @OA\Parameter(
 *     name="compteId",
 *     in="path",
 *     required=true,
 *     @OA\Schema(type="string", format="uuid")
 *   ),
 *   @OA\Response(
 *     response=200,
 *     description="Compte marqué comme supprimé avec succès",
 *     @OA\JsonContent(
 *       @OA\Property(property="success", type="boolean", example=true),
 *       @OA\Property(property="message", type="string", example="Compte supprimé avec succès"),
 *       @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *         @OA\Property(property="numeroCompte", type="string", example="C00123456"),
 *         @OA\Property(property="statut", type="string", enum={"actif","bloque","ferme"}, example="ferme"),
 *         @OA\Property(property="dateFermeture", type="string", format="date-time", example="2025-10-19T11:15:00Z")
 *       )
 *     )
 *   ),
 *   @OA\Response(response=404, description="Compte non trouvé"),
 *   @OA\Response(response=500, description="Erreur interne du serveur")
 * )
 */
// DELETE monteiro.daisa/v1/comptes/{compteId}
public function destroy(string $compteId)
{
    try {
        $compte = Compte::find($compteId);
        
        if (!$compte) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé',
                'errors' => [
                    'message' => 'Aucun compte trouvé avec cet ID',
                    'details' => [
                        'compteId' => $compteId,
                    ],
                ],
            ], 404);
        }

        // Mise à jour du statut et de la date de fermeture avant la suppression
        $compte->update([
            'statut' => 'ferme',
            'dateFermeture' => now()
        ]);

        // Suppression logique
        $compte->delete();

        return response()->json([
            'success' => true,
            'message' => 'Compte supprimé avec succès',
            'data' => [
                'id' => $compte->id,
                'numeroCompte' => $compte->numeroCompte,
                'statut' => $compte->statut,
                'dateFermeture' => $compte->dateFermeture ? $compte->dateFermeture->toIso8601String() : null,
            ]
        ]);

    } catch (\Throwable $e) {
        Log::error('Erreur lors de la suppression du compte: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'compteId' => $compteId
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la suppression du compte',
            'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur'
        ], 500);
    }
     *   tags={"Comptes"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="compteId",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="string", format="uuid")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Compte marqué comme supprimé avec succès",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Compte supprimé avec succès"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *         @OA\Property(property="numeroCompte", type="string", example="C00123456"),
     *         @OA\Property(property="statut", type="string", enum={"actif","bloque","ferme"}, example="ferme"),
     *         @OA\Property(property="dateFermeture", type="string", format="date-time", example="2025-10-19T11:15:00Z")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=404, description="Compte non trouvé"),
     *   @OA\Response(response=500, description="Erreur interne du serveur")
     * )
     */
    // DELETE monteiro.daisa/v1/comptes/{compteId}
    public function destroy(string $compteId)
    {
        try {
            $compte = Compte::find($compteId);
            
            if (!$compte) {
                return response()->json([
                    'success' => false,
                    'message' => 'Compte non trouvé',
                    'errors' => [
                        'message' => 'Aucun compte trouvé avec cet ID',
                        'details' => [
                            'compteId' => $compteId,
                        ],
                    ],
                ], 404);
            }

            // Mise à jour du statut et de la date de fermeture avant la suppression
            $compte->update([
                'statut' => 'ferme',
                'dateFermeture' => now()
            ]);

            // Suppression logique
            $compte->delete();

            return response()->json([
                'success' => true,
                'message' => 'Compte supprimé avec succès',
                'data' => [
                    'id' => $compte->id,
                    'numeroCompte' => $compte->numeroCompte,
                    'statut' => $compte->statut,
                    'dateFermeture' => $compte->dateFermeture ? $compte->dateFermeture->toIso8601String() : null,
                ]
            ]);

        } catch (\Throwable $e) {
            Log::error('Erreur lors de la suppression du compte: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'compteId' => $compteId
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du compte',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur'
            ], 500);
        }
    }
}
