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
     *   @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Liste des clients")
     * )
     */
    // GET monteiro.daisa/v1/clients
    public function index(Request $request)
    {
        $query = Client::query();

        $query->when($request->filled('search'), function($q) use ($request){
            $s = $request->string('search');
            $q->where(function($qq) use ($s){
                $qq->where('nom','like',"%$s%")
                   ->orWhere('prenom','like',"%$s%")
                   ->orWhere('telephone','like',"%$s%")
                   ->orWhere('cni','like',"%$s%");
            });
        });
  
        $clients = $query->limit(100)->get();

        $data = $clients->map(function($c){
            return [
                'id' => $c->id,
                'nom' => $c->nom,
                'prenom' => $c->prenom,
                'telephone' => $c->telephone,
                'cni' => $c->cni,
                'statut' => $c->statut,
            ];
        });

        return $this->successResponse($data);
    }

    /**
     * @OA\Get(
     *   path="/monteiro.daisa/v1/clients/telephone/{telephone}",
     *   summary="Récupérer un client par son numéro de téléphone",
     *   tags={"Clients"},
     *   @OA\Parameter(
     *     name="telephone",
     *     in="path",
     *     required=true,
     *     description="Numéro de téléphone du client",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Client trouvé",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="data", type="object",
     *         @OA\Property(property="id", type="string", format="uuid"),
     *         @OA\Property(property="nom", type="string"),
     *         @OA\Property(property="prenom", type="string"),
     *         @OA\Property(property="telephone", type="string"),
     *         @OA\Property(property="cni", type="string"),
     *         @OA\Property(property="statut", type="string"),
     *         @OA\Property(property="nombre_comptes", type="integer", description="Nombre de comptes liés au client")
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Client non trouvé",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Client non trouvé")
     *     )
     *   )
     * )
     */
    // GET monteiro.daisa/v1/clients/telephone/{telephone}
    public function showByPhone(string $telephone)
    {
        $client = Client::withCount('comptes')->where('telephone', $telephone)->first();
        
        if (!$client) {
            return $this->errorResponse('Client non trouvé', 404);
        }

        $data = [
            'id' => $client->id,
            'nom' => $client->nom,
            'prenom' => $client->prenom,
            'telephone' => $client->telephone,
            'cni' => $client->cni,
            'statut' => $client->statut,
            'nombre_comptes' => $client->comptes_count,
        ];

        return $this->successResponse($data);
    }
}
