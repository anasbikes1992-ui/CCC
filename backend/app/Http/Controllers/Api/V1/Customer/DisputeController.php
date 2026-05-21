<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\Parcel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DisputeController extends Controller
{
    /**
     * List the authenticated customer's disputes.
     */
    public function index(Request $request): JsonResponse
    {
        $disputes = Dispute::query()
            ->where('raised_by_user_id', Auth::id())
            ->with(['parcel:id,parcel_number,status'])
            ->latest()
            ->paginate($request->integer('limit', 20));

        return response()->json([
            'success' => true,
            'data'    => $disputes->items(),
            'meta'    => [
                'total'       => $disputes->total(),
                'page'        => $disputes->currentPage(),
                'last_page'   => $disputes->lastPage(),
                'per_page'    => $disputes->perPage(),
            ],
        ]);
    }

    /**
     * Raise a dispute on one of the customer's parcels.
     */
    public function store(Request $request, string $parcelId): JsonResponse
    {
        $parcel = Parcel::query()
            ->where('id', $parcelId)
            ->where('customer_id', Auth::id())
            ->firstOrFail();

        // Only allow one open dispute per parcel
        $existing = Dispute::query()
            ->where('parcel_id', $parcel->id)
            ->whereIn('status', ['open', 'under_review'])
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'error'   => [
                    'code'    => 'DISPUTE_ALREADY_OPEN',
                    'message' => 'An open dispute already exists for this parcel.',
                ],
            ], 422);
        }

        $validated = $request->validate([
            'type'        => ['required', Rule::in([
                'not_delivered', 'damaged', 'lost',
                'wrong_item', 'late_delivery', 'other',
            ])],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
        ]);

        $dispute = Dispute::create([
            'parcel_id'          => $parcel->id,
            'raised_by_user_id'  => Auth::id(),
            'type'               => $validated['type'],
            'description'        => $validated['description'],
            'status'             => 'open',
        ]);

        return response()->json([
            'success' => true,
            'data'    => $dispute->load('parcel:id,parcel_number,status'),
            'error'   => null,
        ], 201);
    }

    /**
     * View a single dispute (must belong to the authenticated customer).
     */
    public function show(string $id): JsonResponse
    {
        $dispute = Dispute::query()
            ->where('id', $id)
            ->where('raised_by_user_id', Auth::id())
            ->with(['parcel:id,parcel_number,status', 'resolvedBy:id,name'])
            ->firstOrFail();

        return response()->json(['success' => true, 'data' => $dispute, 'error' => null]);
    }
}
