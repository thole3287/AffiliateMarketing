<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function vnpayReturn(Request $request)
    {
        // Log toàn bộ dữ liệu nhận được từ callback
        Log::info('VNPay Callback Data:', $request->all());

        $data = $request->input('data');
        $mac = $request->input('mac');
        // $overallMac = $request->input('overallMac');

        $privateKey = env('VNPAY_HASH_SECRET');
        Log::info('VNPay Callback Data:', $request->all());

        // Kiểm tra tính hợp lệ của dữ liệu giao dịch
        if ($this->isValidMac($data, $mac, $privateKey)) {
            // Cập nhật trạng thái đơn hàng
            // $order = Order::where('order_id', $data['orderId'])->first();
            // if ($order) {
            //     $order->status = 'paid';
            //     $order->save();
            //     Log::info('Order updated successfully:', ['order_id' => $order->id]);
            // }
            Log::info('VNPay Callback Data:', $request->all());

            return response()->json([
                'success' => true,
                'order' => 'successss',
            ]);
        }

        Log::error('Invalid VNPay response:', $request->all());
        return response()->json([
            'success' => false,
            'message' => 'Invalid VNPay response',
        ], 400);
    }

    private function isValidMac($data, $mac, $privateKey)
    {
        $dataForMac = "appId={$data['appId']}&amount={$data['amount']}&description={$data['description']}&orderId={$data['orderId']}&message={$data['message']}&resultCode={$data['resultCode']}&transId={$data['transId']}";
        $reqMac = hash_hmac('sha256', $dataForMac, $privateKey);
        return $reqMac === $mac;
    }

    private function isValidOverallMac($data, $overallMac, $privateKey)
    {
        unset($data['overallMac']);
        $dataOverallMac = collect($data)
            ->sortKeys()
            ->map(function($value, $key) {
                return "{$key}={$value}";
            })
            ->implode('&');

        $reqOverallMac = hash_hmac('sha256', $dataOverallMac, $privateKey);
        return $reqOverallMac === $overallMac;
    }
}
