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
        // // Log toàn bộ dữ liệu nhận được từ callback

        $data = $request->input('data');
        $orderId = $data['orderId'];
        $resultCode = $data['resultCode'];
        $mac = $request->input('mac');
        $overallMac = $request->input('overallMac');

        $privateKey = env('VNPAY_HASH_SECRET');
        Log::info('VNPay Callback Data:', $request->all());

        // Kiểm tra tính hợp lệ của dữ liệu giao dịch
        if ($this->isValidMac($data, $mac, $privateKey) && $this->isValidOverallMac($request->all(), $overallMac, $privateKey)) {
            try {
                // Tìm đơn hàng với zalo_order_id = orderId
                $order = Order::where('zalo_order_id', $orderId)->first();

                // Nếu đơn hàng không tồn tại
                if (!$order) {
                    return response()->json(['message' => 'Order not found'], 404);
                }

                // Nếu resultCode = 1, cập nhật trạng thái đơn hàng
                if ($resultCode == 1) {
                    $order->payment_method = 'paid';
                    $order->save();
                }

                return response()->json(['message' => 'Order updated successfully']);
            } catch (\Exception $e) {
                // Ghi lại lỗi nếu có
                Log::error('Error updating order: ' . $e->getMessage());
                return response()->json(['message' => 'An error occurred while updating the order'], 500);
            }
        }

        // Log::error('Invalid VNPay response:', $request->all());
        return response()->json([
            'success' => false,
            'message' => 'Invalid VNPay response',
        ], 400);
        // Lấy dữ liệu từ request
        //  $data = $request->input('data');
        //  $orderId = $data['orderId'];
        //  $resultCode = $data['resultCode'];
        //  Log::info('VNPay Callback Data:', $request->all());

        //  try {
        //      // Tìm đơn hàng với zalo_order_id = orderId
        //      $order = Order::where('zalo_order_id', $orderId)->first();

        //      // Nếu đơn hàng không tồn tại
        //      if (!$order) {
        //          return response()->json(['message' => 'Order not found'], 404);
        //      }

        //      // Nếu resultCode = 1, cập nhật trạng thái đơn hàng
        //      if ($resultCode == 1) {
        //          $order->payment_method = 'paid';
        //          $order->save();
        //      }

        //      return response()->json(['message' => 'Order updated successfully']);
        //  } catch (\Exception $e) {
        //      // Ghi lại lỗi nếu có
        //      Log::error('Error updating order: ' . $e->getMessage());
        //      return response()->json(['message' => 'An error occurred while updating the order'], 500);
        //  }
    }

    private function isValidMac($data, $mac, $privateKey)
    {
        $dataForMac = "appId={$data['appId']}&amount={$data['amount']}&description={$data['description']}&orderId={$data['orderId']}&resultCode={$data['resultCode']}&transId={$data['transId']}";
        $reqMac = hash_hmac('sha256', $dataForMac, $privateKey);
        return $reqMac === $mac;
    }

    private function isValidOverallMac($data, $overallMac, $privateKey)
    {
        unset($data['overallMac']);
        $dataOverallMac = collect($data)
            ->sortKeys()
            ->map(function ($value, $key) {
                return "{$key}={$value}";
            })
            ->implode('&');

        $reqOverallMac = hash_hmac('sha256', $dataOverallMac, $privateKey);
        return $reqOverallMac === $overallMac;
    }
}
