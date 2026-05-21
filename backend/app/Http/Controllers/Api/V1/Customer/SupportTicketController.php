<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SupportTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tickets = SupportTicket::query()
            ->where('user_id', Auth::id())
            ->with(['parcel:id,parcel_number', 'latestMessage'])
            ->latest()
            ->paginate($request->integer('limit', 20));

        return response()->json([
            'success' => true,
            'data'    => $tickets->items(),
            'meta'    => [
                'total'     => $tickets->total(),
                'page'      => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page'  => $tickets->perPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject'    => ['required', 'string', 'max:255'],
            'parcel_id'  => ['nullable', 'uuid', 'exists:parcels,id'],
            'body'       => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $ticket = SupportTicket::create([
            'user_id'   => Auth::id(),
            'parcel_id' => $validated['parcel_id'] ?? null,
            'subject'   => $validated['subject'],
            'status'    => 'open',
            'priority'  => 'medium',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_id' => Auth::id(),
            'body'      => $validated['body'],
        ]);

        return response()->json([
            'success' => true,
            'data'    => $ticket->load(['messages.sender:id,name', 'parcel:id,parcel_number']),
            'error'   => null,
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $ticket = SupportTicket::query()
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->with(['messages.sender:id,name', 'parcel:id,parcel_number', 'assignedTo:id,name'])
            ->firstOrFail();

        return response()->json(['success' => true, 'data' => $ticket, 'error' => null]);
    }

    /**
     * Customer replies to an existing ticket.
     */
    public function reply(Request $request, string $id): JsonResponse
    {
        $ticket = SupportTicket::query()
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->whereNotIn('status', ['resolved', 'closed'])
            ->firstOrFail();

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:5000'],
        ]);

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_id' => Auth::id(),
            'body'      => $validated['body'],
        ]);

        // Re-open if it was pending customer reply
        if ($ticket->status === 'pending') {
            $ticket->update(['status' => 'in_progress']);
        }

        return response()->json([
            'success' => true,
            'data'    => $message->load('sender:id,name'),
            'error'   => null,
        ], 201);
    }
}
