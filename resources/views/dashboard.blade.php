@extends('layouts.app')

@section('page-title', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Welcome, {{ auth()->user()->name }}!</h5>
                <p class="card-text">You are logged in as <strong>{{ ucfirst(auth()->user()->role) }}</strong></p>
                
                @if(auth()->user()->isAdmin())
                    <div class="alert alert-info">
                        <h6>Admin Panel</h6>
                        <p>You have access to the admin dashboard. Use the menu on the left to manage users and system settings.</p>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">👥 User Management</h6>
                                    <p class="card-text">Manage all users in the system</p>
                                    <a href="{{ route('admin.users.index') }}" class="btn btn-primary btn-sm">Go to Users</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">➕ Add New User</h6>
                                    <p class="card-text">Create a new admin or regular user</p>
                                    <a href="{{ route('admin.users.create') }}" class="btn btn-success btn-sm">Add User</a>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-success">
                        <h6>Welcome to Rayyan</h6>
                        <p>You are a regular user. You can access the research review features.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
