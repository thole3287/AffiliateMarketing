<?php

namespace App\Http\Controllers\Affilinates;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\User;
use App\Models\WithdrawalTicket;
use Illuminate\Http\Request;

class WithdrawalTicketController extends Controller
{
    // Phương thức tạo ticket rút tiền
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'note' => 'nullable|string',
        ]);

        $ticket = WithdrawalTicket::create([
            'user_id' => $request->input('user_id'),
            'total_commission' => User::where('id', $request->input('user_id'))->pluck('total_commission')->first(),
            'amount' => $request->input('amount'),
            'bank_name' => $request->input('bank_name'),
            'account_number' => $request->input('account_number'),
            'note' => $request->input('note') ?? null,
        ]);

        return response()->json(['message' => 'Ticket created successfully', 'ticket' => $ticket], 201);
    }

    // Phương thức lấy danh sách tất cả các ticket rút tiền
    public function index()
    {
        $tickets = WithdrawalTicket::with(['user', 'replies', 'userCommission'])->get();

        $totalTickets = WithdrawalTicket::count();
        $totalCancelled = WithdrawalTicket::where('status', 'cancelled')->count();
        $totalPending = WithdrawalTicket::where('status', 'pending')->count();
        $totalCompleted = WithdrawalTicket::where('status', 'completed')->count();

        return response()->json([
            'totalTickets' => $totalTickets,
            'totalCancelled' => $totalCancelled,
            'totalPending' => $totalPending,
            'totalCompleted' => $totalCompleted,
            'tickets' => $tickets,
        ]);
    }

    // Phương thức cập nhật trạng thái của ticket
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,cancelled,completed',
        ]);

        $ticket = WithdrawalTicket::find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        $ticket->status = $request->input('status');
        $ticket->save();

        return response()->json(['message' => 'Ticket status updated successfully', 'ticket' => $ticket]);
    }
}
