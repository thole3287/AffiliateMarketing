<?php

namespace App\Http\Controllers\Affilinates;

use App\Http\Controllers\Controller;
use App\Models\TicketReply;
use App\Models\WithdrawalTicket;
use Illuminate\Http\Request;

class TicketReplyController extends Controller
{
    // Phương thức tạo trả lời cho ticket
    public function store(Request $request, $ticketId)
    {
        // Validate request
        $request->validate([
            'message' => 'required|string',
        ]);

        $ticket = WithdrawalTicket::find($ticketId);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        // Tạo trả lời mới
        $reply = TicketReply::create([
            'ticket_id' => $ticketId,
            'user_id' => $request->input('user_id'), // Lấy ID người dùng hiện tại
            'message' => $request->input('message'),
        ]);

        return response()->json(['message' => 'Reply created successfully', 'reply' => $reply], 201);
    }

    // Phương thức lấy danh sách tất cả các trả lời của ticket
    public function index($ticketId)
    {
        $ticket = WithdrawalTicket::find($ticketId);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        $replies = TicketReply::where('ticket_id', $ticketId)->with('user')->get();

        return response()->json($replies);
    }
}
