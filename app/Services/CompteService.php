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
        }
        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($qq) use ($s) {
                $qq->where('titulaire', 'like', "%$s%")
                   ->orWhere('numeroCompte', 'like', "%$s%");
            });
        }

        $query->orderBy($filters['sort'], $filters['order']);

        return $query->paginate($filters['limit'], ['*'], 'page', $filters['page']);
    }
}
