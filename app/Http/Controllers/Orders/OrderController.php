<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\AffiliateLink;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\product\Product;
use App\Models\product\ProductVariation;
use App\Models\ProductOffer;
use App\Models\Referral;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


class OrderController extends Controller
{
    public function getOrders(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'nullable|in:pending,paid,failed',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1,max:100',
        ]);

        $query = Order::with(['orderItems', 'orderItems.product', 'orderItems.productVariation'])
            ->where('user_id', $request->user_id);

        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }

        $orders = $query->paginate($request->input('per_page', 10));
        if (!empty($orders->items())) {
            return response()->json([
                'orders' => $orders->items(),
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                ],
            ]);
        } else {
            return response()->json([
                'status' => false,
                'msg' => 'No orders were found for this user!!!'
            ], Response::HTTP_NOT_FOUND);
        }
    }
    public function placeOrder(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.variation_id' => 'nullable|exists:product_variations,id',
            'shipping_address' => 'required|string',
            'total_amount' => 'required|numeric',
            'ordered_at' =>  'nullable|date',
            'payment_method' => 'required|string',
            'payment_status' => 'required|string|in:pending,paid,failed',
        ]);
        $totalAmount = $request->total_amount;

        // Check if coupon code is provided and valid
        $coupon = Coupon::where('coupon_code', $request->coupon_code)
            ->where('coupon_status', 1)
            ->where('expiration_date', '>', now())
            ->first();

        if ($coupon) {
            $totalAmount -= $coupon->discount_amount;
            // You might want to handle negative totalAmount here if discount exceeds the total
            $totalAmount = max(0, $totalAmount);
        }
        $order = Order::create([
            'user_id' => $request->user_id,
            'shipping_address' => $request->shipping_address,
            'total_amount' => $request->total_amount,
            'payment_method' => $request->payment_method,
            'payment_status' => $request->payment_status,
            'coupon_code' => $request->coupon_code ?? null,
            'order_date' => now(),
            'note' =>  $request->note
        ]);
        $orderData = [];
        foreach ($request->products as $product) {
            $product1 = Product::findOrFail($product['product_id']);
            $variation = $product1->variations()->find((int)$product['variation_id']);

            $productPrice = $product1->product_price;

            // Check if product has any offer
            $productOffer = ProductOffer::where('offer_product_id', $product1->id)->first();

            // If product has offer, adjust product price accordingly
            if ($productOffer) {
                if ($productOffer->hot_deal) {
                    $productPrice -= $productPrice * ($productOffer->hot_deal / 100);
                }
                // Add more conditions for other types of offers if needed
            }

            $orderItem = new OrderItems();
            $orderItem->order_id = $order->id;
            $orderItem->product_id =  $product1->id;
            $orderItem->variation_id = $variation?->id;
            $orderItem->quantity = $product['quantity'];
            // $orderItem->product_price = $product1->product_price;
            $orderItem->product_price = $productPrice;

            $orderItem->save();
            $orderData[] = $orderItem;

            if ($request->filled('referral_user_id')) {
                $referral = new Referral([
                    'user_id' => $request->referral_user_id,
                    'product_id' => $product1->id,
                    'order_id' => $order->id,
                    'commission_percentage' => $product1->commission_percentage,
                    'commission_amount' => $productPrice * ($product1->commission_percentage / 100) * $product['quantity'],
                ]);
                $referral->save();

                // Cập nhật total_commission cho người dùng
                $user = User::find($request->referral_user_id);
                $user->total_commission = ($user->total_commission ?? 0) + $referral->commission_amount;
                $user->save();
            }
        }
        return response()->json([
            'message' => 'Order placed successfully',
            'order' => $order,
            'order_detail' =>  $orderData
        ], Response::HTTP_CREATED);
    }

    public function getOrderHistory(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'order_id' => 'nullable|exists:orders,id',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1,max:100',
        ]);

        $orders = Order::with(['user', 'orderItems', 'orderItems.product', 'orderItems.productVariation'])
            ->where('user_id', $request->user_id);

        if ($request->filled('order_id')) {
            $orders->where('id', $request->order_id);
        }

        $orders = $orders->orderByDesc('order_date')
            ->paginate($request->input('per_page', 10));

        if (!empty($orders->items())) {
            return response()->json([

                'orders' => $orders->items(),
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                ],
            ]);
        } else {
            return response()->json([
                'status' => false,
                'msg' => 'No orders were found for this user!!!'
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
