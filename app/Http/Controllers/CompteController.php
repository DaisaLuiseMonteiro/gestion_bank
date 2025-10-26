<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\Client;
use App\Traits\ApiResponseTrait;
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
     *   @OA\Parameter(name="type", in="query", required=false, @OA\Schema(type="string", enum={"courant","epargne","cheque"})),
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
        $query = Compte::query();

        // Filtrage et tri délégués au modèle via scopes
        $query->when($request->filled('type'), fn($q) => $q->where('type', $request->string('type')));
        $query->when($request->filled('statut'), fn($q) => $q->where('statut', $request->string('statut')));
        $query->when($request->filled('search'), function ($q) use ($request) {
            $s = $request->string('search');
            $q->where(function ($qq) use ($s) {
                $qq->where('titulaire', 'like', "%$s%")
                   ->orWhere('numeroCompte', 'like', "%$s%");
            });
        });

        $sort = in_array($request->string('sort'), ['dateCreation','titulaire']) ? $request->string('sort') : 'dateCreation';
        $order = $request->string('order') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $order);

        $page = max(1, (int) $request->input('page', 1));
        $limit = min(100, max(1, (int) $request->input('limit', 10)));

        $paginator = $query->paginate($limit, ['*'], 'page', $page);

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
    }
}
