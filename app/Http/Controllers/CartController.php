<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Srmklive\PayPal\Services\PayPal;
use Surfsidemedia\Shoppingcart\Facades\Cart;

class CartController extends Controller
{
    public function index(){
        $items = Cart::instance('cart')->content();
        return view('cart', compact('items'));
    }

    public function add_to_cart(Request $request){
        Cart::instance('cart')->add($request->id,$request->name, $request->quantity, $request->price)->associate('App\Models\Product');
        return redirect()->back();
    }

    public function increase_cart_quantity($rowId){
        $product = Cart::instance('cart')->get($rowId);
        $qty = $product->qty + 1;
        Cart::instance('cart')->update($rowId, $qty);
        return redirect()->back();
    }

    public function decrease_cart_quantity($rowId){
        $product = Cart::instance('cart')->get($rowId);
        $qty = $product->qty - 1;
        Cart::instance('cart')->update($rowId, $qty);
        return redirect()->back();
    }

    public function remove_cart($rowId){
        Cart::instance('cart')->remove($rowId);
        return redirect()->back();
    }

    public function empty_cart(){
        Cart::instance('cart')->destroy();
        return redirect()->back();
    }

    public function apply_coupon_code(Request $request)
    {
        $coupon_code = $request->coupon_code;
        if(isset($coupon_code)) {
            $coupon = Coupon::where('code', $coupon_code)->where('expiry_date', '>=', Carbon::today())
                ->where('cart_value', '<=', Cart::instance('cart')->subtotal())->first();
            if (!$coupon) {
                return redirect()->back()->with('error', 'Coupon Code Invalid');
            } else {
                Session::put('coupon', [
                    'code' => $coupon->code,
                    'type' => $coupon->type,
                    'value' => $coupon->value,
                    'cart_value' => $coupon->cart_value
                ]);
                $this->calculateDiscount();
                return redirect()->back()->with('success', 'Coupon Applied Successfully');
            }
        }
        else {
            return redirect()->back()->with('error', 'Coupon Code Invalid');
        }
    }

    public function calculateDiscount(){
        if (!Session::has('coupon') || !Session::has('coupon.type') || !Session::has('coupon.value')) {
            return;
        }

        $cartSubtotal = Cart::instance('cart')->subtotal();
        if ($cartSubtotal <= 0) {
            return;
        }

        $coupon = Session::get('coupon');
        $discount = 0;

        if ($coupon['type'] == 'fixed') {
            $discount = floatval($coupon['value']);
        } else { // Percentage discount
            $discount = ($cartSubtotal * floatval($coupon['value'])) / 100;
        }

        $subtotalAfterDiscount = max(0, $cartSubtotal - $discount); // Ensure no negative subtotal
        $taxRate = floatval(config('cart.tax', 10)); // Default tax to 10% if missing
        $taxAfterDiscount = ($subtotalAfterDiscount * $taxRate) / 100;
        $totalAfterDiscount = $subtotalAfterDiscount + $taxAfterDiscount;

        Session::put('discounts', [
            'discount' => number_format($discount, 2, '.', ''),
            'subtotal' => number_format($subtotalAfterDiscount, 2, '.', ''),
            'tax' => number_format($taxAfterDiscount, 2, '.', ''),
            'total' => number_format($totalAfterDiscount, 2, '.', '')
        ]);
    }


    public function remove_coupon_code(){
        Session::forget('coupon');
        Session::forget('discounts');
        return back()->with('success', 'Coupon Code Removed Successfully');
    }

    public function checkout()
    {
        if(!Auth::check()){
            return redirect()->route('login');
        }

        $address = Address::where('user_id', Auth::user()->id)->where('isdefault', 1)->first();
        return view('checkout', compact('address'));
    }

    public function place_an_order(Request $request){
        $user_id = Auth::user()->id;
        $address = Address::where('user_id', $user_id)->where('isdefault', true)->first();

        if(!$address){
            $request->validate([
                'name' => 'required|max:100',
                'phone' => 'required|numeric|digits_between:10,12',
                'zip' => 'required|numeric|digits_between:6,12',
                'state' => 'required',
                'city' => 'required',
                'address' => 'required',
                'locality' => 'required',
                'landmark' => 'required',
            ]);

            $address = new Address();
            $address->name = $request->name;
            $address->phone = $request->phone;
            $address->zip = $request->zip;
            $address->state = $request->state;
            $address->city = $request->city;
            $address->locality = $request->locality;
            $address->landmark = $request->landmark;
            $address->address = $request->address;
            $address->country = 'Vietnam';
            $address->user_id = $user_id;
            $address->isdefault = true;

            $address->save();
        }
        $this->setAmountforCheckout();
        $order = new Order();
        $order->user_id = $user_id;
        $order->subtotal = Session::get('checkout')['subtotal'];
        $order->discount = Session::get('checkout')['discount'];
        $order->tax = Session::get('checkout')['tax'];
        $order->total = Session::get('checkout')['total'];
        $order->name = $address->name;
        $order->phone = $address->phone;
        $order->locality = $address->locality;
        $order->address = $address->address;
        $order->city = $address->city;
        $order->state = $address->state;
        $order->country = $address->country;
        $order->landmark = $address->landmark;
        $order->zip = $address->zip;

        $order->save();

        foreach (Cart::instance('cart')->content() as $item) {
            $orderItem = new OrderItem();
            $orderItem->product_id = $item->id;
            $orderItem->order_id = $order->id;
            $orderItem->price = $item->price;
            $orderItem->quantity = $item->qty;
            $orderItem->save();
        }

        if($request->mode == "card"){
            // Set your Stripe secret key
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

            try {
                // Create a PaymentIntent with the order total (converted to cents)
                $paymentIntent = \Stripe\PaymentIntent::create([
                    'amount' => Session::get('checkout')['total'] * 100, // amount in cents
                    'currency' => env('CURRENCY', 'usd'),
                    'metadata' => [
                        'order_id' => $order->id,
                    ],
                ]);

                // Pass the PaymentIntent's client secret to the view
                return view('payments.card', [
                    'clientSecret' => $paymentIntent->client_secret,
                    'order' => $order,
                ]);
            } catch (\Exception $e) {
                // Handle any errors from Stripe
                return redirect()->route('cart.order.confirmation')
                    ->with('error', 'Error processing payment: ' . $e->getMessage());
            }
        }

        elseif($request->mode == "paypal"){
            // Create an instance of the PayPal client
            $provider = new PayPal();

            // Set API credentials from the config file
            $provider->setApiCredentials(config('paypal'));
            $token = $provider->getAccessToken();
            $provider->setAccessToken($token);

            // Create a new PayPal order with the total amount from your order
            $response = $provider->createOrder([
                "intent" => "CAPTURE",
                "purchase_units" => [
                    [
                        "amount" => [
                            "currency_code" => "USD", // Adjust the currency as needed
                            "value" => $order->total
                        ]
                    ]
                ],
                "application_context" => [
                    "cancel_url" => route('paypal.cancel'), // Define this route
                    "return_url" => route('paypal.success') // Define this route for successful payment
                ]
            ]);

            // Check if the PayPal order was created successfully
            if(isset($response['id']) && $response['id'] != null){
                // Redirect the user to PayPal for approval
                foreach($response['links'] as $link){
                    if($link['rel'] === 'approve'){
                        return redirect()->away($link['href']);
                    }
                }
                return redirect()
                    ->route('cart.order.confirmation')
                    ->with('error', 'Something went wrong with PayPal approval.');
            } else {
                return redirect()
                    ->route('cart.order.confirmation')
                    ->with('error', $response['message'] ?? 'Something went wrong with PayPal.');
            }
        }

        elseif($request->mode == "cod"){
            $transaction = new Transaction();
            $transaction->user_id = $user_id;
            $transaction->order_id = $order->id;
            $transaction->mode = $request->mode;
            $transaction->status = "pending";
            $transaction->save();
        }

        Cart::instance('cart')->destroy();
        Session::forget('coupon');
        Session::forget('discounts');
        Session::forget('checkout');
        Session::put('order_id', $order->id);
        return redirect()->route('cart.order.confirmation');
    }

    public function setAmountforCheckout(){
        if(!Cart::instance('cart')->content()->count() > 0){
            session()->forget('checkout');
            return;
        }

        if(Session::has('coupon')){
            Session::put('checkout', [
                'discount' => Session::get('discounts')['discount'],
                'subtotal' => Session::get('discounts')['subtotal'],
                'tax' => Session::get('discounts')['tax'],
                'total' => Session::get('discounts')['total']
            ]);
        }
        else{
            Session::put('checkout', [
                'discount' => 0,
                'subtotal' => Cart::instance('cart')->subtotal(),
                'tax' => Cart::instance('cart')->tax(),
                'total' => Cart::instance('cart')->total()
            ]);
        }
    }

    public function order_confirmation()
    {
        if(Session::has('order_id')){
            $order = Order::find(Session::get('order_id'));
            return view('order-confirmation', compact('order'));
        }
        return redirect()->route('cart.index');
    }
}
