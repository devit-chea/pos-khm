<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Currency;
use App\Models\GeneralSetting;
use Auth;
use Cache;

class CurrencyController extends Controller
{
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('currency')) {
            $lims_currency_all = Currency::where('is_active', true)->get();
            return view('backend.currency.index', compact('lims_currency_all'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function store(Request $request)
    {
        $data = $this->posFields($request, $request->all());
        Currency::create($data);
        $this->forgetCurrencyCache();
        return redirect()->back()->with('message', __('db.Currency created successfully'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();
        if($data['exchange_rate'] == 1) {
            GeneralSetting::latest()->first()->update(['currency' => $data['currency_id']]);
        }
        Currency::find($data['currency_id'])->update($this->posFields($request, $data));
        $this->forgetCurrencyCache();
        return redirect()->back()->with('message', __('db.Currency updated successfully'));
    }

    public function destroy($id)
    {
        if(!env('USER_VERIFIED'))
            return redirect()->back()->with('not_permitted', __('db.This feature is disable for demo!'));
        Currency::find($id)->update(['is_active' => false]);
        $this->forgetCurrencyCache();
        return redirect()->back()->with('message', __('db.Currency deleted successfully'));
    }

    // Unchecked checkbox isn't submitted; empty rounding means "use app decimals"
    private function posFields(Request $request, array $data): array
    {
        $data['show_in_pos'] = $request->boolean('show_in_pos');
        $data['rounding'] = $request->filled('rounding') ? $request->input('rounding') : null;
        return $data;
    }

    // POS reads the active currency list from cache (Common middleware)
    private function forgetCurrencyCache(): void
    {
        cache()->forget('currency');
        cache()->forget('currency_list');
    }
}
