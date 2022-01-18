<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function index(Request $request)
    {
        if ($request->isMethod('POST')) {
            $validated = $request->validate([
                'email' => 'required',
                'password' => 'required'
            ]);
            if (Auth::attempt($validated)) {
                $request->session()->regenerate();
                 return redirect()->route('home');
            }
            return back()->with('error', __('Wrong email or password!'));
        }
        return view('login');
    }
}
