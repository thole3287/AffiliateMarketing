<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Jobs\SendOrderCancelledEmailJob;
use App\Jobs\SendOrderEmail;
use App\Mail\OrderCancelled;
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
use App\Mail\OrderPlaced;
use Illuminate\Support\Facades\Mail;


class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('user', 'orderItems.product', 'orderItems.productVariation')->get();

        return response()->json([
            'orders' => $orders
        ]);
    }
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
            // 'user_id' => 'required|exists:users,id',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.variation_id' => 'nullable|exists:product_variations,id',
            'shipping_address' => 'required|string',
            'total_amount' => 'required|numeric',
            'ordered_at' =>  'nullable|date',
            'payment_method' => 'nullable|string',
            'payment_status' => 'nullable|string|in:paid,unpaid',
            'order_status' => 'nullable|string|in:ordered,confirmed,cancelled,shipping,completed',
        ]);
        $totalAmount = $request->total_amount;
        $originalTotalAmount = $totalAmount;
        // Check if coupon code is provided and valid
        $coupon = Coupon::where('coupon_code', $request->coupon_code)
            ->where('coupon_status', 1)
            ->where('expiration_date', '>', now())
            ->first();
        $discount = 0;
        if ($coupon) {
            $discount = $coupon->discount_amount;
            $totalAmount -= $discount;

            // You might want to handle negative totalAmount here if discount exceeds the total
            $totalAmount = max(0, $totalAmount);
        }
        // Calculate discount percentage
        $discountPercentage = ($originalTotalAmount > 0) ? ($discount / $originalTotalAmount) * 100 : 0;
        $order = Order::create([
            'user_id' => $request->user_id ?? null,
            'shipping_address' => $request->shipping_address,
            'total_amount' => $request->total_amount,
            'payment_method' => $request->payment_method,
            'payment_status' => $request->payment_status,
            'coupon_code' => $request->coupon_code ?? null,
            'order_date' => now(),
            'order_status' => $request->order_status,
            'note' =>  $request->note ?? null
        ]);
        $order = Order::with(['user', 'orderItems'])->find($order->id);
        $orderData = [];
        $subtotal = 0;

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
            // dd($variation);
            $orderItem = new OrderItems();
            $orderItem->order_id = $order->id;
            $orderItem->product_id =  $product1->id;
            $orderItem->variation_id = $variation?->id;
            $orderItem->quantity = $product['quantity'];
            // $orderItem->product_price = $product1->product_price;
            $orderItem->product_price = $productPrice;

            $orderItem->save();
            $orderData[] = OrderItems::with(['product', 'productVariation'])->find($orderItem->id);
            // $orderData[] = $orderItem;
            $subtotal += $productPrice * $product['quantity'];

            if ($request->filled('referral_user_id')) {
                $referral = Referral::where('user_id', $request->referral_user_id)
                    ->where('product_id', $product1->id)
                    ->where('order_id', $order->id)
                    ->first();

                $commissionAmount = $productPrice * ($product1->commission_percentage / 100) * $product['quantity'];

                if ($referral) {
                    // Update existing referral
                    $referral->commission_amount += $commissionAmount;
                    $referral->save();
                } else {
                    // Create new referral
                    $referral = new Referral([
                        'user_id' => $request->referral_user_id,
                        'product_id' => $product1->id,
                        'order_id' => $order->id,
                        'commission_percentage' => $product1->commission_percentage,
                        'commission_amount' => $commissionAmount,
                    ]);
                    $referral->save();
                }

                // Update total_commission for the user
                $user = User::find($request->referral_user_id);
                $user->total_commission = ($user->total_commission ?? 0) + $commissionAmount;
                $user->save();
            }
        }
        // dd($subtotal,  $discount, $discountPercentage);
        // Send email
        // $user = User::find($request->user_id);
        // if (!empty($user->email)) {
        //     // dispatch(new SendOrderEmail($order, $orderData,  $discount, $subtotal, $discountPercentage, $user->email));
        //     Mail::to($user->email)->send(new OrderPlaced($order, $orderData, $discount, $subtotal, $discountPercentage));
        // }

        return response()->json([
            'message' => 'Order placed successfully',
            'order' => $order,
            'order_items ' =>  $orderData,
            'subtotal' => $subtotal,
            'discount' => $totalAmount,
        ], Response::HTTP_CREATED);
    }

    public function updateOrderStatus(Request $request, $orderId)
    {
        // Validate the incoming request
        $request->validate([
            'payment_status' => 'nullable|string|in:paid,unpaid',
            'order_status' => 'nullable|string|in:ordered,confirmed,cancelled,shipping,completed',
        ]);

        // Find the order by ID
        $order = Order::findOrFail($orderId);

        // Update the order status and payment status
        $order->payment_status = $request->input('payment_status');
        $order->order_status = $request->input('order_status');
        $order->save();

        // Return a success response
        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order,
        ], Response::HTTP_OK);
    }
    public function cancelOrder(Request $request, Order $order)
    {
        // Kiểm tra xem đơn hàng đã được thanh toán hay chưa
        if ($order->payment_status === 'paid') {
            // Xử lý logic hủy đơn hàng đã thanh toán (nếu cần)
            return response()->json(['message' => 'Đơn hàng đã được thanh toán, không thể hủy.'], 400);
        }

        if ($order->payment_status === 'shipping') {
            return response()->json(['message' => 'Đơn hàng đã được vận chuyển, không thể hủy.'], 400);
        }

        // Cập nhật trạng thái đơn hàng thành "cancelled"
        $order->update(['payment_status' => 'cancelled']);

        // Gửi email thông báo hủy đơn hàng
        // $user = $order->user;
        // $user = User::find($order->user_id);

        // SendOrderCancelledEmailJob::dispatch($order, $user->email);

        // Mail::to($user->email)->send(new OrderCancelled($order));

        return response()->json(['message' => 'Đơn hàng đã được hủy thành công.']);
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

    /**
     * Get top selling products
     *
     * @param int $limit
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTopSellingProducts($limit = 10)
    {
        $topProducts = OrderItems::selectRaw('product_id, sum(quantity) as total_quantity')
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->with('product')
            ->get();

        return response()->json([
            'top_selling_products' => $topProducts,
        ]);
    }

    /**
     * Get related products for a given product
     *
     * @param int $productId
     * @param int $limit
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRelatedProducts($productId, $limit = 10)
    {
        $product = Product::findOrFail($productId);
        $categoryId = $product->category_id;

        $relatedProducts = Product::where('category_id', $categoryId)
            ->where('id', '!=', $productId)
            ->limit($limit)
            ->get();

        return response()->json([
            'related_products' => $relatedProducts,
        ]);
    }
}
