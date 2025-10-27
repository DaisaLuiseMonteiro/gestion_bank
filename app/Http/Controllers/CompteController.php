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

    /**
     * @OA\Delete(
     *   path="/monteiro.daisa/v1/comptes/{compteId}",
     *   summary="Supprimer (soft delete) un compte",
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
     *     description="Compte supprimé (fermé) avec succès",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Compte supprimé avec succès"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="id", type="string", format="uuid"),
     *         @OA\Property(property="numeroCompte", type="string", example="C00123456"),
     *         @OA\Property(property="statut", type="string", example="ferme"),
     *         @OA\Property(property="dateFermeture", type="string", format="date-time")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=404, description="Compte introuvable")
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
                    'error' => [
                        'code' => 'COMPTE_NOT_FOUND',
                        'message' => "Le compte avec l'ID spécifié n'existe pas",
                        'details' => [ 'compteId' => $compteId ],
                    ],
                ], 404);
            }

            $nowIso = now()->toISOString();
            $metadata = $compte->metadata ?? [];
            $metadata['dateFermeture'] = $nowIso;
            $compte->metadata = $metadata;
            $compte->statut = 'ferme';
            $compte->save();

            return response()->json([
                'success' => true,
                'message' => 'Compte supprimé avec succès',
                'data' => [
                    'id' => $compte->id,
                    'numeroCompte' => $compte->numeroCompte,
                    'statut' => $compte->statut,
                    'dateFermeture' => $metadata['dateFermeture'] ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Comptes.destroy error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
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

    // GET monteiro.daisa/v1/comptes/{clientId}
    public function showByClient(string $clientId)
    {
        try {
            $compte = Compte::where('client_id', $clientId)->first();
            if (!$compte) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'COMPTE_NOT_FOUND',
                        'message' => "Le compte avec l'ID spécifié n'existe pas",
                        'details' => [
                            'compteId' => $clientId,
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
            Log::error('Comptes.showByClient error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Erreur interne'], 500);
        }
    }
}
