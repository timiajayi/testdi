<!DOCTYPE html>
<html>
<head>
    <title>Login - ID Card Generator</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .company-header {
            text-align: center;
            margin-bottom: 30px;
        }


        .company-logo {
            max-width: 200px;
            max-height: 100px;
        }

        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        input[type="text"],
        input[type="file"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        button {
            background: #007bff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }

        button:hover {
            background: #0056b3;
        }

        .preview-container {
            display: flex;
            gap: 30px;
            margin-top: 30px;
            justify-content: center;
        }

        .preview-box {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            flex: 1;
            max-width: 400px;
        }

        .preview-image {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
        }

        .loader {
            display: none;
            text-align: center;
            margin: 20px 0;
        }

        .loader-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-radius: 50%;
            border-top: 5px solid #007bff;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        .download-btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .download-btn:hover {
            background: #218838;
        }

        #cropper-container {
            width: 400px;
            height: 400px;
            margin: 0 auto;
        }

        .cr-viewport {
            border-radius: 50%;
        }

        #crop-button {
            display: block;
            margin: 10px auto;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        #crop-button:hover {
            background: #0056b3;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .secondary-btn {
            background: #6c757d;
            margin-left: 10px;
        }

        .secondary-btn:hover {
            background: #5a6268;
        }

    </style>
</head>
<body>
    <div class="form-container">
        <div class="company-header">
            <img src="./templates/logo.png" alt="Company Logo" class="company-logo">
            <h1>ID Card Generator</h1>
        </div>
        <?php if (isset($error)) { echo "<p style='color: red;'>$error</p>"; } ?>
        <form method="post" action="login.php">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
