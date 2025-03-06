@extends('layouts.admin')

@section('content')
    <div class="main-content-inner">
        <div class="main-content-wrap">
            <div class="flex items-center flex-wrap justify-between gap20 mb-27">
                <h3>Users</h3>
                <ul class="breadcrumbs flex items-center flex-wrap justify-start gap10">
                    <li>
                        <a href="{{ route('admin.index') }}">
                            <div class="text-tiny">Dashboard</div>
                        </a>
                    </li>
                    <li>
                        <i class="icon-chevron-right"></i>
                    </li>
                    <li>
                        <div class="text-tiny">All User</div>
                    </li>
                </ul>
            </div>

            <div class="wg-box">
                <div class="flex items-center justify-between gap10 flex-wrap">
                    <div class="wg-filter flex-grow">
                        <form class="form-search" method="GET" action="{{ route('admin.users') }}">
                            <fieldset class="name">
                                <input type="text" placeholder="Search here..." name="search" value="{{ request('search') }}">
                            </fieldset>
                            <div class="button-submit">
                                <button type="submit"><i class="icon-search"></i></button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="wg-table table-all-user">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th class="text-center">Total Orders</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
{{--                            @foreach($users as $user)--}}
{{--                                <tr>--}}
{{--                                    <td>{{ $loop->iteration }}</td>--}}
{{--                                    <td class="pname">--}}
{{--                                        <div class="image">--}}
{{--                                            <img src="{{ asset('uploads/users/' . $user->avatar) }}" alt="{{ $user->name }}" class="image">--}}
{{--                                        </div>--}}
{{--                                        <div class="name">--}}
{{--                                            <a href="#" class="body-title-2">{{ $user->name }}</a>--}}
{{--                                            <div class="text-tiny mt-3">{{ strtoupper($user->role) }}</div>--}}
{{--                                        </div>--}}
{{--                                    </td>--}}
{{--                                    <td>{{ $user->phone ?? 'N/A' }}</td>--}}
{{--                                    <td>{{ $user->email }}</td>--}}
{{--                                    <td class="text-center">--}}
{{--                                        <a href="#" target="_blank">{{ $user->orders->count() }}</a>--}}
{{--                                    </td>--}}
{{--                                </tr>--}}
{{--                            @endforeach--}}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="divider"></div>
{{--                <div class="flex items-center justify-between flex-wrap gap10 wgp-pagination">--}}
{{--                    {{ $users->links('pagination::boostrap-5') }}--}}
{{--                </div>--}}
            </div>
        </div>
    </div>
@endsection
