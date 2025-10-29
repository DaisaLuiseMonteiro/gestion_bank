<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *   path="/monteiro.daisa/v1/clients",
     *   summary="Lister les clients",
     *   tags={"Clients"},
     *   @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", default=1)),
     *   @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=10, maximum=100)),
     *   @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *   @OA\Response(
     *     response=200,
     *     description="Liste paginée des clients",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *       @OA\Property(property="pagination", type="object",
     *         @OA\Property(property="current_page", type="integer"),
     *         @OA\Property(property="last_page", type="integer"),
     *         @OA\Property(property="per_page", type="integer"),
     *         @OA\Property(property="total", type="integer"),
     *         @OA\Property(property="next_page_url", type="string", nullable=true),
     *         @OA\Property(property="prev_page_url", type="string", nullable=true)
     *       )
     *     )
     *   )
     * )
     */
    public function index(Request $request)
    {
        $perPage = min($request->input('per_page', 10), 100); // Maximum 100 éléments par page
        $page = max(1, $request->input('page', 1));

        $query = Client::query();

        $query->when($request->filled('search'), function($q) use ($request) {
            $s = $request->string('search');
            $q->where(function($qq) use ($s) {
                $qq->where('nom', 'like', "%$s%")
                   ->orWhere('prenom', 'like', "%$s%")
                   ->orWhere('telephone', 'like', "%$s%")
                   ->orWhere('cni', 'like', "%$s%");
            });
        });

        // Exécution de la requête avec pagination
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        // Formatage des données de pagination
        $response = [
            'success' => true,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
            ]
        ];

        return response()->json($response);
    }
}
