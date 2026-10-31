<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

use App\Models\Product;
use App\Models\Customer;
use App\Models\Order;
use App\Jobs\SendOrderConfirmation;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer.name' => 'required|string|max:255',
            'customer.email' => 'required|email',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|integer|distinct|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = DB::transaction(function () use ($request) {

                // Find or create customer
                $customer = Customer::firstOrCreate(
                    ['email' => $request->customer['email']],
                    ['name' => $request->customer['name']]
                );

                $subtotal = 0;
                $totalTax = 0;
                $orderItems = [];

                // Lock products to prevent overselling
                $productIds = collect($request->products)
                    ->pluck('product_id')
                    ->sort()
                    ->values();

                $products = Product::whereIn('id', $productIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($request->products as $item) {

                    $product = $products->get($item['product_id']);
                    $quantity = $item['quantity'];

                    if (!$product || $product->stock < $quantity) {
                        throw ValidationException::withMessages([
                            'stock' => [
                                'Insufficient stock for product: ' .
                                ($product ? $product->name : 'Unknown')
                            ]
                        ]);
                    }

                    $lineSubtotal = round(
                        $product->price * $quantity, 2
                    );

                    $lineTax = round(
                        $lineSubtotal * $product->tax_percentage / 100, 2
                    );

                    $lineTotal = $lineSubtotal + $lineTax;

                    $subtotal += $lineSubtotal;
                    $totalTax += $lineTax;

                    $orderItems[] = [
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $product->price,
                        'tax_percentage' => $product->tax_percentage,
                        'subtotal' => $lineSubtotal,
                        'tax' => $lineTax,
                        'total' => $lineTotal,
                    ];

                    // Deduct stock
                    $product->decrement('stock', $quantity);
                }

                // Create order
                $order = Order::create([
                    'customer_id' => $customer->id,
                    'subtotal' => $subtotal,
                    'tax' => $totalTax,
                    'grand_total' => $subtotal + $totalTax,
                ]);

                // Create order items
                $order->items()->createMany($orderItems);

                return $order;
            });

            // Dispatch job after successful transaction
            SendOrderConfirmation::dispatch($order);

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order->load('customer', 'items.product')
            ], 201);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Unable to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function history(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $customer = Customer::with('orders.items.product')
            ->where('email', $request->email)
            ->first();

        if (!$customer) {
            return response()->json([
                'message' => 'Customer not found'
            ], 404);
        }

        return response()->json($customer->orders);
    }

    public function lowStock(Request $request)
    {
        $threshold = $request->query('threshold', 5);

        $products = Product::where('stock', '<', $threshold)->get();

        return response()->json($products);
    }
}
