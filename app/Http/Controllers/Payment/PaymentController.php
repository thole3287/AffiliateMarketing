<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function vnpayReturn(Request $request)
    {
        $vnpayData = $request->all();

        // Kiểm tra tính hợp lệ của dữ liệu VNPay
        if ($this->isValidVNPayResponse($vnpayData)) {
            Log::info('Order updated successfully: ', ['data' => $vnpayData]);
            // dd($vnpayData);
            // Cập nhật trạng thái đơn hàng
            // $order = Order::where('order_id', $vnpayData['vnp_TxnRef'])->first();
            // if ($order) {
            //     $order->status = 'paid';
            //     $order->save();
            // }

            return response()->json([
                'success' => true,
                // 'order' => $order,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid VNPay response',
        ], 400);
    }

    private function isValidVNPayResponse($vnpayData)
    {
        $hashSecret = env('VNPAY_HASH_SECRET');
        $vnpSecureHash = $vnpayData['vnp_SecureHash'];
        unset($vnpayData['vnp_SecureHash']);
        unset($vnpayData['vnp_SecureHashType']);
        ksort($vnpayData);
        $hashData = '';
        foreach ($vnpayData as $key => $value) {
            $hashData .= $key . '=' . $value . '&';
        }
        $hashData = rtrim($hashData, '&');

        $secureHash = hash('sha256', $hashSecret . $hashData);

        return $secureHash === $vnpSecureHash;
    }
}
