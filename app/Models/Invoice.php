<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Support\Concerns\BelongsToCompany;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'client_id',
        'number',
        'status',
        'issue_date',
        'due_date',
        'currency',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'amount_paid',
        'notes',
        'public_token',
        'sent_at',
        'paid_at',
        'voided_at',
        'void_reason',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'invoice_number',
        'amount_due',
    ];

    public function getInvoiceNumberAttribute(): string
    {
        return $this->number;
    }

    public function getAmountDueAttribute(): int
    {
        return $this->balance();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'tax_total' => 'integer',
            'total' => 'integer',
            'amount_paid' => 'integer',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('position');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderByDesc('paid_at');
    }

    /**
     * @return HasMany<InvoiceReminder, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(InvoiceReminder::class)->orderBy('days_overdue');
    }

    public function balance(): int
    {
        return max(0, $this->total - $this->amount_paid);
    }

    public function isDraft(): bool
    {
        return $this->status === InvoiceStatus::DRAFT;
    }

    public function isSent(): bool
    {
        return $this->status === InvoiceStatus::SENT;
    }

    public function isPaid(): bool
    {
        return $this->status === InvoiceStatus::PAID;
    }

    public function isVoid(): bool
    {
        return $this->status === InvoiceStatus::VOID;
    }

    public function isOverdue(): bool
    {
        return $this->status === InvoiceStatus::OVERDUE;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $needle = "%{$term}%";

        return $query->where(function (Builder $q) use ($needle): void {
            $q->where('number', 'ILIKE', $needle)
                ->orWhereHas('client', function (Builder $clientQuery) use ($needle): void {
                    $clientQuery->where('name', 'ILIKE', $needle);
                });
        });
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeStatus(Builder $query, InvoiceStatus|string|null $status): Builder
    {
        if (! $status) {
            return $query;
        }

        $value = $status instanceof InvoiceStatus ? $status->value : $status;

        return $query->where('status', $value);
    }
}
