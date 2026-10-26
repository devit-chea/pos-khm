<?php

namespace Modules\Ecommerce\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Ecommerce\Services\DemoStorefrontProfile;

class DemoStorefrontController extends Controller
{
    public function switch(string $profile, DemoStorefrontProfile $demoProfile): RedirectResponse
    {
        abort_unless($demoProfile->enabled(), 404);
        abort_unless($demoProfile->select($profile), 404);

        return redirect('/');
    }
}
