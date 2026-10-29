<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - SIMASU Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        .password-container {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            background: none;
            border: none;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .toggle-password:hover {
            color: #2e7d32;
        }
        #password {
            padding-right: 45px; 
        }
    </style>
</head>
<body>

    <div class="hero">
        <div class="logo-wrap flex flex-col items-center">
            <img src="{{ asset('logo.png') }}" alt="SIMASU Logo" class="logo">
            <div class="brand">SIMASU</div>
        </div>
        <h1>Selamat Datang Kembali</h1>
        <div class="sub">Silakan masukkan kredensial admin Anda untuk melanjutkan ke dashboard pengelolaan.</div>
    </div>

    <div class="shell">
        <div class="card">
            
            @if ($errors->any())
                <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert" style="margin-bottom: 20px;">
                    <span class="font-medium">Gagal!</span> {{ $errors->first() }}
                </div>
            @endif

            @if (session('success'))
                <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert" style="margin-bottom: 20px;">
                    <span class="font-medium">Sukses!</span> {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('login.process') }}" method="POST">
                @csrf
                
                <div class="form-group mb-4">
                    <label class="label" for="email">Alamat Email</label>
                    <div class="input-wrap">
                        <input type="email" name="email" id="email" class="input" 
                               placeholder="admin@indrayuda.my.id" 
                               value="{{ old('email') }}" required autofocus>
                    </div>
                </div>

                <div class="form-group mb-6">
                    <label class="label" for="password">Kata Sandi</label>
                    <div class="input-wrap password-container">
                        <input type="password" name="password" id="password" class="input" 
                               placeholder="••••••••" required>
                        
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            
                            <svg id="eye-closed" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-full" style="width: 100%;">Masuk</button>
            
            </form>
            
            <div style="text-align: center; margin-top: 24px; color: #666; font-size: 14px;">
                &copy; {{ date('Y') }} SIMASU. All rights reserved.
            </div>

        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeOpen = document.getElementById('eye-open');
            const eyeClosed = document.getElementById('eye-closed');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeOpen.style.display = 'block';
                eyeClosed.style.display = 'none';
            } else {
                passwordInput.type = 'password';
                eyeOpen.style.display = 'none';
                eyeClosed.style.display = 'block';
            }
        }
    </script>

</body>
</html>