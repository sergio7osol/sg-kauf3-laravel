<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLabelRequest;
use App\Http\Requests\UpdateLabelRequest;
use App\Http\Requests\SyncPurchaseLabelsRequest;
use App\Models\Label;
use App\Models\Purchase;
use App\Support\CaseConverter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    /**
     * Display a listing of the user's labels.
     */
    public function index(Request $request): JsonResponse
    {
        $labels = Label::where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get()
            ->map(fn(Label $label) => $label->toData()->toArray())
            ->values();

        return response()->json([
            'data' => $labels,
            'meta' => [
                'count' => $labels->count(),
            ],
        ]);
    }

    /**
     * Store a newly created label.
     */
    public function store(StoreLabelRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $data = CaseConverter::toSnakeCase($validated);

        $label = Label::create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        return response()->json([
            'data' => $label->toData()->toArray(),
            'message' => 'Label created successfully.',
        ], 201);
    }

    /**
     * Display the specified label.
     */
    public function show(Request $request, Label $label): JsonResponse
    {
        $this->authorize('view', $label);

        return response()->json([
            'data' => $label->toData()->toArray(),
        ]);
    }

    /**
     * Update the specified label.
     */
    public function update(UpdateLabelRequest $request, Label $label): JsonResponse
    {
        $this->authorize('update', $label);

        $validated = $request->validated();
        $data = CaseConverter::toSnakeCase($validated);

        $updateData = [];
        if (array_key_exists('name', $data)) {
            $updateData['name'] = $data['name'];
        }
        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'];
        }

        if (!empty($updateData)) {
            $label->update($updateData);
        }

        return response()->json([
            'data' => $label->toData()->toArray(),
            'message' => 'Label updated successfully.',
        ]);
    }

    /**
     * Remove the specified label.
     */
    public function destroy(Request $request, Label $label): JsonResponse
    {
        $this->authorize('delete', $label);

        $label->delete();

        return response()->json([
            'message' => 'Label deleted successfully.',
        ]);
    }

    /**
     * Sync labels for a purchase.
     * Replaces all existing labels with the provided set.
     */
    public function syncPurchaseLabels(SyncPurchaseLabelsRequest $request, Purchase $purchase): JsonResponse
    {
        // Ensure the purchase belongs to the authenticated user
        if ($purchase->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Purchase not found.',
            ], 404);
        }

        $validated = $request->validated();
        $labelIds = $validated['labelIds'] ?? [];

        // Sync the labels (replaces existing pivot records)
        $purchase->labels()->sync($labelIds);

        // Reload labels for response
        $purchase->load('labels');

        return response()->json([
            'data' => $purchase->labels->map(fn($label) => $label->toData()->toArray())->values(),
            'message' => 'Purchase labels updated successfully.',
        ]);
    }
}
