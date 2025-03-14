<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/asyst.png') }}">
    <link rel="stylesheet" href="{{ asset('css/style2.css') }}">
</head>

<body>
    <div class="login-container">
        <form action="{{ route('login.post') }}" method="POST">
            @csrf
            <h2>Silahkan Log In</h2>
            <div class="form-group">
                <input type="email" placeholder="Alamat Email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <input type="password" placeholder="Masukkan Password" id="password" name="password" required>
            </div>
            <button type="submit">LOG IN</button>
        </form>
    </div>
</body>

</html>
