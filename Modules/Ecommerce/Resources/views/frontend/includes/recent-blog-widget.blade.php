@php
    $title = (string) data_get($settings, 'title', __('db.Latest from the Blog'));
    $limit = (int) data_get($settings, 'limit', 3);

    $posts = $widget->prepared_posts ?? collect();
@endphp

@if(count($posts) > 0)
<section class="recent-blog-section" style="padding: 80px 0; background: #f8f9fa;">
    <div class="container-fluid">
        <div class="section-title text-center mb-5">
            <h3 style="font-weight: 700; font-size: 2.2rem; color: #222;">{{ $title }}</h3>
            <p style="color: #666; max-width: 600px; margin: 10px auto 0;">{{__('db.Read our latest news, articles, and updates')}}</p>
        </div>

        <div class="row justify-content-center">
            @foreach($posts as $post)
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="blog-card" style="background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.04); transition: all 0.3s ease; height: 100%; display: flex; flex-direction: column;">
                    <div class="blog-img" style="overflow: hidden; height: 260px; position: relative;">
                        <a href="{{ url('blog/'.$post->slug) }}">
                            <img loading="lazy" src="{{ isset($post->thumbnail) && $post->thumbnail && file_exists(public_path('frontend/images/blog/'.$post->thumbnail)) ? url('frontend/images/blog/'.$post->thumbnail) : 'https://placehold.co/600x400/eee/999?text=Blog+Post' }}" alt="{{ $post->title }}" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;">
                        </a>
                        <div class="blog-date" style="position: absolute; top: 20px; right: 20px; background: #fff; padding: 10px; border-radius: 8px; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                            <span style="display: block; font-size: 1.5rem; font-weight: 800; color: #222; line-height: 1;">{{ date('d', strtotime($post->created_at)) }}</span>
                            <span style="display: block; font-size: 0.8rem; font-weight: 600; color: #ff4757; text-transform: uppercase;">{{ date('M', strtotime($post->created_at)) }}</span>
                        </div>
                    </div>
                    <div class="blog-content" style="padding: 30px; flex-grow: 1; display: flex; flex-direction: column;">
                        <h4 style="font-weight: 700; margin-bottom: 15px; font-size: 1.3rem; line-height: 1.4;">
                            <a href="{{ url('blog/'.$post->slug) }}" style="color: #222; text-decoration: none; transition: color 0.3s ease;" class="blog-title-link">{{ Str::limit($post->title, 60) }}</a>
                        </h4>
                        <p style="color: #666; margin-bottom: 25px; line-height: 1.6; font-size: 0.95rem; flex-grow: 1;">
                            {{ Str::limit(strip_tags($post->short_description ?? ($post->description ?? '')), 120) }}
                        </p>
                        <a href="{{ url('blog/'.$post->slug) }}" class="read-more" style="color: #222; font-weight: 700; text-transform: uppercase; font-size: 0.85rem; text-decoration: none; letter-spacing: 1px; display: inline-flex; align-items: center; transition: color 0.3s ease;">
                            {{__('db.Read Article')}} <span class="material-symbols-outlined" style="margin-left: 5px; font-size: 18px; transition: transform 0.3s ease;">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<style>
    .blog-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.08) !important;
    }
    .blog-card:hover .blog-img img {
        transform: scale(1.08);
    }
    .blog-card:hover .blog-title-link {
        color: #ff4757 !important;
    }
    .blog-card:hover .read-more {
        color: #ff4757 !important;
    }
    .blog-card:hover .read-more span {
        transform: translateX(5px);
    }
</style>
@endif
