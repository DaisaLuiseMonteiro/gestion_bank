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
}
