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

       // Nhóm referrals theo user
       $groupedReferrals = $referrals->groupBy('user_id');

       // Khởi tạo mảng để lưu kết quả đã tổng hợp
       $aggregatedData = [];

       // Khởi tạo các biến để lưu trữ tổng số liệu
       $totalCommissionAmountAllUsers = 0;
       $paidOrdersCountAllUsers = 0;
       $unpaidOrdersCountAllUsers = 0;

       foreach ($groupedReferrals as $userId => $userReferrals) {
           $totalCommissionAmount = $userReferrals->sum('commission_amount');
           $paidOrdersCount = $userReferrals->filter(function ($referral) {
               return $referral->order && $referral->order->payment_status == 'paid';
           })->count();
           $unpaidOrdersCount = $userReferrals->filter(function ($referral) {
               return $referral->order && $referral->order->payment_status != 'paid';
           })->count();

           $user = $userReferrals->first()->user; // Lấy thông tin user từ referral đầu tiên trong nhóm

           // Tổng hợp các products và orders
           $products = $userReferrals->map(function ($referral) {
               return $referral->product;
           })->unique('id')->values();

           $orders = $userReferrals->map(function ($referral) {
               return $referral->order;
           })->unique('id')->values();

           $aggregatedData[] = [
               'user' => $user,
               'totalCommissionAmount' => $totalCommissionAmount,
               'paidOrdersCount' => $paidOrdersCount,
               'unpaidOrdersCount' => $unpaidOrdersCount,
               'products' => $products,
               'orders' => $orders,
           ];

           // Cộng dồn số liệu vào các biến tổng
           $totalCommissionAmountAllUsers += $totalCommissionAmount;
           $paidOrdersCountAllUsers += $paidOrdersCount;
           $unpaidOrdersCountAllUsers += $unpaidOrdersCount;
       }

       // Tổng số referrals theo user_id
       $totalReferrals = $groupedReferrals->count();

       // Trả về dữ liệu dưới dạng JSON
       return response()->json([
           'totalReferrals' => $totalReferrals,
           'totalCommissionAmount' => $totalCommissionAmountAllUsers,
           'paidOrdersCount' => $paidOrdersCountAllUsers,
           'unpaidOrdersCount' => $unpaidOrdersCountAllUsers,
           'aggregatedData' => $aggregatedData,
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
