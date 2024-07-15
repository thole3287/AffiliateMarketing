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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;


class OrderController extends Controller
{
    public function index(Request $request)
    {
        $currentPage = $request->input('page', 1);

        // Lấy số lượng mục trên mỗi trang từ yêu cầu, mặc định là 10
        $perPage = $request->input('per_page', 10);

        // Lấy danh sách đơn hàng và phân trang, kèm theo thông tin người dùng và người duyệt đơn hàng
        $orders = Order::with(['user', 'orderItems.product', 'orderItems.productVariation', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Tạo một mảng kết quả JSON bao gồm các thông tin phân trang và danh sách đơn hàng
        $responseData = [
            'orders' => $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'user_id' => $order->user_id,
                    'shipping_address' => $order->shipping_address,
                    'total_amount' => $order->total_amount,
                    'order_date' => $order->order_date,
                    'zalo_order_id' => $order->zalo_order_id,
                    'check_out_order' => $order->check_out_order,
                    'commission_processed' => $order->commission_processed,
                    'coupon_code' => $order->coupon_code,
                    'user' => $order->user,
                    'order_items' => $order->orderItems,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'approved_by' => $order->approvedBy->name ?? null, // Thêm thông tin người duyệt đơn hàng
                    'order_status' => $order->order_status,
                    'note' => $order->note,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ];
            }),
            'pagination' => [
                'total_orders' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem()
            ],
        ];

        return response()->json($responseData);
    }


    public function updateNote(Request $request, $orderId)
    {
        $request->validate([
            'note' => 'required|string',
        ]);

        // Tìm đơn hàng bằng ID
        $order = Order::findOrFail($orderId);

        // Cập nhật ghi chú và ID người thực hiện ghi chú
        $order->note = $request->input('note');
        $order->noted_by = $request->input('user_id'); // Lấy ID người dùng đã đăng nhập
        $user_infor = User::where('id', $request->input('user_id'))->first();
        // Trả về phản hồi thành công
        return response()->json([
            'message' => 'Order note updated successfully',
            'order' => $order,
            'userInfo' => $user_infor
        ], Response::HTTP_OK);
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

        $orders = $query->orderByDesc('created_at')->paginate($request->input('per_page', 10));

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
            'checkoutArray' => 'nullable|array',
            'checkoutArray.*.product_id' => 'nullable|exists:products,id',
            'checkoutArray.*.variation_id' => 'nullable|exists:product_variations,id',
            'checkoutArray.*.referral_user_id' => 'nullable|exists:users,id',
            'checkoutArray.*.quantity' => 'nullable|integer|min:1',
        ]);
        // Log::info('data order:', $request->all());

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
            'zalo_order_id' => $request->zalo_order_id ?? null,
            'check_out_order' => $request->checkoutArray ?? [],
        ]);

        $orderData = [];
        $subtotal = 0;

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
        }

        $orderProduct = Order::with(['user', 'orderItems.product', 'orderItems.productVariation'])->find($order->id);
        // dd($subtotal,  $discount, $discountPercentage);
        // Send email
        $user = User::find($request->user_id);
        if (!empty($user->email)) {
            dispatch(new SendOrderEmail($order, $orderData, $user->email, $discount, $subtotal, $discountPercentage));
            // Mail::to($user->email)->send(new OrderPlaced($order, $orderData, $discount, $subtotal, $discountPercentage));
        }
        // Log::info('data order return:', [
        //     'message' => 'Order placed successfully',
        //     'order' => $orderProduct,
        //     'subtotal' => $subtotal,
        //     'discount' => $discount,
        //     'total_amount' => $totalAmount,
        //     'coupon_code' => $coupon ?? null,
        //     'discount_percentage' => $discountPercentage,
        // ]);

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

    public function filterOrders(Request $request)
    {
        $request->validate([
            'payment_status' => 'nullable|string|in:paid,unpaid',
            'order_status' => 'nullable|string|in:ordered,confirmed,cancelled,shipping,completed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $query = Order::query();

        // Áp dụng điều kiện filter nếu có
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        if ($request->has('order_status')) {
            $query->where('order_status', $request->input('order_status'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('order_date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('order_date', '<=', $request->input('end_date'));
        }

        // Lấy danh sách đơn hàng theo điều kiện
        $orders = $query->with('user', 'orderItems.product', 'orderItems.productVariation')->get();

        return response()->json([
            'orders' => $orders
        ]);
    }

    public function updateOrderStatus(Request $request, $orderId)
    {
        $request->validate([
            'note' => 'nullable|string',
            'user_id' => 'nullable|integer|exists:users,id',
            'payment_status' => 'nullable|string|in:paid,unpaid',
            'order_status' => 'nullable|string|in:ordered,confirmed,cancelled,shipping,completed',
            'status_payment_updated_by' => 'nullable|integer|exists:users,id', // Thêm điều kiện xác thực cho status_payment_updated_by
        ]);

        // Tìm đơn hàng bằng ID
        $order = Order::findOrFail($orderId);

        // Cập nhật ghi chú nếu có
        if ($request->has('note')) {
            $order->note = $request->input('note');
            $order->noted_by = $request->input('user_id');
        }

        // Kiểm tra trạng thái thanh toán trước đó
        $previousPaymentStatus = $order->payment_status;

        // Cập nhật trạng thái đơn hàng và trạng thái thanh toán nếu có
        if ($request->has('payment_status')) {
            $order->payment_status = $request->input('payment_status');
            $order->status_payment_updated_by = $request->input('status_payment_updated_by');
        }

        if ($request->has('order_status')) {
            $order->order_status = $request->input('order_status');
        }

        // Nếu trạng thái thanh toán là 'paid' lần đầu tiên và hoa hồng chưa được xử lý
        if ($previousPaymentStatus !== 'paid' && $order->payment_status === 'paid' && !$order->commission_processed) {
            $checkoutArray = $order->check_out_order ?? [];
            foreach ($checkoutArray as $item) {
                $referralUserId = $item['referral_user_id'];
                $productId = $item['product_id'];

                $product = Product::findOrFail($productId);
                // Kiểm tra trạng thái hoa hồng của sản phẩm
                if ($product->product_commission_status === 'inactive') {
                    continue; // Bỏ qua nếu trạng thái là 'inactive'
                }

                // Tính toán giá trị hoa hồng dựa trên trạng thái
                if ($product->product_commission_status === 'active') {
                    $commissionPercentage = $product->commission_percentage;
                } elseif ($product->product_commission_status === 'special') {
                    $commissionPercentage = $product->special_commission_percentage;
                } else {
                    continue; // Bỏ qua nếu trạng thái không hợp lệ
                }

                $commissionAmount = $product->product_price * ($commissionPercentage / 100) * $item['quantity'];

                $referral = Referral::where('user_id', $referralUserId)
                    ->where('product_id', $productId)
                    ->where('order_id', $order->id)
                    ->first();

                if ($referral) {
                    $referral->commission_amount += $commissionAmount;
                    $referral->save();
                } else {
                    $referral = new Referral([
                        'user_id' => $referralUserId,
                        'product_id' => $productId,
                        'order_id' => $order->id,
                        'commission_percentage' => $commissionPercentage,
                        'commission_amount' => $commissionAmount,
                    ]);
                    $referral->save();
                }

                $userCommission = UserCommission::firstOrNew(['user_id' => $referralUserId]);
                $userCommission->total_commission = ($userCommission->total_commission ?? 0) + $commissionAmount;
                $userCommission->save();
            }

            // Đánh dấu hoa hồng đã được xử lý
            $order->commission_processed = true;
        }

        $order->save();

        // Nạp thông tin người đã cập nhật trạng thái thanh toán
        $order->load('approvedBy');

        // Trả về phản hồi thành công
        $userInfor = $request->has('user_id') ? User::find($request->input('user_id')) : null;

        // Tạo dữ liệu phản hồi
        $orderData = $order->toArray();
        $orderData['approved_by'] = $order->approvedBy->name ?? null; // Thêm thông tin người đã cập nhật trạng thái thanh toán

        return response()->json([
            'message' => 'Order updated successfully',
            'order' => $orderData,
            'userInfo' => $userInfor,
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
        $user = User::find($order->user_id);

        SendOrderCancelledEmailJob::dispatch($order, $user->email);

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

        // Thêm điều kiện sắp xếp theo created_at giảm dần
        $orders = $orders->orderByDesc('created_at')
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
