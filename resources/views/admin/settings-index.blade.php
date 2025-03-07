@extends('layouts.admin')

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form name="account_edit_form" action="{{ route('admin.settings.update') }}" method="POST" class="form-new-product form-style-1 needs-validation" novalidate>
        @csrf
        <!-- Các field nhập liệu -->
        <fieldset class="name">
            <div class="body-title">Name <span class="tf-color-1">*</span></div>
            <input class="flex-grow" type="text" placeholder="Full Name" name="name" tabindex="0" value="{{ auth()->user()->name }}" aria-required="true" required>
        </fieldset>
        <fieldset class="name">
            <div class="body-title pb-3">email <span class="tf-color-1">*</span></div>
            <input class="flex-grow" type="text" placeholder="email" id="email" name="email" aria-required="true" required>
        </fieldset>
        <!-- Tương tự cho Mobile, Email -->
        <fieldset class="name">
            <div class="body-title pb-3">Mobile <span class="tf-color-1">*</span></div>
            <input class="flex-grow" type="password" placeholder="mobile" id="mobile" name="mobile" aria-required="true" required>
        </fieldset>
        <!-- Phần thay đổi mật khẩu -->
        <fieldset class="name">
            <div class="body-title pb-3">Old password <span class="tf-color-1">*</span></div>
            <input class="flex-grow" type="password" placeholder="Old password" id="old_password" name="old_password" aria-required="true" required>
        </fieldset>
        <fieldset class="name">
            <div class="body-title pb-3">New password <span class="tf-color-1">*</span></div>
            <input class="flex-grow" type="password" placeholder="New password" id="new_password" name="new_password" aria-required="true" required>
        </fieldset>
        <fieldset class="name">
            <div class="body-title pb-3">Confirm new password <span class="tf-color-1">*</span></div>
            <input class="flex-grow" type="password" placeholder="Confirm new password" id="new_password_confirmation" name="new_password_confirmation" aria-required="true" required>
            <div class="invalid-feedback">Passwords did not match!</div>
        </fieldset>
        <button type="submit" class="btn btn-primary tf-button w208">Save Changes</button>
    </form>

@endsection
