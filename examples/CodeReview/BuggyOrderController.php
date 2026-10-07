<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');

        $orders = DB::select("SELECT * FROM orders WHERE status = '$status'");

        return view('orders.index', ['orders' => $orders]);
    }

    public function report()
    {
        $orders = Order::where('created_at', '>=', now()->subDays(30))->get();

        $rows = [];

        foreach ($orders as $order) {
            $rows[] = [
                'id' => $order->id,
                'customer' => $order->customer->name,
                'total' => $order->total,
            ];
        }

        return response()->json($rows);
    }

    public function show(int $id)
    {
        $order = Order::find($id);

        return response()->json([
            'id' => $order->id,
            'total' => $order->total,
            'paid' => $order->paid_at !== null,
        ]);
    }
}
