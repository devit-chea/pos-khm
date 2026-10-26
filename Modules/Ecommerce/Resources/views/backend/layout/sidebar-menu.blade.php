<li><a class="{{ request()->routeIs('slider.index', 'slidersCreate', 'slidersDelete') ? 'active' : '' }}" href="{{route('slider.index')}}">{{__('db.Sliders')}}</a></li>
<li><a class="{{ request()->routeIs('menu.index', 'menu.store', 'menu.update') ? 'active' : '' }}" href="{{route('menu.index')}}">{{__('db.Menu')}}</a></li>
<li><a class="{{ request()->routeIs('collection.*') ? 'active' : '' }}" href="{{route('collection.index')}}">{{__('db.Collections')}}</a></li>
<li><a class="{{ request()->routeIs('page.*') ? 'active' : '' }}" href="{{route('page.index')}}">{{__('db.Pages')}}</a></li>
<li><a class="{{ request()->routeIs('widget.*') ? 'active' : '' }}" href="{{route('widget.index')}}">{{__('db.Widgets')}}</a></li>
<li><a class="{{ request()->routeIs('faq.category.*') ? 'active' : '' }}" href="{{route('faq.category.index')}}">{{__('db.Faq Category')}}</a></li>
<li><a class="{{ request()->routeIs('faq.index', 'faq.store', 'faq.update') ? 'active' : '' }}" href="{{route('faq.index')}}">{{__('db.Faqs')}}</a></li>
<li><a class="{{ request()->routeIs('social.links*') ? 'active' : '' }}" href="{{route('social.links')}}">{{__('db.Social Links')}}</a></li>
<li><a class="{{ request()->routeIs('blog.post*') ? 'active' : '' }}" href="{{route('blog.post')}}">{{__('db.Blog')}}</a></li>
<li><a class="{{ request()->routeIs('setting.ecommerce.gateway') ? 'active' : '' }}" href="{{route('setting.gateway')}}">{{__('db.Payment Gateways')}}</a></li>
<li><a class="{{ request()->routeIs('setting.ecommerce', 'setting.ecommerce.update') ? 'active' : '' }}" href="{{route('setting.ecommerce')}}">{{__('db.settings')}}</a></li>
<li><a class="{{ request()->routeIs('reviews.*') ? 'active' : '' }}" href="{{route('reviews.index')}}">{{__('db.Product Review')}}</a></li>
<li><a class="{{ request()->routeIs('newsletter.*') ? 'active' : '' }}" href="{{route('newsletter.index')}}">{{__('db.newsletter')}}</a></li>
<li><a class="{{ request()->routeIs('delivery.area.*') ? 'active' : '' }}" href="{{route('delivery.area.index')}}">{{__('db.Delivery Areas')}}</a></li>
<li id="ecommerce-theme-menu"><a class="{{ request()->routeIs('setting.ecommerce.theme', 'setting.ecommerce.theme.update') ? 'active' : '' }}" href="{{route('setting.ecommerce.theme')}}">{{__('db.Theme')}}</a></li>
