@extends('ecommerce::frontend.layout.main')

@section('content')
<section class="my-5">
    <div class="container">
        <div class="col-lg-6 mx-auto">
            <h1 class="text-center">{{ __('db.Secure payment') }}</h1>
            <p class="text-center">
                {{ $attempt->currency_code }}
                {{ number_format((float) $attempt->provider_amount, $attempt->minor_unit) }}
            </p>
            <div id="payment-provider-container"></div>
            <div id="payment-error" class="alert alert-danger d-none"></div>
            <button id="payment-start" class="button style1 d-block w-100">{{ __('db.Continue to payment') }}</button>
        </div>
    </div>
</section>
@endsection

@section('script')
@if($attempt->provider === 'stripe')
<script src="https://js.stripe.com/v3/"></script>
@elseif($attempt->provider === 'razorpay')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
@endif
<script>
(() => {
    const start = document.getElementById('payment-start');
    const errorBox = document.getElementById('payment-error');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const initiateUrl = @json(route('payment.initiate', $attempt->provider));
    const completeUrl = @json(route('payment.complete', $attempt->provider));
    const provider = @json($attempt->provider);

    const post = async (url, body = {}) => {
        const response = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
            body: JSON.stringify(body)
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'Payment could not be processed.');
        return result;
    };
    const finish = async body => {
        const result = await post(completeUrl, body);
        window.location.assign(result.redirect);
    };

    const returnedIntent = new URLSearchParams(window.location.search).get('payment_intent');
    if (provider === 'stripe' && returnedIntent) {
        start.disabled = true;
        finish({payment_intent_id: returnedIntent}).catch(error => {
            errorBox.textContent = error.message || 'Payment could not be verified.';
            errorBox.classList.remove('d-none');
        });
        return;
    }

    start.addEventListener('click', async () => {
        start.disabled = true;
        errorBox.classList.add('d-none');
        try {
            const setup = await post(initiateUrl);
            if (provider === 'stripe') {
                const stripe = Stripe(setup.publishable_key);
                const elements = stripe.elements({clientSecret: setup.client_secret});
                elements.create('payment').mount('#payment-provider-container');
                start.textContent = @json(__('db.Pay'));
                start.disabled = false;
                start.onclick = async event => {
                    event.preventDefault();
                    start.disabled = true;
                    const result = await stripe.confirmPayment({
                        elements,
                        confirmParams: {return_url: window.location.href},
                        redirect: 'if_required'
                    });
                    if (result.error) throw result.error;
                    await finish({payment_intent_id: result.paymentIntent.id});
                };
            } else if (provider === 'paypal') {
                start.classList.add('d-none');
                const script = document.createElement('script');
                script.src = `https://www.paypal.com/sdk/js?client-id=${encodeURIComponent(setup.client_id)}&currency=${encodeURIComponent(setup.currency)}`;
                script.onload = () => paypal.Buttons({
                        createOrder: () => setup.order_id,
                        onApprove: data => finish({order_id: data.orderID})
                    }).render('#payment-provider-container');
                document.head.appendChild(script);
            } else if (provider === 'razorpay') {
                new Razorpay({
                    key: setup.key,
                    amount: setup.amount,
                    currency: setup.currency,
                    order_id: setup.order_id,
                    handler: finish
                }).open();
            }
        } catch (error) {
            errorBox.textContent = error.message || 'Payment could not be processed.';
            errorBox.classList.remove('d-none');
            start.disabled = false;
        }
    });
})();
</script>
@endsection
