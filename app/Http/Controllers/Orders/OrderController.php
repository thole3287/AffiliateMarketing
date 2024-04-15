<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\product\Product;
use App\Models\product\ProductVariation;
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

        $order = Order::create([
            'user_id' => $request->user_id,
            'shipping_address' => $request->shipping_address,
            'total_amount' => $request->total_amount,
            'payment_method' => $request->payment_method,
            'payment_status' => $request->payment_status,
            'order_date' => now(),
            'note' =>  $request->note
        ]);
        $orderData = [];
        foreach ($request->products as $product) {
            // dd($product['product_id'], $product['variation_id']);
            $product1 = Product::findOrFail($product['product_id']);

            // $variation =ProductVariation::where('product_id', $product['id'])->get();
            $variation = $product1->variations()->find((int)$product['variation_id']);
            // dd($order->id, $product1->id, $variation->id, $product['quantity'], $product1->product_price);
            // dd($product1->product_price / $product['quantity']);
            $data = [
                'order_id' => $order->id,
                'product_id' => $product1->id,
                'variation_id' => $variation?->id,
                'quantity' => $product['quantity'],
                'product_price' => $product1->product_price / $product['quantity'],
            ];
            $orderItem = new OrderItems();
            $orderItem->order_id = $order->id;
            $orderItem->product_id =  $product1->id;
            $orderItem->variation_id = $variation?->id;
            $orderItem->quantity = $product['quantity'];
            $orderItem->product_price = $product1->product_price;
            // dd($product['variation_id'], $variation);
            // OrderItems::create( $data);
            // dd(12313);
        }
        // dd()
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


    // public function placeOrder(Request $request)
    // {
    //     $request->validate([
    //         'user_id' => 'required|exists:users,id',
    //         'products' => 'required|array',
    //         'products.*.product_id' => 'required|exists:products,id',
    //         'products.*.quantity' => 'required|integer|min:1',
    //         'products.*.variation_id' => 'nullable|exists:product_variations,id',
    //         'products.*.product_size' => 'nullable|string',
    //         'products.*.product_color' => 'nullable|string',
    //         'products.*.variation_size' => 'nullable|string',
    //         'products.*.variation_color' => 'nullable|string',
    //         'shipping_address' => 'required|string',
    //         'total_amount' => 'required|numeric',
    //         'payment_method' => 'required|string',
    //         'payment_status' => 'required|string|in:pending,paid,failed',
    //     ]);

    //     $order = Order::create([
    //         'user_id' => $request->user_id,
    //         'shipping_address' => $request->shipping_address,
    //         'order_total_amount' => $request->total_amount,
    //         'payment_method' => $request->payment_method,
    //         'payment_status' => $request->payment_status,
    //         'ordered_at' => now(),
    //     ]);

    //     foreach ($request->products as $product) {
    //         $productModel = Product::findOrFail($product['product_id']);
    //         $variation = $productModel->variations()->find($product['variation_id']);

    //         $orderItem = OrderItems::create([
    //             'order_id' => $order->id,
    //             'product_id' => $productModel->id,
    //             'variation_id' => $variation?->id,
    //             'product_size' => $product['product_size'] ?? null,
    //             'product_color' => $product['product_color'] ?? null,
    //             'variation_size' => $variation?->size ?? null,
    //             'variation_color' => $variation?->color ?? null,
    //             'quantity' => $product['quantity'],
    //             'product_price' => $productModel->price,
    //             'variation_price' => $variation?->price,
    //         ]);
    //     }

    //     return response()->json([
    //         'message' => 'Order placed successfully',
    //         'order' => $order,
    //         'order_detail' => $orderItem ?? []

    //     ], Response::HTTP_CREATED);
    // }
}
