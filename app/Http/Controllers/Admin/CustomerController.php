<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->withCount('orders')
            ->withSum(['orders as total_spent' => fn ($q) => $q->whereNotIn('status', ['pendente', 'cancelado'])], 'total')
            ->latest();

        if ($search = $request->query('q')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        return view('admin.customers', ['customers' => $query->paginate(20)->withQueryString()]);
    }
}
