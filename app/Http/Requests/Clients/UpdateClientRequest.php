<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use App\Models\Client;
use App\Services\Tenancy\TenantManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $companyId = app(TenantManager::class)->idOrFail();
        /** @var Client|int|string $client */
        $client = $this->route('client');
        $clientId = $client instanceof Client ? $client->id : $client;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('clients', 'email')
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at')
                    ->ignore($clientId),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'billing_address' => ['nullable', 'string', 'max:1000'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'send_reminders' => ['boolean'],
        ];
    }
}
