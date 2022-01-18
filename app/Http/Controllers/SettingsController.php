<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ShippingFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


class SettingsController extends Controller
{
    public function index(Request  $request)
    {
        /**
         * @var User $user
         */

    
    
        if ($request->isMethod('POST')) {
            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'required|max:255',
                    'email' => ['email', 'required', Rule::unique('users')->ignore(Auth::user()->getAuthIdentifier())],
                    'password' => 'nullable|confirmed|min:6',
                    'password_confirmation' => 'nullable|min:6',
                    'shop_url' => 'required'
                ],
                [
                    'name.required' => __('Please fill the name field.'),
                    'email.required' => __('Email field is required.'),
                    'email.email' => __('Email is not valid.'),
                    'email.unique' => __('Email is already used.'),
                    'password.min' => __('Password is too short. It can be minimum 6 characters.'),
                    'password_confirmation.min' => __('Password is too short. It can be minimum 6 characters.'),
                    'password.confirmed' => __('Please make sure that Password and Password Confirmation fields are equal.'),
                    'shop_url.required' => __('Please fill the rakuten shop url.')
                ]
            );
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator);
            }
            $validated = $validator->validated();
            $user = Auth::user();
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            if ($validated['password']) {
                $user->password = Hash::make($validated['password']);
            }
            $user->yahoo_app_id = $request->get('yahoo_app_id');
            $user->rakuten_rms_secret = $request->get('rakuten_rms_secret');
            $user->rakuten_rms_licence = $request->get('rakuten_rms_licence');
            $user->rakuten_fee = $request->get('rakuten_fee');
            $user->shop_url = $validated['shop_url'];
            $user->save();
            return redirect()->back()->withInput()->with('success', __('Settings has been updated!'));
        }
        return view('settings', [
            'user' => Auth::user(),
            'shipping_fees' => ShippingFee::query()->where('user_id', Auth::id())->orderBy('fee')->get()
        ]);
    }

    public function save(Request $request)
    {
        /**
         * @var ShippingFee $shippingFee
         */
        $shippingFee = ShippingFee::query()
            ->where('user_id', Auth::id())
            ->where('id', $request->get('id'))
            ->firstOrNew();
        if ($request->isMethod('POST')) {
            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'fee' => 'required',
                    'depth' => 'nullable',
                    'width' => 'nullable',
                    'height' => 'nullable',
                    'kg' => 'nullable',
                    'totalDWH' => 'nullable'
                    
                ]
            );
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator);
            }
            $validated = $validator->validated();
            
            $shippingFee->user_id = Auth::id();
            $shippingFee->name = $validated['name'];
            $shippingFee->fee = str_replace(',', '.', $validated['fee']);
            $shippingFee->depth = str_replace(',', '.', $validated['depth']);
            $shippingFee->width = str_replace(',', '.', $validated['width']);
            $shippingFee->height = str_replace(',', '.', $validated['height']);
            $shippingFee->kg = str_replace(',', '.', $validated['kg']);
            $shippingFee->totalDWH = str_replace(',', '.', $validated['totalDWH']);
            $shippingFee->save();
            if ($request->get('id') > 0) {
                return redirect()->back()->with('success', __(trans('Shipping method has been updated!')));
            } else {
                return redirect()->route('settings')->with('success', __(trans('Shipping method has been created!')));
            }
        }
        return view('shipping-settings', [
            'shipping_fee' => $shippingFee
        ]);
    }

    public function delete(Request $request)
    {
        $id = $request->get('id');
        ShippingFee::query()->where('user_id', Auth::id())->where('id', $id)->delete();
        return redirect()->route('settings')->with('success', __(trans('Shipping method has been deleted!')));
    }


}
