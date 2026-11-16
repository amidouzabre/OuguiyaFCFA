<?php

namespace App\Models;

use App\Observers\TransactionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([TransactionObserver::class])]
class Transaction extends Model
{
    /** @use HasFactory<Factory<Transaction>> */
    use HasFactory;

    protected $fillable = [
        'client_id',
        'purchase_source_id',
        'date_operation',
        'numero_operation',
        'type_operation',
        'description',
        'ouguiya',
        'payement_ouguiya',
        'solde_ouguiya_ligne',
        'fcfa',
        'payement_fcfa',
        'solde_fcfa_ligne',
        'rate',
        'taux_achat',
        'gain',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_operation' => 'date',
            'numero_operation' => 'integer',
            'ouguiya' => 'decimal:2',
            'payement_ouguiya' => 'decimal:2',
            'solde_ouguiya_ligne' => 'decimal:2',
            'fcfa' => 'decimal:2',
            'payement_fcfa' => 'decimal:2',
            'solde_fcfa_ligne' => 'decimal:2',
            'rate' => 'decimal:4',
            'taux_achat' => 'decimal:4',
            'gain' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<Transaction, $this> */
    public function purchaseSource(): BelongsTo
    {
        return $this->belongsTo(self::class, 'purchase_source_id');
    }

    /** @return HasMany<Transaction, $this> */
    public function sales(): HasMany
    {
        return $this->hasMany(self::class, 'purchase_source_id');
    }
}
