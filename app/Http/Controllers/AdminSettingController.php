<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminSettingController extends Controller
{
    public function index()
    {
        return view('admin.settings-index');
    }


    public function update(Request $request)
    {
        $user = Auth::user(); // Get the logged-in user

        // Validate input fields
        $request->validate([
            'name'                  => 'required|string|max:255',
            'mobile'                => 'required|string|max:15',
            'email'                 => 'required|email|max:255|unique:users,email,' . $user->id,
            'old_password'          => 'nullable|string|min:6|required_with:new_password',
            'new_password'          => 'nullable|string|min:6|confirmed',
        ]);

        // Update user details (except password)
        $user->update([
            'name'   => $request->name,
            'mobile' => $request->mobile,
            'email'  => $request->email,
        ]);

        // Check if password update is requested
        if ($request->filled('old_password') && $request->filled('new_password')) {
            if (!Hash::check($request->old_password, $user->password)) {
                return back()->withErrors(['old_password' => 'The old password is incorrect!']);
            }

            // Update password
            $user->password = Hash::make($request->new_password);
            $user->save();
        }

        return back()->with('success', 'Settings updated successfully!');
    }


}
