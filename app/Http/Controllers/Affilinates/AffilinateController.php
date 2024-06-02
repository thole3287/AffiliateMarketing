<?php

namespace App\Http\Controllers\Affilinates;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use Illuminate\Http\Request;

class AffilinateController extends Controller
{
    public function index()
    {
       // Lấy tất cả referrals và load các quan hệ
       $referrals = Referral::with(['user', 'product', 'order'])->get();
       $totalReferrals = $referrals->count();

       // Tính tổng doanh thu từ cộng tác viên
       $totalCommissionAmount = $referrals->sum('commission_amount');

       // Tính số đơn hàng đã thanh toán và chưa thanh toán
       $paidOrdersCount = $referrals->filter(function ($referral) {
           return $referral->order && $referral->order->payment_status == 'paid';
       })->count();

       $unpaidOrdersCount = $referrals->filter(function ($referral) {
           return $referral->order && $referral->order->payment_status != 'paid';
       })->count();

       // Trả về dữ liệu dưới dạng JSON
       return response()->json([
           'totalReferrals' => $totalReferrals,
           'totalCommissionAmount' => $totalCommissionAmount,
           'paidOrdersCount' => $paidOrdersCount,
           'unpaidOrdersCount' => $unpaidOrdersCount,
           'data' => $referrals,
       ]);
    }

    public function show($id)
    {
        $referral = Referral::with(['user', 'product', 'order'])->find($id);

        if (!$referral) {
            return response()->json(['message' => 'Referral not found'], 404);
        }

        return response()->json($referral);
    }

    // Phương thức cập nhật trạng thái của referral
    public function updateStatus(Request $request, $id)
    {
        // Validate request
        $request->validate([
            'status' => 'required|string'
        ]);

        // Find referral by id
        $referral = Referral::find($id);

        if (!$referral) {
            return response()->json(['message' => 'Referral not found'], 404);
        }

        // Update status
        $referral->status = $request->input('status');
        $referral->save();

        return response()->json(['message' => 'Referral status updated successfully', 'referral' => $referral]);
    }
}
