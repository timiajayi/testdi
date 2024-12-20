<!DOCTYPE html>
<html>
<head>
    <title>Create User</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Create New User</h1>
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

        <form class="admin-form" method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="password_confirmation" required>
            </div>
            
            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    <option value="staff">Staff</option>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>


            <button type="submit" class="btn btn-primary">Create User</button>
        </form>
    </div>
</body>
</html>
