<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Concerns\BelongsToCompany;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'billing_address',
        'tax_number',
        'send_reminders',
    ];

    /**
     * @return HasMany<Invoice, self>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'send_reminders' => 'boolean',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        $term = "%{$search}%";

        return $query->where(function (Builder $q) use ($term): void {
            $q->where('name', 'ILIKE', $term)
                ->orWhere('email', 'ILIKE', $term)
                ->orWhere('phone', 'ILIKE', $term);
        });
    }
}
