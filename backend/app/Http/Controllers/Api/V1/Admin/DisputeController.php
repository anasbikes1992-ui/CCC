<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DisputeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Dispute::query()
            ->with([
                'parcel:id,parcel_number,status',
                'raisedBy:id,name,phone',
                'resolvedBy:id,name',
            ])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $query->whereHas('parcel', fn ($q) =>
                $q->where('parcel_number', 'ilike', '%' . $request->search . '%')
            );
        }

        $disputes = $query->paginate($request->integer('limit', 30));

        return response()->json([
            'success' => true,
            'data'    => $disputes->items(),
            'meta'    => [
                'total'     => $disputes->total(),
                'page'      => $disputes->currentPage(),
                'last_page' => $disputes->lastPage(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $dispute = Dispute::with([
            'parcel.customer:id,name,phone',
            'raisedBy:id,name,phone',
            'resolvedBy:id,name',
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $dispute, 'error' => null]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $dispute = Dispute::findOrFail($id);

        $validated = $request->validate([
            'status'     => ['required', Rule::in([
                'open', 'under_review', 'resolved', 'rejected', 'closed',
            ])],
            'resolution' => ['required_if:status,resolved,rejected', 'nullable', 'string', 'max:2000'],
        ]);

        $data = ['status' => $validated['status']];

        if (in_array($validated['status'], ['resolved', 'rejected'], true)) {
            $data['resolved_by']  = Auth::id();
            $data['resolution']   = $validated['resolution'];
            $data['resolved_at']  = now();
        }

        $dispute->update($data);

        return response()->json([
            'success' => true,
            'data'    => $dispute->fresh(['raisedBy:id,name', 'resolvedBy:id,name']),
            'error'   => null,
        ]);
    }
}
