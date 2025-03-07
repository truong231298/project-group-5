<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal; // Make sure the PayPal package is installed
use Illuminate\Support\Facades\Session;

class PaypalController extends Controller
{
    /**
     * Handle the PayPal payment cancellation.
     */
    public function cancel(Request $request)
    {
        // Optionally, update the order or transaction status as "canceled" in your database

        // Redirect back to the order confirmation or checkout page with an error message
        return redirect()->route('cart.order.confirmation')
            ->with('error', 'You have canceled your PayPal payment.');
    }

    /**
     * Handle the successful PayPal payment.
     */
    public function success(Request $request)
    {
        // Create an instance of the PayPal client
        $provider = new PayPal();

        // Set API credentials from configuration
        $provider->setApiCredentials(config('paypal'));
        $token = $provider->getAccessToken();
        $provider->setAccessToken($token);

        // Retrieve the PayPal order ID (often passed as a token)
        $paypalOrderId = $request->query('token');

        // Capture the payment
        $response = $provider->capturePaymentOrder($paypalOrderId);

        if (isset($response['status']) && $response['status'] === 'COMPLETED') {
            // Payment was successful; update your order and transaction status accordingly.
            // For example:
            // Order::find(Session::get('order_id'))->update(['payment_status' => 'paid']);

            return redirect()->route('cart.order.confirmation')
                ->with('success', 'Your PayPal payment was successful!');
        } else {
            // Payment failed; you can log the error or notify the user.
            return redirect()->route('cart.order.confirmation')
                ->with('error', 'Payment failed. Please try again.');
        }
    }
}
