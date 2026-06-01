<?php

namespace App\Models;

use App\Models\Concerns\MappeAnciensAttributs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Ce modele represente un client.
class Client extends Model
{
    use MappeAnciensAttributs;

    protected array $mappageAnciensAttributs = [
        'name' => 'nom',
        'post_name' => 'postnom',
        'phone' => 'telephone',
        'address' => 'adresse',
        'company' => 'entreprise',
    ];

    protected $fillable = [
        'name',
        'post_name',
        'email',
        'phone',
        'address',
        'company',
    ];

    // Un client peut avoir plusieurs factures.
    public function factures(): HasMany
    {
        return $this->hasMany(Facture::class, 'client_id');
    }
}
