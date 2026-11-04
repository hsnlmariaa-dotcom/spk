<?php
/**
 * Login Page - CDSS DBD
 * Clinical Decision Support System
 */

require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$error = '';
$username_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $username_val = htmlspecialchars($username);

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && $user['password'] === hash('sha256', $password)) {
            // Set session
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['username']    = $user['username'];
            $_SESSION['nama_lengkap']= $user['nama_lengkap'];
            $_SESSION['role']        = $user['role'];

            // Update last_login
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

            header('Location: ' . BASE_URL . '/index.php');
            exit();
        } else {
            $error = 'Username atau password salah. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — CDSS-DBD Clinical Decision Support System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:       #0ea5e9;
            --primary-dark:  #0284c7;
            --primary-glow:  rgba(14,165,233,0.35);
            --bg:            #060d1a;
            --card-bg:       rgba(255,255,255,0.04);
            --card-border:   rgba(255,255,255,0.09);
            --text:          #f1f5f9;
            --text-muted:    #94a3b8;
            --input-bg:      rgba(255,255,255,0.06);
            --input-border:  rgba(255,255,255,0.12);
            --input-focus:   rgba(14,165,233,0.5);
            --error-bg:      rgba(239,68,68,0.12);
            --error-border:  rgba(239,68,68,0.4);
            --error-text:    #fca5a5;
            --success:       #10b981;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Animated gradient background */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 20%, rgba(14,165,233,0.12) 0%, transparent 60%),
                radial-gradient(ellipse 60% 80% at 80% 80%, rgba(99,102,241,0.10) 0%, transparent 60%),
                radial-gradient(ellipse 50% 50% at 50% 50%, rgba(16,185,129,0.05) 0%, transparent 70%);
            z-index: 0;
            animation: bgPulse 8s ease-in-out infinite alternate;
        }

        @keyframes bgPulse {
            0%   { opacity: 0.7; transform: scale(1); }
            100% { opacity: 1;   transform: scale(1.05); }
        }

        /* Grid pattern overlay */
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: 0;
        }

        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            padding: 24px 16px;
            animation: fadeSlideUp 0.6s cubic-bezier(0.22,1,0.36,1) both;
        }

        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Brand logo area */
        .brand {
            text-align: center;
            margin-bottom: 32px;
        }

        .brand-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 0 0 1px rgba(14,165,233,0.3), 0 8px 32px rgba(14,165,233,0.25);
            animation: iconGlow 3s ease-in-out infinite alternate;
        }

        @keyframes iconGlow {
            0%   { box-shadow: 0 0 0 1px rgba(14,165,233,0.3), 0 8px 32px rgba(14,165,233,0.20); }
            100% { box-shadow: 0 0 0 1px rgba(14,165,233,0.5), 0 8px 48px rgba(14,165,233,0.40); }
        }

        .brand-icon svg { width: 34px; height: 34px; color: white; }

        .brand h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }

        .brand p {
            font-size: 0.82rem;
            color: var(--text-muted);
            font-weight: 400;
        }

        /* Card */
        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 36px 32px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.04) inset,
                0 24px 64px rgba(0,0,0,0.4);
        }

        .card-header {
            margin-bottom: 28px;
        }

        .card-header h2 {
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 4px;
        }

        .card-header p {
            font-size: 0.82rem;
            color: var(--text-muted);
        }

        /* Form */
        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
            display: flex;
            align-items: center;
        }

        .form-control {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 10px;
            padding: 12px 14px 12px 42px;
            font-size: 0.92rem;
            color: var(--text);
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
            outline: none;
        }

        .form-control::placeholder { color: rgba(148,163,184,0.5); }

        .form-control:focus {
            border-color: var(--primary);
            background: rgba(14,165,233,0.06);
            box-shadow: 0 0 0 3px var(--input-focus);
        }

        /* Password toggle */
        .toggle-pass {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            padding: 0;
            display: flex;
            align-items: center;
            transition: color 0.2s;
        }

        .toggle-pass:hover { color: var(--primary); }

        /* Error alert */
        .alert-error {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 0.84rem;
            color: var(--error-text);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%,100% { transform: translateX(0); }
            20%,60%  { transform: translateX(-6px); }
            40%,80%  { transform: translateX(6px); }
        }

        /* Submit button */
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            border-radius: 10px;
            padding: 13px;
            font-size: 0.95rem;
            font-weight: 600;
            color: white;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            margin-top: 8px;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.2px;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
            opacity: 0;
            transition: opacity 0.25s;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(14,165,233,0.40);
        }

        .btn-login:hover::before { opacity: 1; }
        .btn-login:active { transform: translateY(0); }

        .btn-login.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        /* User credentials hint */
        .credentials-hint {
            margin-top: 24px;
            padding: 14px;
            background: rgba(14,165,233,0.06);
            border: 1px solid rgba(14,165,233,0.15);
            border-radius: 10px;
        }

        .credentials-hint p {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 8px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cred-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            font-size: 0.82rem;
        }

        .cred-row + .cred-row { border-top: 1px solid rgba(255,255,255,0.05); margin-top: 4px; padding-top: 9px; }

        .cred-badge {
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 600;
        }

        .badge-admin { background: rgba(251,191,36,0.15); color: #fbbf24; }
        .badge-dokter { background: rgba(16,185,129,0.15); color: #10b981; }

        .cred-info { color: var(--text); font-weight: 500; }
        .cred-pass { color: var(--text-muted); font-family: monospace; font-size: 0.80rem; }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.75rem;
            color: rgba(148,163,184,0.5);
        }

        @media (max-width: 480px) {
            .login-card { padding: 28px 20px; }
        }
    </style>
</head>
<body>
<div class="login-wrapper">

    <!-- Brand -->
    <div class="brand">
        <div class="brand-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 14V6c0-1.1-.9-2-2-2H7c-1.1 0-2 .9-2 2v8H3v2h2v4c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2v-4h2v-2h-2z"/>
                <path d="M12 8v8"/><path d="M8 12h8"/>
            </svg>
        </div>
        <h1>CDSS-DBD</h1>
        <p>Clinical Decision Support System — Demam Berdarah Dengue</p>
    </div>

    <!-- Card -->
    <div class="login-card">
        <div class="card-header">
            <h2>Masuk ke Sistem</h2>
            <p>Gunakan kredensial akun yang telah diberikan</p>
        </div>

        <?php if ($error): ?>
        <div class="alert-error">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                        </svg>
                    </span>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        placeholder="Masukkan username"
                        value="<?= htmlspecialchars($username_val) ?>"
                        autocomplete="username"
                        required
                    >
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="toggle-pass" id="togglePass" aria-label="Tampilkan password">
                        <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login" id="btnLogin">
                Masuk ke Dashboard
            </button>
        </form>

        <!-- Credential Hints -->
        <div class="credentials-hint">
            <p>📋 Akun Default</p>
            <div class="cred-row">
                <span class="cred-badge badge-admin">Admin</span>
                <span class="cred-info">admin</span>
                <span class="cred-pass">admin123</span>
            </div>
            <div class="cred-row">
                <span class="cred-badge badge-dokter">Dokter</span>
                <span class="cred-info">dokter1</span>
                <span class="cred-pass">dokter123</span>
            </div>
        </div>
    </div>

    <div class="login-footer">
        CDSS-DBD v2.0 &bull; Metode Certainty Factor &bull; Standar WHO
    </div>
</div>

<script>
// Toggle password visibility
document.getElementById('togglePass').addEventListener('click', function () {
    const pw = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pw.type === 'password') {
        pw.type = 'text';
        icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
    } else {
        pw.type = 'password';
        icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }
});

// Loading state on submit
document.getElementById('loginForm').addEventListener('submit', function () {
    const btn = document.getElementById('btnLogin');
    btn.classList.add('loading');
    btn.textContent = 'Memverifikasi...';
});
</script>
</body>
</html>
