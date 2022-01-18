<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $pagination = User::query()
            ->orderBy('name')
            ->paginate(30);
        return view('users.search', [
            'pagination' => $pagination
        ]);
    }

    public function save(Request $request)
    {
        /**
         * @var User $user
         */
        $user = User::query()->where('id', $request->get('id'))->firstOrNew();
        if ($request->isMethod('POST')) {
            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'email' => [
                        'required',
                        'string',
                        'email',
                        'max:255',
                        Rule::unique('users')->ignore($user->id)
                    ],
                    'role' => 'required',
                    'password' => 'nullable|confirmed|min:6',
                    'password_confirmation' => 'nullable|min:6'
                ],
                [
                    'name.required' => __('Name is required.'),
                    'email.required' => __('Email is required.'),
                    'email.email' => __('Email is not valid.'),
                    'email.unique' => __('Email is already in use.'),
                    'password.confirmed' => __('Please confirm your password.'),
                    'password.min' => __('Password can be minimum 6 characters long.')
                ]
            );
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator);
            }
            $validated = $validator->validated();
            $user->role = $validated['role'];
            $user->name = $validated['name'];
            $user->email = $validated['email'];
           // $user->yahoo_app_id = $validated['yahoo_app_id'];
            if ($validated['password'] != '') {
                $user->password = Hash::make($validated['password']);
            }
            $user->save();
            if ($request->get('id') > 0) {
                return redirect()->back()->with('success', __('User has been updated!'));
            } else {
                return redirect()->back()->with('success', __('User has been created!'));
            }
        }
        return view('users.save', [
            'user' => $user
        ]);
    }

    public function delete(Request $request)
    {
        $id = $request->get('id');
        if (Auth::id() != $id) {
            User::query()->where('id', $id)->delete();
        }
        return redirect()->route('users.search')->with('success', __('User has been deleted!'));
    }
}
