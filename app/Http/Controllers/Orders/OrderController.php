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
use App\Models\UserCommission;
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
            'coupon_code' => 'nullable|string|exists:coupons,coupon_code',
            'checkoutArray' => 'required|array',
            'checkoutArray.*.productid' => 'required|exists:products,id',
            'checkoutArray.*.variantid' => 'nullable|exists:product_variations,id',
            'checkoutArray.*.referral_user_id' => 'required|exists:users,id',
            'checkoutArray.*.quantity' => 'required|integer|min:1',
        ]);

        $totalAmount = $request->total_amount;
        $originalTotalAmount = $totalAmount;

        $discount = 0;
        $coupon = null;

        if ($request->has('coupon_code')) {
            $coupon = Coupon::where('coupon_code', $request->coupon_code)->first();

            if (!$coupon) {
                return response()->json(['message' => 'Coupon code does not exist.'], 400);
            }

            if ($coupon->coupon_status != 'active') {
                return response()->json(['message' => 'Coupon code is inactive.'], 400);
            }

            if ($coupon->expiration_date <= now()) {
                return response()->json(['message' => 'Coupon code has expired.'], 400);
            }

            $discount = $coupon->discount_amount;
            $totalAmount -= $discount;
            $totalAmount = max(0, $totalAmount);
        }

        $discountPercentage = ($originalTotalAmount > 0) ? ($discount / $originalTotalAmount) * 100 : 0;

        $order = Order::create([
            'user_id' => $request->user_id ?? null,
            'shipping_address' => $request->shipping_address,
            'total_amount' => $totalAmount,
            'payment_method' => $request->payment_method,
            'payment_status' => $request->payment_status,
            'coupon_code' => $coupon ? $coupon->coupon_code : null,
            'order_date' => now(),
            'order_status' => $request->order_status,
            'note' =>  $request->note ?? null,
            'discount' => $discount,
        ]);

        $orderData = [];
        $subtotal = 0;

        $checkoutArray = $request->checkoutArray;

        foreach ($request->products as $product) {
            $product1 = Product::findOrFail($product['product_id']);
            $variation = $product1->variations()->find((int)$product['variation_id']);

            $productPrice = $product1->product_price;

            $productOffer = ProductOffer::where('offer_product_id', $product1->id)->first();
            if ($productOffer) {
                if ($productOffer->hot_deal) {
                    $productPrice -= $productPrice * ($productOffer->hot_deal / 100);
                }
            }

            $orderItem = new OrderItems();
            $orderItem->order_id = $order->id;
            $orderItem->product_id =  $product1->id;
            $orderItem->variation_id = $variation?->id;
            $orderItem->quantity = $product['quantity'];
            $orderItem->product_price = $productPrice;
            $orderItem->save();

            $orderData[] = OrderItems::with(['product', 'productVariation'])->find($orderItem->id);
            $subtotal += $productPrice * $product['quantity'];

            if ($request->filled('referral_user_id')) {
                $isValidReferral = false;
                foreach ($checkoutArray as $item) {
                    if ($item['referral_user_id'] == $request->referral_user_id && $item['product_id'] == $product1->id) {
                        $isValidReferral = true;
                        break;
                    }
                }

                if ($isValidReferral) {
                    $referral = Referral::where('user_id', $request->referral_user_id)
                        ->where('product_id', $product1->id)
                        ->where('order_id', $order->id)
                        ->first();

                    $commissionAmount = $productPrice * ($product1->commission_percentage / 100) * $product['quantity'];

                    if ($referral) {
                        $referral->commission_amount += $commissionAmount;
                        $referral->save();
                    } else {
                        $referral = new Referral([
                            'user_id' => $request->referral_user_id,
                            'product_id' => $product1->id,
                            'order_id' => $order->id,
                            'commission_percentage' => $product1->commission_percentage,
                            'commission_amount' => $commissionAmount,
                        ]);
                        $referral->save();
                    }

                    $userCommission = UserCommission::firstOrNew(['user_id' => $request->referral_user_id]);
                    $userCommission->total_commission = ($userCommission->total_commission ?? 0) + $commissionAmount;
                    $userCommission->save();
                }
            }
        }

        $orderProduct = Order::with(['user', 'orderItems.product', 'orderItems.productVariation'])->find($order->id);

        return response()->json([
            'message' => 'Order placed successfully',
            'order' => $orderProduct,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total_amount' => $totalAmount,
            'coupon_code' => $coupon ?? null,
            'discount_percentage' => $discountPercentage,
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
    public function updateOrderStatusCallback(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            // 'appId' => 'required|string',
            'orderId' => 'required|string',
            // 'transId' => 'required|string',
            // 'method' => 'required|string',
            // 'transTime' => 'required|string',
            // 'merchantTransId' => 'required|string',
            // 'amount' => 'required|numeric',
            // 'description' => 'required|string',
            'resultCode' => 'required|integer',
            // 'message' => 'required|string',
            // 'extradata' => 'nullable|string',
        ]);
        $orderId = $request->input('orderId');
        // Find the order by ID
        $order = Order::findOrFail($orderId);

        // Update the order status based on resultCode
        if ($request->input('resultCode') == 1) {
            $order->payment_status = 'paid';
            $order->order_status = 'confirmed';
        } else {
            $order->payment_status = 'unpaid';
            $order->order_status = 'cancelled';
        }

        // Update the payment method
        $order->payment_method = $request->input('method');
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
