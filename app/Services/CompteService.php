<?php

namespace App\Services;

use App\Models\Compte;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class CompteService
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = Compte::query();

        // Filtrage par type
        if (!empty($filters['type'])) {
            $type = strtolower($filters['type']);
            if (in_array($type, ['cheque', 'epargne'])) {
                $query->where('type', $type);
            }
        }
        
        // Filtrage par statut
        if (!empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        } else {
            $query->where('statut', 'actif');
        }
        
        // Recherche
        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($qq) use ($s) {
                $qq->where('titulaire', 'like', "%$s%")
                   ->orWhere('numeroCompte', 'like', "%$s%");
            });
        }

        // Tri
        $sort = $filters['sort'] ?? 'created_at';
        $order = $filters['order'] ?? 'desc';
        $query->orderBy($sort, $order);

        // Pagination
        $perPage = $filters['limit'] ?? 5;
        $page = $filters['page'] ?? 1;
        
        // Log de débogage
        Log::info('Requête de filtrage des comptes', [
            'filtres' => $filters,
            'requete_sql' => $query->toSql(),
            'parametres' => $query->getBindings()
        ]);
        
        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
