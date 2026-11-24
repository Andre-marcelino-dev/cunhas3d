<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $paidStatuses = [Order::STATUS_PAID, Order::STATUS_PRODUCTION, Order::STATUS_SHIPPED, Order::STATUS_DELIVERED];
        $paid = Order::whereIn('status', $paidStatuses);

        // Faturamento dos últimos 14 dias para o gráfico
        $days = collect(range(13, 0))->map(fn ($i) => now()->subDays($i)->toDateString());
        $revenueByDay = Order::whereIn('status', $paidStatuses)
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as day, SUM(total) as total')
            ->groupBy('day')->pluck('total', 'day');

        $chart = $days->map(fn ($day) => [
            'label' => \Carbon\Carbon::parse($day)->format('d/m'),
            'value' => (float) ($revenueByDay[$day] ?? 0),
        ]);

        return view('admin.dashboard', [
            'revenueToday' => (clone $paid)->whereDate('created_at', today())->sum('total'),
            'revenueMonth' => (clone $paid)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total'),
            'ordersPending' => Order::where('status', Order::STATUS_PENDING)->count(),
            'ordersToProduce' => Order::whereIn('status', [Order::STATUS_PAID, Order::STATUS_PRODUCTION])->count(),
            'customers' => User::count(),
            'lowStock' => Product::where('stock', '<=', 3)->orderBy('stock')->take(6)->get(),
            'latestOrders' => Order::latest()->take(8)->get(),
            'topProducts' => OrderItem::query()
                ->select('name', DB::raw('SUM(quantity) as qty'), DB::raw('SUM(total) as revenue'))
                ->whereHas('order', fn ($q) => $q->whereIn('status', $paidStatuses))
                ->groupBy('name')->orderByDesc('qty')->take(5)->get(),
            'chart' => $chart,
        ]);
    }
}
