<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\AiException;
use App\Models\Invoice;
use App\Services\Ai\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiInvoiceController extends Controller
{
    /**
     * Parse natural language text into an unsaved invoice draft.
     */
    public function __invoke(Request $request, AiService $aiService): JsonResponse
    {
        $this->authorize('create', Invoice::class);

        $request->validate([
            'prompt' => ['required', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $companyId = (int) $user->current_company_id;

        try {
            $draft = $aiService->generateInvoiceDraft(
                companyId: $companyId,
                userId: $user->id,
                prompt: $request->input('prompt'),
            );

            return response()->json([
                'success' => true,
                'draft' => $draft,
            ]);
        } catch (AiException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
