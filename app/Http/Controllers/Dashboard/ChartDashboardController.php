<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\TopProductsExport;
use App\Exports\TopSalesByBrandExport;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\product\Product;
use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Http\Response;
use App\Exports\TotalOrdersExport;
use App\Exports\TotalOrdersReferralExport;
use App\Exports\WeeklySalesExport;
use Maatwebsite\Excel\Facades\Excel;

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
            $totalOrders[$month] = (float)$count;
        }

        $totalOrdersData = [];
        foreach ($totalOrders as $month => $count) {
            $totalOrdersData[] = [$month, (float)$count];
        }

        if (request()->has('total-order-chart')) {
            // dd($totalOrdersData);
            return Excel::download(new TotalOrdersExport($totalOrdersData), 'total_orders.xlsx');
        }

        //weekly chart
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

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

        // Initialize revenue data array for each day of the week (0 to 6)
        $revenueData = array_fill(0, 7, 0);

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

        // Mapping day indices to day names (Monday to Sunday)
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $revenueChartData = [];
        foreach ($revenueData as $dayIndex => $revenue) {
            $dayName = $daysOfWeek[$dayIndex];
            $revenueChartData[] = [$dayName, (float)$revenue];
        }
        if (request()->has('weekly-sales-chart')) {
            return Excel::download(new WeeklySalesExport($revenueChartData), 'weekly_sales.xlsx');
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
            ->get()->map(function ($item, $index) {
                return [
                    'id' => $index + 1,
                    'name' => $item->brand_name,
                    'value' => (float) $item->total_revenue,
                ];
            });
        $topSalesByBrandData = [];
        foreach ($topBrands as $brand) {
            $topSalesByBrandData[] = [
                'id' => $brand['id'],
                'name' => $brand['name'],
                'value' => $brand['value'],
            ];
        }
        if (request()->has('top-sales-by-brand-chart')) {
            // dd($topSalesByBrandData);
            return Excel::download(new TopSalesByBrandExport($topSalesByBrandData), 'top_sales_by_brand.xlsx');
        }

        $year1 = 2023;
        $year2 = 2024;

        $topProducts = OrderItems::selectRaw('products.product_name as product_name,
                                          SUM(CASE WHEN YEAR(orders.order_date) = ? THEN order_items.quantity ELSE 0 END) as year1_total,
                                          SUM(CASE WHEN YEAR(orders.order_date) = ? THEN order_items.quantity ELSE 0 END) as year2_total', [$year1, $year2])
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->groupBy('products.product_name')
            ->orderBy('products.product_name')
            ->get();

        $topProductsData = [];
        foreach ($topProducts as $product) {
            $topProductsData[] = [
                'product' => $product->product_name,
                strval($year1) => (float) $product->year1_total,
                strval($year2) => (float) $product->year2_total,
            ];
        }

        if (request()->has('top-products-chart')) {
            return Excel::download(new TopProductsExport($topProductsData, $year1, $year2), 'top_products.xlsx');
        }
        // Lấy dữ liệu tổng số đơn hàng Referral theo tháng
        $currentYear = Carbon::now()->year;

        $totalOrdersReferral = Referral::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as total')
        )
            ->whereYear('created_at', $currentYear)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy(DB::raw('MONTH(created_at)'))
            ->get()
            ->toArray();

        $data = [];

        // Điền vào mảng dữ liệu cho tất cả các tháng trong năm hiện tại
        for ($i = 1; $i <= 12; $i++) {
            $data[$i] = 0; // Khởi tạo số đơn hàng của mỗi tháng thành 0
        }

        foreach ($totalOrdersReferral as $order) {
            $data[$order['month']] = (float)$order['total']; // Gán số đơn hàng của tháng
        }

        // Chuyển đổi mảng dữ liệu thành mảng chứa tổng số đơn hàng cho từng tháng
        $result = array_values($data);

        if (request()->has('total-orders-referral-chart')) {
            // Chuẩn bị dữ liệu cho Excel xuất với tháng là chuỗi
            $totalOrdersReferralData = [];
            foreach ($data as $month => $total) {
                $totalOrdersReferralData[] = [
                    'month' => strval($month),
                    'total_orders_referral' => strval($total),
                ];
            }

            return Excel::download(new TotalOrdersReferralExport($totalOrdersReferralData), 'total_orders_referral.xlsx');
        }

        return response()->json([
            'totalOrderChart' => [
                'totalOrderCount' => $totalOrderCount,
                'totalOrders' => array_values($totalOrders)
            ],
            'weeklySalesChart' => [
                'totalWeeklyRevenue' => $totalWeeklyRevenue,
                'dailyRevenue' => $revenueData,
            ],
            'topSalesByBrandChart' => [
                'topSalesByBrand' => $topSalesByBrandData,
            ],
            'topProductsChart' => [
                'topProducts' => $topProductsData,
            ],
            'totalOrdersReferralChart' => [
                'totalOrdersReferrals' => $result,
            ],
        ]);
    }
}
