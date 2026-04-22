<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar h3 {
            margin-bottom: 30px;
            border-bottom: 2px solid #34495e;
            padding-bottom: 15px;
        }
        .sidebar a {
            display: block;
            color: #ecf0f1;
            text-decoration: none;
            padding: 12px 15px;
            margin-bottom: 5px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .sidebar a:hover {
            background-color: #34495e;
            color: white;
        }
        .sidebar a.active {
            background-color: #3498db;
            color: white;
        }
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 20px;
        }
        .navbar-top {
            background-color: #34495e;
            color: white;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar-top .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .navbar-top a {
            color: white;
            text-decoration: none;
        }
        .navbar-top a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h3>Rayyan Admin</h3>
        <nav>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.users.index') }}" class="@if(Route::currentRouteName() === 'admin.users.index') active @endif">
                    👥 Manage Users
                </a>
                <a href="{{ route('admin.users.create') }}" class="@if(Route::currentRouteName() === 'admin.users.create') active @endif">
                    ➕ Add New User
                </a>
            @endif
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="navbar-top">
            <div>
                <h5 style="margin: 0;">@yield('page-title', 'Dashboard')</h5>
            </div>
            <div class="user-info">
                <span>{{ auth()->user()->name }}</span>
                <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-danger">Logout</button>
                </form>
            </div>
        </div>

        <!-- Page Content -->
        <div class="content">
            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
