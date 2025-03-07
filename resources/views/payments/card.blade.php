<!DOCTYPE html>
<html>
<head>
    <title>Complete Your Payment</title>
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
<h1>Pay with Card</h1>
<form id="payment-form">
    <div id="card-element"></div>
    <button id="submit">Pay</button>
    <div id="error-message"></div>
</form>

<script>
    const stripe = Stripe('{{ env("STRIPE_KEY") }}');
    const elements = stripe.elements();
    const cardElement = elements.create('card');
    cardElement.mount('#card-element');

    const form = document.getElementById('payment-form');
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const { error, paymentIntent } = await stripe.confirmCardPayment('{{ $clientSecret }}', {
            payment_method: {
                card: cardElement,
            }
        });

        if (error) {
            document.getElementById('error-message').textContent = error.message;
        } else {
            if (paymentIntent.status === 'succeeded') {
                window.location.href = "{{ route('cart.order.confirmation') }}?status=success";
            }
        }
    });
</script>
</body>
</html>
