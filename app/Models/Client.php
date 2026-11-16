<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'telephone',
        'address',
        'notes',
        'solde_ouguiya',
        'solde_fcfa',
    ];

    protected function casts(): array
    {
        return [
            'solde_ouguiya' => 'decimal:2',
            'solde_fcfa' => 'decimal:2',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->orderBy('date_operation', 'asc')->orderBy('id', 'asc');
    }

    public function latestTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->orderBy('date_operation', 'desc')->orderBy('id', 'desc');
    }
}
