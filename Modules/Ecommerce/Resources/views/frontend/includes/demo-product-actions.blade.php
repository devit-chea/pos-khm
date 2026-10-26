<script>
"use strict";
(function ($) {
    $(document).on('click', '.add-to-cart', function (event) {
        event.preventDefault();
        var button = $(this), id = button.data('id'), original = button.html();
        var qty = $('#add_to_cart_' + id + ' input[name=qty]').val() || 1;
        button.prop('disabled', true);
        $.post(@json(route('addToCart')), {product_id: id, qty: qty, _token: @json(csrf_token())})
            .done(function (response) {
                if (response && response.success) {
                    $('.alert').addClass('alert-custom show').find('.message').html(response.success);
                    $('.cart__menu .cart_qty').html(response.total_qty);
                    $('.cart__menu .total').html(response.currency_code + (response.subTotal * response.currency_rate).toFixed(2));
                    window.setTimeout(function () { $('.alert').removeClass('show'); }, 4000);
                }
            }).always(function () { button.prop('disabled', false).html(original); });
    });
})(window.jQuery);
</script>
