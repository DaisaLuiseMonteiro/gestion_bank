<?php

namespace App\Services;

use App\Models\Compte;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CompteService
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = Compte::query();

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        } else {
            $query->where('statut', 'actif'); // Par défaut, on ne montre que les comptes actifs
        }
        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($qq) use ($s) {
                $qq->where('titulaire', 'like', "%$s%")
                   ->orWhere('numeroCompte', 'like', "%$s%");
            });
        }

        $sort = $filters['sort'] ?? 'created_at';
        $order = $filters['order'] ?? 'desc';
        $query->orderBy($sort, $order);

        // Par défaut, on affiche 5 comptes par page
        $perPage = $filters['limit'] ?? 5;
        $page = $filters['page'] ?? 1;
        
        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
