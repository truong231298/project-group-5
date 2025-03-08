@extends('layouts.admin')

@section('content')
    <style>
        .text-danger {
            font-size: initial;
            line-height: 36px;
        }

        .alert {
            font-size: initial;
        }
    </style>
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

    <div class="main-content-inner">
        <div class="main-content-wrap">
            <div class="flex items-center flex-wrap justify-between gap20 mb-27">
                <h3>Settings</h3>
                <ul class="breadcrumbs flex items-center flex-wrap justify-start gap10">
                    <li>
                        <a href="#">
                            <div class="text-tiny">Dashboard</div>
                        </a>
                    </li>
                    <li>
                        <i class="icon-chevron-right"></i>
                    </li>
                    <li>
                        <div class="text-tiny">Settings</div>
                    </li>
                </ul>
            </div>
            <div class="wg-box">
                <div class="col-lg-12">
                    <div class="page-content my-account__edit">
                        <div class="my-account__edit-form">

                            <form name="account_edit_form" action="{{ route('admin.settings.update') }}" method="POST"
                                  class="form-new-product form-style-1 needs-validation"
                                  novalidate="">
                                @csrf
                                <fieldset class="name">
                                    <div class="body-title">Name <span class="tf-color-1">*</span>
                                    </div>
                                    <input class="flex-grow" type="text" placeholder="Full Name"
                                           name="name" tabindex="0" value="{{ auth()->user()->name }}" aria-required="true"
                                           required="">
                                </fieldset>

                                <fieldset class="name">
                                    <div class="body-title">Mobile Number <span
                                            class="tf-color-1">*</span></div>
                                    <input class="flex-grow" type="text" placeholder="Mobile Number"
                                           name="mobile" tabindex="0" value="{{ auth()->user()->mobile }}" aria-required="true"
                                           required="">
                                </fieldset>

                                <fieldset class="name">
                                    <div class="body-title">Email Address <span
                                            class="tf-color-1">*</span></div>
                                    <input class="flex-grow" type="text" placeholder="Email Address"
                                           name="email" tabindex="0" value="{{ auth()->user()->email }}" aria-required="true"
                                           required="">
                                </fieldset>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="my-3">
                                            <h5 class="text-uppercase mb-0">Password Change</h5>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <fieldset class="name">
                                            <div class="body-title pb-3">Old password <span
                                                    class="tf-color-1">*</span>
                                            </div>
                                            <input class="flex-grow" type="password"
                                                   placeholder="Old password" id="old_password"
                                                   name="old_password" aria-required="true"
                                                   required="">
                                        </fieldset>

                                    </div>
                                    <div class="col-md-12">
                                        <fieldset class="name">
                                            <div class="body-title pb-3">New password <span
                                                    class="tf-color-1">*</span>
                                            </div>
                                            <input class="flex-grow" type="password"
                                                   placeholder="New password" id="new_password"
                                                   name="new_password" aria-required="true"
                                                   required="">
                                        </fieldset>

                                    </div>
                                    <div class="col-md-12">
                                        <fieldset class="name">
                                            <div class="body-title pb-3">Confirm new password <span
                                                    class="tf-color-1">*</span></div>
                                            <input class="flex-grow" type="password"
                                                   placeholder="Confirm new password" cfpwd=""
                                                   data-cf-pwd="#new_password"
                                                   id="new_password_confirmation"
                                                   name="new_password_confirmation"
                                                   aria-required="true" required="">
                                            <div class="invalid-feedback">Passwords did not match!
                                            </div>
                                        </fieldset>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="my-3">
                                            <button type="submit"
                                                    class="btn btn-primary tf-button w208">Save
                                                Changes</button>
                                        </div>
                                    </div>

@endsection
