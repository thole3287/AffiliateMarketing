<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Http\Response;

class ChartDashboardController extends Controller
{
    // public function getWeeklyOrderTotals()
    // {
    //     // Get the current month
    //     $totalOrderCount = Order::count();
    //     $currentMonth = Carbon::now()->month;
    //     $currentYear = Carbon::now()->year;

    //     // Query to get the total orders per week for the current month
    //     $weeklyOrders = Order::select(DB::raw('WEEK(order_date) as week, COUNT(*) as total'))
    //         ->whereMonth('order_date', $currentMonth)
    //         ->whereYear('order_date', $currentYear)
    //         ->groupBy(DB::raw('WEEK(order_date)'))
    //         ->pluck('total', 'week')
    //         ->toArray();
    //     // Initialize the weekly totals array
    //     $totalOrders = array_fill(0, 5, 0); // Assuming a maximum of 5 weeks in a month

    //     // Fill the total orders array with the data from the query
    //     foreach ($weeklyOrders as $week => $total) {
    //         // Subtract the starting week to make the index 0-based
    //         $index = $week - Carbon::now()->startOfMonth()->week;
    //         if ($index >= 0 && $index < 5) {
    //             $totalOrders[$index] = $total;
    //         }
    //     }

    //     return response()->json([
    //         'totalOrderCount' =>  $totalOrderCount,
    //         'totalOrders' => $totalOrders,
    //     ], Response::HTTP_OK);
    // }
    public function getTotalOrdersByMonth()
    {
        $totalOrderCount = Order::count();
        // Get the total number of orders grouped by month
        $orders = Order::select(
            DB::raw('count(id) as total_orders'),
            DB::raw('MONTH(created_at) as month')
        )
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy(DB::raw('MONTH(created_at)'))
            ->get()
            ->pluck('total_orders', 'month')
            ->toArray();

        // Initialize an array with 12 elements (one for each month) set to 0
        $totalOrders = array_fill(1, 12, 0);

        // Fill the totalOrders array with the actual values from the query
        foreach ($orders as $month => $count) {
            $totalOrders[$month] = $count;
        }


        //weekly chart
        // Get the start and end dates of the current week
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        // Get the daily revenue for the current week
        $dailyRevenue = Order::select(
            DB::raw('SUM(total_amount) as total_revenue'),
            DB::raw('DATE(created_at) as date')
        )
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'))
            ->get()
            ->pluck('total_revenue', 'date')
            ->toArray();

        // Initialize an array for the daily revenue with zeros for each day of the current week
        $revenueData = array_fill(0, 7, 0);

        // Fill the revenueData array with the actual values from the query
        $dayIndex = 0;
        $totalWeeklyRevenue = 0;
        for ($date = $startOfWeek; $date <= $endOfWeek; $date->addDay()) {
            $formattedDate = $date->format('Y-m-d');
            if (isset($dailyRevenue[$formattedDate])) {
                $revenueData[$dayIndex] = (float)$dailyRevenue[$formattedDate];
                $totalWeeklyRevenue += $dailyRevenue[$formattedDate];
            }
            $dayIndex++;
        }



        // top brand selling
        $topBrands = Product::join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('brands', 'products.brand_id', '=', 'brands.id')
            ->select(
                'brands.name as brand_name',
                DB::raw('CAST(SUM(order_items.product_price * order_items.quantity) AS DECIMAL(10, 2)) as total_revenue')
            )
            ->groupBy('brands.name')
            ->orderByDesc('total_revenue')
            ->take(3)
            ->get()->map(function ($item) {
                $item->total_revenue = (float) $item->total_revenue;
                return $item;
            });

        // dd( $topBrands);
        // Transform the data into the desired format
        $topSalesByBrand = $topBrands->map(function ($brand, $index) {
            return [
                'id' => $index + 1,
                'name' => $brand->brand_name,
                'value' => $brand->total_revenue,
            ];
        });


        ///topProductsChart
        $year1 = 2023; // Năm thứ nhất
        $year2 = 2024; // Năm thứ hai

        $topProducts = OrderItems::selectRaw('products.product_name as product_name,
                                      SUM(CASE WHEN YEAR(orders.order_date) = ? THEN order_items.quantity ELSE 0 END) as year1_total,
                                      SUM(CASE WHEN YEAR(orders.order_date) = ? THEN order_items.quantity ELSE 0 END) as year2_total', [$year1, $year2])
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->groupBy('products.product_name')
            ->orderBy('products.product_name')
            ->get();

        // Tạo một mảng để lưu trữ kết quả
        $data = [['product', (string)$year1, (string)$year2]];

        // Lặp qua kết quả và thêm vào mảng kết quả
        foreach ($topProducts as $product) {
            $productName = $product->product_name;
            $year1Total = $product->year1_total;
            $year2Total = $product->year2_total;

            // Thêm vào mảng
            $data[] = [$productName, (float)$year1Total, (float)$year2Total];
        }
        return response()->json([
            'totalOrderChart' => [
                'totalOrderCount' =>  $totalOrderCount,
                'totalOrders' => array_values($totalOrders)
            ],
            'weeklySalesChart' => [
                'totalWeeklyRevenue' => $totalWeeklyRevenue,
                'dailyRevenue' => $revenueData,
            ],
            'topSalesByBrandChart' => [
                'topSalesByBrand' =>  $topSalesByBrand,
            ],
            'topProductsChart' => [
                'topProducts' => $data,
            ],

        ]);
    }
}
