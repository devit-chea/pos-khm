@extends('ecommerce::frontend.layout.main')

@section('content')
<section class="my-5">
    <div class="container">
        <div class="col-lg-6 mx-auto text-center">
            <h1>{{ __('db.Payment pending') }}</h1>
            <p>
                {{ __('db.This payment provider is awaiting a certified server verification adapter. No payment has been recorded.') }}
            </p>
            <p>
                {{ $attempt->currency_code }}
                {{ number_format((float) $attempt->provider_amount, $attempt->minor_unit) }}
            </p>
            <a class="button style1" href="{{ route('checkout') }}">{{ __('db.Return to checkout') }}</a>
        </div>
    </div>
</section>
@endsection
