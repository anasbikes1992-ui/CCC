<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

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
        $query = SupportTicket::query()
            ->with([
                'user:id,name,phone',
                'parcel:id,parcel_number',
                'assignedTo:id,name',
                'latestMessage.sender:id,name',
            ])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assigned_to')) {
            $value = $request->assigned_to;
            if ($value === 'me') {
                $query->where('assigned_to', Auth::id());
            } elseif ($value === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $value);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'ilike', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'ilike', "%{$search}%"));
            });
        }

        $tickets = $query->paginate($request->integer('limit', 30));

        return response()->json([
            'success' => true,
            'data'    => $tickets->items(),
            'meta'    => [
                'total'     => $tickets->total(),
                'page'      => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $ticket = SupportTicket::with([
            'user:id,name,phone,email',
            'parcel:id,parcel_number,status',
            'assignedTo:id,name',
            'messages.sender:id,name',
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $ticket, 'error' => null]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $ticket = SupportTicket::findOrFail($id);

        $validated = $request->validate([
            'status'      => ['sometimes', Rule::in(['open', 'pending', 'in_progress', 'resolved', 'closed'])],
            'priority'    => ['sometimes', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'assigned_to' => ['sometimes', 'nullable', 'uuid', 'exists:users,id'],
        ]);

        if (isset($validated['status']) && in_array($validated['status'], ['resolved', 'closed'], true)) {
            $validated['resolved_at'] = now();
        }

        $ticket->update($validated);

        return response()->json([
            'success' => true,
            'data'    => $ticket->fresh(['user:id,name', 'assignedTo:id,name']),
            'error'   => null,
        ]);
    }

    /**
     * Admin / support agent replies to a ticket thread.
     */
    public function reply(Request $request, string $id): JsonResponse
    {
        $ticket = SupportTicket::findOrFail($id);

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:5000'],
        ]);

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_id' => Auth::id(),
            'body'      => $validated['body'],
        ]);

        // Flip to pending (waiting on customer) unless already resolved/closed
        if (! in_array($ticket->status, ['resolved', 'closed'], true)) {
            $ticket->update(['status' => 'pending']);
        }

        return response()->json([
            'success' => true,
            'data'    => $message->load('sender:id,name'),
            'error'   => null,
        ], 201);
    }
}
