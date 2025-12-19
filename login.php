<?php
session_start();
include 'db.php';

$error = '';
$success = '';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $mode = $_POST['form_mode']; // 'login' or 'register'

    if ($mode === 'login') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Invalid email or password";
        }
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
            $stmt->execute([$email, $hashedPassword]);
            $success = "Account created! You can now log in.";
        } catch (PDOException $e) {
            $error = ($e->getCode() == 23000) ? "Email already registered" : "Registration failed";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" href="favicon.png" type="image/png">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TidyTasks • Welcome</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        :root {
            --brand: #6366f1;
            --brand-glow: rgba(99, 102, 241, 0.4);
            --bg: #030712;
            --card-bg: rgba(17, 24, 39, 0.7);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            background-image: radial-gradient(circle at 50% -20%, #1e1b4b 0%, transparent 50%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #f9fafb;
        }

        .auth-card {
            width: 100%;
            max-width: 440px;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 2.5rem;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .logo-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 32px;
        }

        .logo-icon {
            width: 56px;
            height: 56px;
            background: var(--brand);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 0 20px var(--brand-glow);
        }

        h2 { font-weight: 800; font-size: 1.75rem; letter-spacing: -0.025em; margin-bottom: 4px; }
        .subtitle { color: #9ca3af; font-size: 0.875rem; margin-bottom: 24px; text-align: center; }

        /* Toggle Switcher */
        .switcher {
            display: flex;
            background: rgba(0, 0, 0, 0.3);
            padding: 4px;
            border-radius: 14px;
            margin-bottom: 32px;
            position: relative;
        }

        .switcher button {
            flex: 1;
            padding: 10px;
            border: none;
            background: transparent;
            color: #9ca3af;
            font-weight: 700;
            font-size: 0.875rem;
            cursor: pointer;
            z-index: 2;
            transition: color 0.3s;
        }

        .switcher .active-bg {
            position: absolute;
            left: 4px;
            top: 4px;
            bottom: 4px;
            width: calc(50% - 4px);
            background: var(--brand);
            border-radius: 10px;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1;
        }

        .switcher button.active { color: white; }

        /* Form Styling */
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 0.75rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; margin-left: 4px; }
        
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #4b5563; font-size: 1.25rem; }

        input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            color: white;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        input:focus {
            outline: none;
            border-color: var(--brand);
            background: rgba(99, 102, 241, 0.05);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: var(--brand);
            color: white;
            border: none;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }

        .submit-btn:hover {
            background: #4f46e5;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        }

        .msg {
            padding: 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }
        .msg.error { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }
        .msg.success { background: rgba(16, 185, 129, 0.1); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.2); }

    </style>
</head>
<body>

    <div class="auth-card">
        <div class="logo-area">
            <div class="logo-icon">
                <i class="ri-checkbox-circle-fill text-3xl text-white"></i>
            </div>
            <h2 id="form-title">Welcome Back</h2>
            <p class="subtitle" id="form-subtitle">Login to your TidyTasks account</p>
        </div>

        <?php if($error): ?> <div class="msg error"><?= $error ?></div> <?php endif; ?>
        <?php if($success): ?> <div class="msg success"><?= $success ?></div> <?php endif; ?>

        <div class="switcher">
            <div class="active-bg" id="switcher-bg"></div>
            <button type="button" id="login-toggle" class="active" onclick="setMode('login')">Login</button>
            <button type="button" id="reg-toggle" onclick="setMode('register')">Register</button>
        </div>

        <form method="POST" id="auth-form">
            <input type="hidden" name="form_mode" id="form_mode" value="login">
            
            <div class="form-group">
                <label>Email Address</label>
                <div class="input-wrapper">
                    <i class="ri-mail-line"></i>
                    <input type="email" name="email" placeholder="name@email.com" required>
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-wrapper">
                    <i class="ri-lock-password-line"></i>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="submit-btn" id="submit-text">Sign In</button>
        </form>
    </div>

    <script>
        function setMode(mode) {
            const bg = document.getElementById('switcher-bg');
            const title = document.getElementById('form-title');
            const subtitle = document.getElementById('form-subtitle');
            const btnText = document.getElementById('submit-text');
            const modeInput = document.getElementById('form_mode');
            const loginBtn = document.getElementById('login-toggle');
            const regBtn = document.getElementById('reg-toggle');

            if (mode === 'register') {
                bg.style.transform = 'translateX(100%)';
                title.innerText = 'Create Account';
                subtitle.innerText = 'Start organizing your tasks today';
                btnText.innerText = 'Create Free Account';
                modeInput.value = 'register';
                regBtn.classList.add('active');
                loginBtn.classList.remove('active');
            } else {
                bg.style.transform = 'translateX(0%)';
                title.innerText = 'Welcome Back';
                subtitle.innerText = 'Login to your TidyTasks account';
                btnText.innerText = 'Sign In';
                modeInput.value = 'login';
                loginBtn.classList.add('active');
                regBtn.classList.remove('active');
            }
        }
    </script>
</body>
</html>
