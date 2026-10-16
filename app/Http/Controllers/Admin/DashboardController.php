<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $approvedThisMonth = Order::query()
            ->where('status', OrderStatus::Approved)
            ->where('paid_at', '>=', now()->startOfMonth());

        return view('admin.dashboard', [
            'studentsCount' => User::query()->where('role', UserRole::Student)->count(),
            'coursesCount' => Course::query()->count(),
            'salesThisMonth' => (clone $approvedThisMonth)->count(),
            'revenueThisMonth' => (clone $approvedThisMonth)->sum('amount'),
            'latestOrders' => Order::query()->with(['user', 'product'])->latest()->limit(10)->get(),
        ]);
    }
}
