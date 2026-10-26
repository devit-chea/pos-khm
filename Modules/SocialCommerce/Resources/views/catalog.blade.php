@extends('backend.layout.main')
@section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<style>
    .sc-catalog-head { gap: 12px; flex-wrap: wrap; }
    .sc-catalog-head h4 { margin: 0; font-size: 1.1rem; font-weight: 600; }
    .sc-catalog-filter { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .sc-product-cell { min-width: 220px; }
    .sc-product-name { font-weight: 600; color: #34395e; }
    .sc-thumb { width: 56px; height: 56px; object-fit: cover; border-radius: 6px; border: 1px solid #eef0f4; background: #f8f9fa; }
    .sc-link-row { min-width: 260px; }
    .sc-share-actions { display: flex; gap: 4px; flex-wrap: wrap; }
    .sc-share-actions .btn, .sc-publish-actions .btn { min-width: 38px; }
    .sc-publish-actions form { margin: 0; }
    .sc-empty { padding: 28px 12px; color: #6c757d; }
    @media (max-width: 767px) {
        .sc-catalog-filter, .sc-catalog-filter select { width: 100%; }
    }
</style>

<section>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center sc-catalog-head">
                        <h4>{{ __('db.Social Commerce Catalog') }}</h4>
                        <form action="{{ route('socialcommerce.catalog') }}" method="GET" class="sc-catalog-filter">
                            <label class="mb-0">{{ __('db.Filter') }}:</label>
                            <select name="filter" class="form-control" onchange="this.form.submit()">
                                <option value="all" {{ $filter == 'all' ? 'selected' : '' }}>{{ __('db.All Active Products') }}</option>
                                <option value="published" {{ $filter == 'published' ? 'selected' : '' }}>{{ __('db.Published') }}</option>
                                <option value="unpublished" {{ $filter == 'unpublished' ? 'selected' : '' }}>{{ __('db.Not Published') }}</option>
                                <option value="missing_image" {{ $filter == 'missing_image' ? 'selected' : '' }}>{{ __('db.Missing Image') }}</option>
                                <option value="out_of_stock" {{ $filter == 'out_of_stock' ? 'selected' : '' }}>{{ __('db.Out of Stock') }}</option>
                            </select>
                        </form>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('db.Product Name') }}</th>
                                        <th>{{ __('db.Code/SKU') }}</th>
                                        <th>{{ __('db.Price') }}</th>
                                        <th>{{ __('db.Stock') }}</th>
                                        <th>{{ __('db.Social Status') }}</th>
                                        <th>{{ __('db.Public Link') }}</th>
                                        <th>{{ __('db.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($products as $product)
                                        @php
                                            $isPublished = in_array($product->id, $publishedIds);
                                            $scSetting = $settings->get($product->id);
                                            $hasImage = !empty($product->image) && $product->image !== 'zummXD2dvAtI.png';
                                            $isValid = $hasImage && $product->price > 0 && !empty($product->name);
                                            $toggleUrl = route('socialcommerce.togglePublish', $product->id);
                                        @endphp
                                        <tr data-product-id="{{ $product->id }}" data-is-valid="{{ $isValid ? '1' : '0' }}" data-toggle-url="{{ $toggleUrl }}">
                                            <td class="sc-product-cell">
                                                @if($hasImage)
                                                    @php
                                                        $images = explode(',', $product->image);
                                                        $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
                                                    @endphp
                                                    <img src="{{ asset($asset_prefix . 'images/product/'.$images[0]) }}" class="sc-thumb mr-2" alt="{{ $product->name }}">
                                                @else
                                                    <span class="badge badge-warning mr-2">{{ __('db.No Image') }}</span>
                                                @endif
                                                <span class="sc-product-name">{{ $product->name }}</span>
                                            </td>
                                            <td>{{ $product->code }}</td>
                                            <td>{{ number_format((float)$product->price, config('decimal', 2), '.', '') }}</td>
                                            <td>
                                                @if($product->qty > 0)
                                                    <span class="badge badge-success">{{ $product->qty }} {{ __('db.in stock') }}</span>
                                                @else
                                                    <span class="badge badge-danger">{{ __('db.Out of Stock') }}</span>
                                                @endif
                                            </td>
                                            <td class="sc-status-cell">
                                                @if($isPublished)
                                                    <span class="badge badge-success mb-2 d-inline-block sc-status-badge">{{ __('db.Published') }}</span>
                                                @else
                                                    <span class="badge badge-secondary sc-status-badge">{{ __('db.Not Published') }}</span>
                                                @endif
                                                @if(!$isValid)
                                                    <br><small class="text-danger sc-invalid-warning">{{ __('db.Missing requirements') }}</small>
                                                @endif
                                            </td>
                                            <td class="sc-link-row sc-links-cell">
                                                @if($isPublished)
                                                    @php
                                                        $publicLink = $product->social_commerce_url;
                                                        $shareText = ($scSetting->social_title ?: $product->name) . ' - ' . ($scSetting->social_description ?: strip_tags($product->product_details));
                                                        $whatsAppUrl = 'https://wa.me/?' . http_build_query(['text' => $shareText . ' ' . $publicLink], '', '&', PHP_QUERY_RFC3986);
                                                        $facebookUrl = 'https://www.facebook.com/sharer/sharer.php?' . http_build_query(['u' => $publicLink], '', '&', PHP_QUERY_RFC3986);
                                                        $telegramUrl = 'https://t.me/share/url?' . http_build_query(['url' => $publicLink, 'text' => $shareText], '', '&', PHP_QUERY_RFC3986);
                                                    @endphp
                                                    <div class="sc-link-container">
                                                        <div class="input-group input-group-sm mb-2">
                                                            <input type="text" class="form-control sc-link-input" value="{{ $publicLink }}" readonly aria-label="{{ __('db.Public Link') }}">
                                                            <button class="btn btn-outline-secondary sc-copy-btn" type="button" title="{{ __('db.Copy link') }}" aria-label="{{ __('db.Copy link') }}" data-url="{{ $publicLink }}">
                                                                <i class="ti ti-copy"></i>
                                                            </button>
                                                        </div>
                                                        <div class="sc-share-actions">
                                                            <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-success sc-wa-btn" title="{{ __('db.Share on WhatsApp') }}"><i class="ti ti-brand-whatsapp"></i></a>
                                                            <a href="{{ $facebookUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-primary sc-fb-btn" title="{{ __('db.Share on Facebook') }}"><i class="ti ti-brand-facebook"></i></a>
                                                            <a href="{{ $telegramUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-info sc-tg-btn" title="{{ __('db.Share on Telegram') }}"><i class="ti ti-brand-telegram"></i></a>
                                                            <button type="button" class="btn btn-sm btn-dark sc-qr-btn" data-url="{{ route('socialcommerce.qr', $product->id) }}" title="{{ __('db.View QR Code') }}" aria-label="{{ __('db.View QR Code') }}"><i class="ti ti-qrcode"></i></button>
                                                            <a href="{{ $publicLink }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary sc-view-btn" title="{{ __('db.View product') }}"><i class="ti ti-external-link"></i></a>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-muted sc-no-link">-</span>
                                                @endif
                                            </td>
                                            <td class="sc-publish-actions">
                                                @if($isPublished)
                                                    <form action="{{ $toggleUrl }}" method="POST" class="sc-toggle-form" data-product-id="{{ $product->id }}" data-action="unpublish">
                                                        @csrf
                                                        <input type="hidden" name="unpublish" value="1">
                                                        <button type="submit" class="btn btn-sm btn-danger sc-toggle-btn sc-unpublish-btn">{{ __('db.Unpublish') }}</button>
                                                    </form>
                                                @else
                                                    <form action="{{ $toggleUrl }}" method="POST" class="sc-toggle-form" data-product-id="{{ $product->id }}" data-action="publish">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-primary sc-toggle-btn sc-publish-btn" {{ !$isValid ? 'disabled' : '' }}>{{ __('db.Publish') }}</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">{{ __('db.No products found for this filter') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            {{ $products->appends(['filter' => $filter])->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
    var currentFilter = @json($filter);
    var I18N = {
        published: @json(__('db.Published')),
        notPublished: @json(__('db.Not Published')),
        publish: @json(__('db.Publish')),
        unpublish: @json(__('db.Unpublish')),
        publishing: @json(__('db.Publishing...')),
        unpublishing: @json(__('db.Unpublishing...')),
        publicLink: @json(__('db.Public Link')),
        copyLink: @json(__('db.Copy link')),
        linkCopied: @json(__('db.Link copied')),
        unableToCopy: @json(__('db.Unable to copy link')),
        shareWhatsApp: @json(__('db.Share on WhatsApp')),
        shareFacebook: @json(__('db.Share on Facebook')),
        shareTelegram: @json(__('db.Share on Telegram')),
        viewQr: @json(__('db.View QR Code')),
        viewProduct: @json(__('db.View product')),
        confirmation: @json(__('db.Confirmation')),
        unpublishTitle: @json(__('db.Unpublish Confirmation') ?? __('db.Confirmation')),
        unpublishConfirmMsg: @json(__('db.Are you sure you want to unpublish this product?')),
        cancel: @json(__('db.Cancel')),
        genericError: @json(__('db.Something went wrong. Please try again.')),
        publishedSuccess: @json(__('db.Product published to social commerce catalogue')),
        unpublishedSuccess: @json(__('db.Product unpublished successfully')),
        noProductsFound: @json(__('db.No products found for this filter'))
    };

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.getAttribute('content')) {
            return meta.getAttribute('content');
        }
        var input = document.querySelector('input[name="_token"]');
        return input ? input.value : '';
    }

    document.addEventListener('click', function(e) {
        var copyBtn = e.target.closest('.sc-copy-btn');
        if (copyBtn) {
            copySocialCommerceUrl(copyBtn);
            return;
        }

        var qrBtn = e.target.closest('.sc-qr-btn');
        if (qrBtn && qrBtn.dataset.url) {
            window.open(qrBtn.dataset.url, '_blank', 'noopener,width=400,height=400');
        }
    });

    document.addEventListener('submit', function(e) {
        var form = e.target.closest('.sc-toggle-form');
        if (!form) return;

        e.preventDefault();

        var row = form.closest('tr');
        var productId = form.getAttribute('data-product-id') || row?.getAttribute('data-product-id');
        var action = form.getAttribute('data-action') || (form.querySelector('input[name="unpublish"]') ? 'unpublish' : 'publish');
        var btn = form.querySelector('.sc-toggle-btn') || form.querySelector('button[type="submit"]');

        if (!btn || btn.disabled || row?.dataset.busy === 'true') {
            return;
        }

        if (action === 'unpublish') {
            if (window.SaleProConfirm && typeof window.SaleProConfirm.open === 'function') {
                window.SaleProConfirm.open({
                    title: I18N.unpublishTitle || I18N.confirmation,
                    message: I18N.unpublishConfirmMsg,
                    type: 'warning',
                    confirmText: I18N.unpublish,
                    cancelText: I18N.cancel
                }).then(function(confirmed) {
                    if (confirmed) {
                        executeAjaxToggle(form, row, productId, action, btn);
                    }
                });
                return;
            }
        }

        executeAjaxToggle(form, row, productId, action, btn);
    });

    function executeAjaxToggle(form, row, productId, action, btn) {
        var toggleUrl = form.getAttribute('action') || row?.getAttribute('data-toggle-url');
        var csrfToken = getCsrfToken();
        var isValid = row ? row.getAttribute('data-is-valid') === '1' : true;
        var originalHtml = btn.innerHTML;

        if (row) {
            row.dataset.busy = 'true';
            var allBtns = row.querySelectorAll('.sc-publish-actions button, .sc-publish-actions a');
            allBtns.forEach(function(b) { b.disabled = true; });
        }
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span>' + (action === 'publish' ? I18N.publishing : I18N.unpublishing);

        var formData = new FormData(form);

        fetch(toggleUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(function(response) {
            return response.json().then(function(data) {
                return {
                    status: response.status,
                    ok: response.ok,
                    data: data
                };
            }).catch(function() {
                return {
                    status: response.status,
                    ok: response.ok,
                    data: {
                        success: false,
                        message: I18N.genericError
                    }
                };
            });
        })
        .then(function(res) {
            if (row) {
                delete row.dataset.busy;
            }

            if (res.ok && res.data && res.data.success) {
                updateRowState(row, res.data, toggleUrl, isValid);
                if (window.SaleProToast && typeof window.SaleProToast.success === 'function') {
                    window.SaleProToast.success(res.data.message || (res.data.published ? I18N.publishedSuccess : I18N.unpublishedSuccess));
                } else if (window.SaleProToast && typeof window.SaleProToast.show === 'function') {
                    window.SaleProToast.show(res.data.message || (res.data.published ? I18N.publishedSuccess : I18N.unpublishedSuccess), 'success');
                }
            } else {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                if (row) {
                    var allBtns = row.querySelectorAll('.sc-publish-actions button, .sc-publish-actions a');
                    allBtns.forEach(function(b) { b.disabled = false; });
                }
                var errMsg = (res.data && res.data.message) ? res.data.message : I18N.genericError;
                if (window.SaleProToast && typeof window.SaleProToast.error === 'function') {
                    window.SaleProToast.error(errMsg);
                } else if (window.SaleProToast && typeof window.SaleProToast.show === 'function') {
                    window.SaleProToast.show(errMsg, 'error');
                }
            }
        })
        .catch(function(err) {
            if (row) {
                delete row.dataset.busy;
                var allBtns = row.querySelectorAll('.sc-publish-actions button, .sc-publish-actions a');
                allBtns.forEach(function(b) { b.disabled = false; });
            }
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            if (window.SaleProToast && typeof window.SaleProToast.error === 'function') {
                window.SaleProToast.error(I18N.genericError);
            } else if (window.SaleProToast && typeof window.SaleProToast.show === 'function') {
                window.SaleProToast.show(I18N.genericError, 'error');
            }
        });
    }

    function updateRowState(row, data, toggleUrl, isValid) {
        if (!row) return;

        var shouldRemove = false;
        if (currentFilter === 'published' && !data.published) {
            shouldRemove = true;
        } else if (currentFilter === 'unpublished' && data.published) {
            shouldRemove = true;
        }

        if (shouldRemove) {
            if (window.jQuery) {
                $(row).fadeOut(300, function() {
                    var tbody = row.closest('tbody');
                    row.remove();
                    if (tbody && tbody.querySelectorAll('tr[data-product-id]').length === 0) {
                        tbody.innerHTML = '<tr class="sc-empty-row"><td colspan="7" class="text-center">' + escapeHtml(I18N.noProductsFound) + '</td></tr>';
                    }
                });
            } else {
                var tbody = row.closest('tbody');
                row.remove();
                if (tbody && tbody.querySelectorAll('tr[data-product-id]').length === 0) {
                    tbody.innerHTML = '<tr class="sc-empty-row"><td colspan="7" class="text-center">' + escapeHtml(I18N.noProductsFound) + '</td></tr>';
                }
            }
            return;
        }

        var statusBadge = row.querySelector('.sc-status-badge');
        var linksCell = row.querySelector('.sc-links-cell');
        var actionsCell = row.querySelector('.sc-publish-actions');
        var csrfToken = getCsrfToken();
        var productId = data.product_id || row.getAttribute('data-product-id');

        if (data.published) {
            // Update Status Badge
            if (statusBadge) {
                statusBadge.className = 'badge badge-success mb-2 d-inline-block sc-status-badge';
                statusBadge.textContent = I18N.published;
            }

            // Update Links Cell
            if (linksCell) {
                var publicUrl = data.public_url || '';
                var qrUrl = data.qr_url || '';
                var waUrl = data.whatsapp_url || ('https://wa.me/?' + encodeURIComponent(publicUrl));
                var fbUrl = data.facebook_url || ('https://www.facebook.com/sharer/sharer.php?' + encodeURIComponent(publicUrl));
                var tgUrl = data.telegram_url || ('https://t.me/share/url?' + encodeURIComponent(publicUrl));

                linksCell.innerHTML = 
                    '<div class="sc-link-container">' +
                        '<div class="input-group input-group-sm mb-2">' +
                            '<input type="text" class="form-control sc-link-input" value="' + escapeHtml(publicUrl) + '" readonly aria-label="' + escapeHtml(I18N.publicLink) + '">' +
                            '<button class="btn btn-outline-secondary sc-copy-btn" type="button" title="' + escapeHtml(I18N.copyLink) + '" aria-label="' + escapeHtml(I18N.copyLink) + '" data-url="' + escapeHtml(publicUrl) + '">' +
                                '<i class="ti ti-copy"></i>' +
                            '</button>' +
                        '</div>' +
                        '<div class="sc-share-actions">' +
                            '<a href="' + escapeHtml(waUrl) + '" target="_blank" rel="noopener" class="btn btn-sm btn-success sc-wa-btn" title="' + escapeHtml(I18N.shareWhatsApp) + '"><i class="ti ti-brand-whatsapp"></i></a> ' +
                            '<a href="' + escapeHtml(fbUrl) + '" target="_blank" rel="noopener" class="btn btn-sm btn-primary sc-fb-btn" title="' + escapeHtml(I18N.shareFacebook) + '"><i class="ti ti-brand-facebook"></i></a> ' +
                            '<a href="' + escapeHtml(tgUrl) + '" target="_blank" rel="noopener" class="btn btn-sm btn-info sc-tg-btn" title="' + escapeHtml(I18N.shareTelegram) + '"><i class="ti ti-brand-telegram"></i></a> ' +
                            '<button type="button" class="btn btn-sm btn-dark sc-qr-btn" data-url="' + escapeHtml(qrUrl) + '" title="' + escapeHtml(I18N.viewQr) + '" aria-label="' + escapeHtml(I18N.viewQr) + '"><i class="ti ti-qrcode"></i></button> ' +
                            '<a href="' + escapeHtml(publicUrl) + '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary sc-view-btn" title="' + escapeHtml(I18N.viewProduct) + '"><i class="ti ti-external-link"></i></a>' +
                        '</div>' +
                    '</div>';
            }

            // Update Action Button
            if (actionsCell) {
                actionsCell.innerHTML = 
                    '<form action="' + escapeHtml(toggleUrl) + '" method="POST" class="sc-toggle-form" data-product-id="' + escapeHtml(productId) + '" data-action="unpublish">' +
                        '<input type="hidden" name="_token" value="' + escapeHtml(csrfToken) + '">' +
                        '<input type="hidden" name="unpublish" value="1">' +
                        '<button type="submit" class="btn btn-sm btn-danger sc-toggle-btn sc-unpublish-btn">' + escapeHtml(I18N.unpublish) + '</button>' +
                    '</form>';
            }
        } else {
            // Update Status Badge
            if (statusBadge) {
                statusBadge.className = 'badge badge-secondary sc-status-badge';
                statusBadge.textContent = I18N.notPublished;
            }

            // Update Links Cell
            if (linksCell) {
                linksCell.innerHTML = '<span class="text-muted sc-no-link">-</span>';
            }

            // Update Action Button
            if (actionsCell) {
                actionsCell.innerHTML = 
                    '<form action="' + escapeHtml(toggleUrl) + '" method="POST" class="sc-toggle-form" data-product-id="' + escapeHtml(productId) + '" data-action="publish">' +
                        '<input type="hidden" name="_token" value="' + escapeHtml(csrfToken) + '">' +
                        '<button type="submit" class="btn btn-sm btn-primary sc-toggle-btn sc-publish-btn"' + (!isValid ? ' disabled' : '') + '>' + escapeHtml(I18N.publish) + '</button>' +
                    '</form>';
            }
        }
    }

    function copySocialCommerceUrl(btn) {
        var url = btn.getAttribute('data-url');
        if (!url) return;
        
        var successMsg = I18N.linkCopied;
        var errorMsg = I18N.unableToCopy;
        
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(function() {
                if (window.SaleProToast && typeof window.SaleProToast.show === 'function') {
                    window.SaleProToast.show(successMsg);
                }
            }).catch(function(err) {
                fallbackCopyTextToClipboard(url, successMsg, errorMsg);
            });
        } else {
            fallbackCopyTextToClipboard(url, successMsg, errorMsg);
        }
    }

    function fallbackCopyTextToClipboard(text, successMsg, errorMsg) {
        var textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            var successful = document.execCommand('copy');
            if (successful) {
                if (window.SaleProToast && typeof window.SaleProToast.show === 'function') {
                    window.SaleProToast.show(successMsg);
                }
            } else {
                if (window.SaleProToast && typeof window.SaleProToast.show === 'function') {
                    window.SaleProToast.show(errorMsg);
                }
            }
        } catch (err) {
            if (window.SaleProToast && typeof window.SaleProToast.show === 'function') {
                window.SaleProToast.show(errorMsg);
            }
        }
        document.body.removeChild(textArea);
    }
</script>
@endpush
@endsection
