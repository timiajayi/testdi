<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <nav>
                <a href="{{ route('admin.users') }}">Manage Users</a>
                <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn">Logout</button>
                </form>
            </nav>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Users</h3>
                <p class="stat-number">{{ $totalUsers }}</p>
            </div>
            <div class="stat-card">
                <h3>Total Admins</h3>
                <p class="stat-number">{{ $totalAdmins }}</p>
            </div>
        </div>
    </div>
</body>
</html>
