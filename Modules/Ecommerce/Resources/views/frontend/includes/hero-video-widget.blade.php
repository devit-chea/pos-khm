@php
    $video_url = (string) data_get($settings, 'video_url', '');
    $title = (string) data_get($settings, 'title', '');
    $subtitle = (string) data_get($settings, 'subtitle', '');
    $button_text = (string) data_get($settings, 'button_text', '');
    $button_link = (string) data_get($settings, 'button_link', '');

    // Convert standard youtube link to autoplay background embed
    if (strpos($video_url, 'youtube.com/watch?v=') !== false) {
        $video_id = explode('v=', $video_url)[1];
        $video_id = explode('&', $video_id)[0];
        $video_url = "https://www.youtube.com/embed/{$video_id}?autoplay=1&mute=1&loop=1&playlist={$video_id}&controls=0&showinfo=0&rel=0";
    }
    $has_valid_video = filter_var($video_url, FILTER_VALIDATE_URL) !== false
        && in_array(parse_url($video_url, PHP_URL_SCHEME), ['http', 'https'], true);
@endphp

@if($has_valid_video)
<section class="hero-video-section" style="position: relative; overflow: hidden; min-height: 60vh; display: flex; align-items: center; justify-content: center; text-align: center; color: white;">
    <div class="video-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1;"></div>
    <div class="video-background" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0;">
        <iframe src="{{ $video_url }}" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" style="width: 100vw; height: 56.25vw; min-height: 100vh; min-width: 177.77vh; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); pointer-events: none;"></iframe>
    </div>
    <div class="hero-content" style="position: relative; z-index: 2; padding: 50px; max-width: 800px;">
        @if($title) <h1 style="font-size: 3.5rem; font-weight: 800; margin-bottom: 20px; color: #fff; letter-spacing: -1px; text-shadow: 0 4px 10px rgba(0,0,0,0.3);">{{ $title }}</h1> @endif
        @if($subtitle) <p style="font-size: 1.25rem; margin-bottom: 30px; color: #f8f9fa; font-weight: 400; text-shadow: 0 2px 5px rgba(0,0,0,0.3);">{{ $subtitle }}</p> @endif
        @if($button_text) <a href="{{ $button_link }}" class="btn btn-primary btn-lg" style="padding: 15px 40px; font-size: 1.1rem; border-radius: 50px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 10px 20px rgba(0,0,0,0.2); transition: all 0.3s ease;">{{ $button_text }}</a> @endif
    </div>
</section>
@endif
