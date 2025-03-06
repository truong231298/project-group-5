<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminSettingController extends Controller
{
    public function index()
    {
        return view('admin.settings-index');
    }


    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name'                  => 'required|string|max:255',
            'mobile'                => 'required|string|max:15',
            'email'                 => 'required|email|max:255',
            'old_password'          => 'required_with:new_password|string',
            'new_password'          => 'nullable|string|min:6|confirmed',
        ]);


        $user->update([
            'name'   => $request->name,
            'mobile' => $request->mobile,
            'email'  => $request->email,
        ]);


        if ($request->filled('old_password') && $request->filled('new_password')) {

            if (Hash::check($request->old_password, $user->password)) {
                $user->password = Hash::make($request->new_password);
                $user->save();
            } else {
                return back()->withErrors(['old_password' => 'wrong password!']);
            }
        }

        return back()->with('success', 'Settings updated successfully!');
    }
}
