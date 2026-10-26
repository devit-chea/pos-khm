@php
    $title = (string) data_get($settings, 'title', __('db.Deal of the Day'));
    $product_id = data_get($settings, 'product_id');
    $end_date = (string) data_get($settings, 'end_date', '');

    $product = $widget->prepared_product ?? null;
@endphp

@if($product && $end_date)
<section class="deal-of-the-day-section" style="padding: 80px 0; background: #fff8f8;">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6 text-center">
                <img loading="lazy" src="{{url('images/product')}}/{{$product->image}}" alt="{{$product->name}}" style="max-width: 80%; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            </div>
            <div class="col-md-6 mt-4 mt-md-0">
                <div class="deal-content" style="padding-left: 30px;">
                    <h5 style="color: #ff4757; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 10px;">{{ $title }}</h5>
                    <h2 style="font-size: 3rem; font-weight: 800; margin-bottom: 20px;">{{ $product->name }}</h2>
                    <div class="price" style="font-size: 2rem; font-weight: bold; color: #2ed573; margin-bottom: 30px;">
                        ${{ number_format($product->price, 2) }}
                    </div>

                    <div class="countdown-timer d-flex flex-wrap mb-4" id="deal-countdown-{{$widget->id}}" data-end-date="{{ $end_date }}">
                        <!-- JS injected timer -->
                    </div>

                    <a href="{{url('product/'.$product->slug.'/'.$product->id)}}" class="btn btn-primary btn-lg" style="padding: 15px 40px; border-radius: 50px; font-weight: bold; text-transform: uppercase;">{{__('db.Shop Now')}}</a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .countdown-timer .time-box {
        background: #fff;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        min-width: 80px;
        margin-right: 15px;
        margin-bottom: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    .countdown-timer .time-box span {
        display: block;
        font-size: 2.5rem;
        font-weight: 800;
        color: #222;
        line-height: 1;
    }
    .countdown-timer .time-box small {
        display: block;
        font-size: 0.8rem;
        color: #777;
        text-transform: uppercase;
        margin-top: 5px;
        font-weight: bold;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var countdownEl = document.getElementById('deal-countdown-{{$widget->id}}');
        if(!countdownEl) return;

        var endDateStr = countdownEl.getAttribute('data-end-date');
        // Handle Safari Date parsing issue
        endDateStr = endDateStr.replace(/-/g, '/');
        var endDate = new Date(endDateStr).getTime();

        var timer = setInterval(function() {
            var now = new Date().getTime();
            var distance = endDate - now;

            if (distance < 0) {
                clearInterval(timer);
                countdownEl.innerHTML = "<div class='time-box'><span>00</span><small>Days</small></div><div class='time-box'><span>00</span><small>Hours</small></div><div class='time-box'><span>00</span><small>Mins</small></div><div class='time-box'><span>00</span><small>Secs</small></div>";
                return;
            }

            var days = Math.floor(distance / (1000 * 60 * 60 * 24));
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);

            countdownEl.innerHTML = "<div class='time-box'><span>" + (days < 10 ? '0'+days : days) + "</span><small>Days</small></div>" +
                                   "<div class='time-box'><span>" + (hours < 10 ? '0'+hours : hours) + "</span><small>Hours</small></div>" +
                                   "<div class='time-box'><span>" + (minutes < 10 ? '0'+minutes : minutes) + "</span><small>Mins</small></div>" +
                                   "<div class='time-box'><span>" + (seconds < 10 ? '0'+seconds : seconds) + "</span><small>Secs</small></div>";
        }, 1000);
    });
</script>
@endif
