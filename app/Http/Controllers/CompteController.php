<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\Client;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use App\Http\Requests\Compte\ListComptesRequest;
use App\Http\Requests\Compte\UpdateCompteRequest;
use App\Services\CompteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('limit', 15);
            $page = $request->input('page', 1);
            
            $query = Compte::query();
            
            // Filtres
            if ($type = $request->input('type')) {
                $query->where('type', $type);
            }
            
            if ($statut = $request->input('statut')) {
                $query->where('statut', $statut);
            }
            
            if ($search = $request->input('search')) {
                $query->where(function($q) use ($search) {
                    $q->where('numeroCompte', 'like', "%{$search}%")
                      ->orWhere('titulaire', 'like', "%{$search}%");
                });
            }
            
            // Tri
            $sort = $request->input('sort', 'dateCreation');
            $order = $request->input('order', 'desc');
            $query->orderBy($sort, $order);
            
            // Pagination
            $paginator = $query->paginate($perPage, ['*'], 'page', $page);
            
            $data = $paginator->getCollection()->map(fn($c) => $this->formatCompteData($c));
            
            return $this->successResponse([
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
            
        } catch (\Throwable $e) {
            Log::error('Comptes.index error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Erreur lors de la récupération des comptes');
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

    /**
     * @OA\Post(
     *   path="/monteiro.daisa/v1/comptes",
     *   summary="Créer un nouveau compte",
     *   tags={"Comptes"},
     *   security={{"bearerAuth": {}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"client_id", "type", "devise"},
     *       @OA\Property(property="client_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *       @OA\Property(property="type", type="string", enum={"cheque", "epargne"}, example="epargne"),
     *       @OA\Property(property="devise", type="string", example="EUR"),
     *       @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, example="actif"),
     *       @OA\Property(property="metadata", type="object", example={"source": "api"})
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Compte créé avec succès",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="data", type="object", ref="#/components/schemas/Compte")
     *     )
     *   ),
     *   @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function store(Request $request)
    {
        try {
            // Validation via le modèle
            $validated = Compte::validate($request->all());
            
            // Création du compte
            $compte = Compte::create([
                'numeroCompte' => 'C' . Str::random(8),
                'titulaire' => $validated['titulaire'] ?? 'Titulaire inconnu',
                'type' => $validated['type'],
                'devise' => $validated['devise'],
                'statut' => $validated['statut'] ?? 'actif',
                'metadata' => $validated['metadata'] ?? [],
                'client_id' => $validated['client_id'],
                'dateCreation' => now()
            ]);

            return $this->successResponse(
                $this->formatCompteDetail($compte),
                201
            );

        } catch (\Throwable $e) {
            Log::error('Comptes.store error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'input' => $request->all()
            ]);
            return $this->errorResponse('Erreur lors de la création du compte');
        }
    }

    /**
     * @OA\Put(
     *   path="/monteiro.daisa/v1/comptes/{compteId}",
     *   summary="Mettre à jour un compte",
     *   tags={"Comptes"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="compteId",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="string", format="uuid")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="type", type="string", enum={"cheque", "epargne"}, example="epargne"),
     *       @OA\Property(property="devise", type="string", example="EUR"),
     *       @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}),
     *       @OA\Property(property="metadata", type="object")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Compte mis à jour avec succès",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="data", type="object", ref="#/components/schemas/Compte")
     *     )
     *   ),
     *   @OA\Response(response=404, description="Compte non trouvé"),
     *   @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function update(Request $request, string $id)
    {
        try {
            $compte = Compte::findOrFail($id);
            
            // Validation via le modèle (mise à jour)
            $validated = Compte::validate($request->all(), true);
            
            // Mise à jour du compte
            $compte->update($validated);

            return $this->successResponse(
                $this->formatCompteDetail($compte)
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse(
                "Le compte avec l'ID spécifié n'existe pas", 
                404, 
                'COMPTE_NOT_FOUND', 
                ['id' => $id]
            );
        } catch (\Throwable $e) {
            Log::error('Comptes.update error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
                'input' => $request->all()
            ]);
            return $this->errorResponse('Erreur lors de la mise à jour du compte');
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
     * Formate les détails d'un compte pour la réponse
     */
    private function formatCompteDetail(Compte $compte): array
    {
        $dateCreation = $compte->dateCreation instanceof \Illuminate\Support\Carbon
            ? $compte->dateCreation->toISOString()
            : (string) $compte->dateCreation;

        return [
            'id' => $compte->id,
            'numeroCompte' => $compte->numeroCompte,
            'titulaire' => $compte->titulaire,
            'type' => $compte->type,
            'solde' => $compte->solde ?? 0,
            'devise' => $compte->devise,
            'dateCreation' => $dateCreation,
            'statut' => $compte->statut,
            'client_id' => $compte->client_id,
            'metadata' => $compte->metadata,
            'created_at' => $compte->created_at?->toDateTimeString(),
            'updated_at' => $compte->updated_at?->toDateTimeString(),
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
    /**
     * @OA\Get(
     *   path="/monteiro.daisa/v1/comptes/{compteId}",
     *   summary="Récupérer un compte par son ID",
     *   tags={"Comptes"},
     *   @OA\Parameter(
     *     name="compteId",
     *     in="path",
     *     required=true,
     *     description="ID du compte à récupérer",
     *     @OA\Schema(type="string", format="uuid")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Compte trouvé",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="id", type="string", format="uuid"),
     *         @OA\Property(property="numeroCompte", type="string", example="C00123456"),
     *         @OA\Property(property="titulaire", type="string", example="John Doe"),
     *         @OA\Property(property="type", type="string", enum={"epargne", "courant"}),
     *         @OA\Property(property="solde", type="number", format="float", example=1000.50),
     *         @OA\Property(property="devise", type="string", example="EUR"),
     *         @OA\Property(property="dateCreation", type="string", format="date-time"),
     *         @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}),
     *         @OA\Property(property="motifBlocage", type="string", nullable=true),
     *         @OA\Property(property="metadata", type="object")
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Compte non trouvé",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="error", type="object",
     *         @OA\Property(property="code", type="string", example="COMPTE_NOT_FOUND"),
     *         @OA\Property(property="message", type="string", example="Le compte avec l'ID spécifié n'existe pas"),
     *         @OA\Property(property="details", type="object",
     *           @OA\Property(property="compteId", type="string", format="uuid")
     *         )
     *       )
     *     )
     *   )
     * )
     */
    public function show(string $compteId)
    {
        try {
            $compte = Compte::find($compteId);
            
            if (!$compte) {
                return $this->errorResponse(
                    "Le compte avec l'ID spécifié n'existe pas",
                    404,
                    'COMPTE_NOT_FOUND',
                    ['compteId' => $compteId]
                );
            }

            return $this->successResponse($this->formatCompteDetail($compte));
            
        } catch (\Throwable $e) {
            Log::error('Comptes.show error: '.$e->getMessage(), [
                'compteId' => $compteId,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse('Erreur lors de la récupération du compte');
        }
    }

    /**
     * @OA\Patch(
     *   path="/monteiro.daisa/v1/comptes/{compteId}",
     *   summary="Mettre à jour les informations d'un compte",
     *   tags={"Comptes"},
     *   @OA\Parameter(name="compteId", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="titulaire", type="string", example="Amadou Diallo Junior"),
     *       @OA\Property(
     *         property="informationsClient",
     *         type="object",
     *         @OA\Property(property="telephone", type="string", example="771234568"),
     *         @OA\Property(property="email", type="string", example="client@example.com"),
     *         @OA\Property(property="password", type="string", example="nouveaumotdepasse"),
     *         @OA\Property(property="nci", type="string", example="1234567890123")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=200, description="Compte mis à jour avec succès"),
     *   @OA\Response(response=404, description="Compte non trouvé"),
     *   @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function update(UpdateCompteRequest $request, string $compteId)
    {
        try {
            return DB::transaction(function () use ($request, $compteId) {
                // Récupérer le compte
                $compte = Compte::findOrFail($compteId);
                $client = $compte->client;
                
                // Mettre à jour les champs du compte si fournis
                if ($request->has('titulaire')) {
                    $compte->titulaire = $request->titulaire;
                }
                
                // Mettre à jour les informations du client si fournies
                if ($request->has('informationsClient')) {
                    $clientData = $request->informationsClient;
                    
                    if (isset($clientData['telephone'])) {
                        $client->telephone = $clientData['telephone'];
                    }
                    
                    if (isset($clientData['email'])) {
                        $client->email = $clientData['email'] ?: null;
                    }
                    
                    if (isset($clientData['password'])) {
                        $client->password = bcrypt($clientData['password']);
                    }
                    
                    if (isset($clientData['nci'])) {
                        $client->nci = $clientData['nci'];
                    }
                    
                    $client->save();
                }
                
                $compte->save();
                $compte->refresh();
                
                // Mettre à jour les métadonnées
                $metadata = array_merge($compte->metadata ?? [], [
                    'derniereModification' => now()->toIso8601String(),
                    'version' => ($compte->metadata['version'] ?? 0) + 1
                ]);
                
                $compte->update(['metadata' => $metadata]);
                
                return $this->successResponse(
                    $this->formatCompteData($compte),
                    'Compte mis à jour avec succès',
                    200
                );
                
            });
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Compte non trouvé', 404);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du compte: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la mise à jour du compte', 500);
        }
    }
    
    private function formatCompteData($compte)
    {
        return [
            'id' => $compte->id,
            'numeroCompte' => $compte->numero_compte,
            'titulaire' => $compte->titulaire,
            'type' => $compte->type,
            'solde' => $compte->solde,
            'devise' => $compte->devise,
            'dateCreation' => $compte->created_at->toIso8601String(),
            'statut' => $compte->statut,
            'metadata' => $compte->metadata
        ];
    }

    /**
     * @OA\Patch(
     *   path="/monteiro.daisa/v1/comptes/{compteId}",
     *   summary="Mettre à jour les informations d'un compte",
     *   tags={"Comptes"},
     *   @OA\Parameter(name="compteId", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="titulaire", type="string", example="Amadou Diallo Junior"),
     *       @OA\Property(
     *         property="informationsClient",
     *         type="object",
     *         @OA\Property(property="telephone", type="string", example="771234568"),
     *         @OA\Property(property="email", type="string", example="client@example.com"),
     *         @OA\Property(property="password", type="string", example="nouveaumotdepasse"),
     *         @OA\Property(property="nci", type="string", example="1234567890123")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=200, description="Compte mis à jour avec succès"),
     *   @OA\Response(response=404, description="Compte non trouvé"),
     *   @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function update(UpdateCompteRequest $request, string $compteId)
    {
        try {
            return DB::transaction(function () use ($request, $compteId) {
                // Récupérer le compte
                $compte = Compte::findOrFail($compteId);
                $client = $compte->client;
                
                // Mettre à jour les champs du compte si fournis
                if ($request->has('titulaire')) {
                    $compte->titulaire = $request->titulaire;
                }
                
                // Mettre à jour les informations du client si fournies
                if ($request->has('informationsClient')) {
                    $clientData = $request->informationsClient;
                    
                    if (isset($clientData['telephone'])) {
                        $client->telephone = $clientData['telephone'];
                    }
                    
                    if (isset($clientData['email'])) {
                        $client->email = $clientData['email'] ?: null;
                    }
                    
                    if (isset($clientData['password'])) {
                        $client->password = bcrypt($clientData['password']);
                    }
                    
                    if (isset($clientData['nci'])) {
                        $client->nci = $clientData['nci'];
                    }
                    
                    $client->save();
                }
                
                $compte->save();
                $compte->refresh();
                
                // Mettre à jour les métadonnées
                $metadata = array_merge($compte->metadata ?? [], [
                    'derniereModification' => now()->toIso8601String(),
                    'version' => ($compte->metadata['version'] ?? 0) + 1
                ]);
                
                $compte->update(['metadata' => $metadata]);
                
                return $this->successResponse(
                    $this->formatCompteData($compte),
                    'Compte mis à jour avec succès',
                    200
                );
                
            });
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Compte non trouvé', 404);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du compte: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la mise à jour du compte', 500);
        }
    }
    
    private function formatCompteData($compte)
    {
        return [
            'id' => $compte->id,
            'numeroCompte' => $compte->numero_compte,
            'titulaire' => $compte->titulaire,
            'type' => $compte->type,
            'solde' => $compte->solde,
            'devise' => $compte->devise,
            'dateCreation' => $compte->created_at->toIso8601String(),
            'statut' => $compte->statut,
            'metadata' => $compte->metadata
        ];
    }
}
