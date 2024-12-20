<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Edit User</h1>
            <a href="{{ route('admin.users') }}" class="btn">Back to Users</a>
        </div>

        @if($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="admin-form" method="POST" action="{{ route('admin.users.update', $user->id) }}">
            @csrf
            @method('PUT')
            
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
            </div>

            <div class="form-group">
                <label>New Password (leave blank to keep current)</label>
                <input type="password" name="password">
            </div>

            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="password_confirmation">
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    <option value="staff" {{ $user->role === 'staff' ? 'selected' : '' }}>Staff</option>
                    <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>User</option>
                    <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Update User</button>
        </form>
    </div>
</body>
</html>
