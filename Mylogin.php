<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "users_db");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

$success_msg = "";
$error_msg = "";
$redirect_js = false; 
$current_state = "login-state"; 

// Form handle
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Registration Process
    if (isset($_POST['action']) && $_POST['action'] === 'register') {
        $current_state = "signup-state"; 

        $firstname = trim($_POST['firstname']);
        $lastname  = trim($_POST['lastname']);
        $username  = trim($_POST['username']);
        $email     = trim($_POST['email']);
        $phone     = trim($_POST['phone']);
        $dob       = trim($_POST['dob']);
        $password  = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            $error_msg = "Passwords do not match!";
        } else {
            // Check if user already exists
            $check = $conn->prepare("SELECT id FROM users2 WHERE email=? OR username=? OR phone=?");
            $check->bind_param("sss", $email, $username, $phone);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                $error_msg = "Account already exists with this information!";
            } else {
                // Reverse string task logic
                $rev_password = strrev($password);
                
                // Hashing the reversed password
                $hashed_password = password_hash($rev_password, PASSWORD_DEFAULT);

                // Insert into database
                $stmt = $conn->prepare("INSERT INTO users2 (firstname, lastname, username, email, phone, dob, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $firstname, $lastname, $username, $email, $phone, $dob, $hashed_password);

                if ($stmt->execute()) {
                    $success_msg = "Registration successful! You can log in now.";
                    $current_state = "login-state"; 
                } else {
                    $error_msg = "Registration failed! Please try again.";
                }
                $stmt->close();
            }
            $check->close();
        }
    }

    // Login Process
    else if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $current_state = "login-state"; 

        $login_input = trim($_POST['login_input']);
        $password    = $_POST['password'];

        $stmt = $conn->prepare("SELECT * FROM users2 WHERE email=? OR username=? OR phone=?");
        $stmt->bind_param("sss", $login_input, $login_input, $login_input);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Reverse input password to match with db
            $rev_login_pass = strrev($password);

            if (password_verify($rev_login_pass, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                $success_msg = "Login successful! Redirecting to dashboard...";
                $redirect_js = true; 
            } else {
                $error_msg = "Invalid password! Please try again.";
            }
        } else {
            $error_msg = "User account not found.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Stitch | Authentication</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "surface-variant": "#273647",
                        "on-background": "#d4e4fa",
                        "outline-variant": "#434655",
                        "outline": "#8d90a0",
                        "primary": "#2563eb"
                    },
                    "fontFamily": { "sans": ["Inter", "sans-serif"] }
                }
            }
        }
    </script>
    <?php if ($redirect_js): ?>
    <script>
        setTimeout(function() {
            window.location.href = "index.html";
        }, 2000);
    </script>
    <?php endif; ?>
    <style>
        .glass-morphism {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(0, 0, 0, 0.08);
        }
        .dark .glass-morphism {
            background: rgba(13, 28, 45, 0.36);
            border: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.4);
        }
        .auth-panel { transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1); }
        .signup-view {
            position: absolute; top: 0; right: 0; height: 100%; width: 100%;
            background-color: #2563eb; z-index: 30; pointer-events: none; opacity: 0;
            transform: translateX(100%); border-top-left-radius: 200px; border-bottom-left-radius: 200px;
        }
        .signup-state .signup-view {
            opacity: 1; pointer-events: auto; transform: translateX(0);
            border-top-left-radius: 0px; border-bottom-left-radius: 0px;
        }
        .login-state .login-view { opacity: 1; transform: translateX(0); }
        .signup-state .login-view { opacity: 0; transform: translateX(-20%); }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 22; }
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 4px; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-[#051424] text-slate-800 dark:text-[#d4e4fa] min-h-screen font-sans transition-colors duration-300 flex flex-col justify-between relative overflow-hidden">

    <div class="absolute top-[-10%] left-[-10%] w-[400px] h-[400px] rounded-full bg-blue-500/10 dark:bg-blue-600/10 blur-[100px] pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[400px] h-[400px] rounded-full bg-indigo-500/10 dark:bg-indigo-900/20 blur-[100px] pointer-events-none"></div>

    <header class="w-full z-50">
        <nav class="flex justify-between items-center w-full px-6 py-4 max-w-6xl mx-auto">
            <div class="text-xl font-bold text-primary tracking-tighter">UNIVERSITY</div>
            <button aria-label="Toggle Theme" class="w-9 h-9 flex items-center justify-center rounded-full bg-slate-200/60 dark:bg-surface-variant/20 text-slate-700 dark:text-[#c3c6d7] hover:text-primary transition-all duration-200" onclick="toggleTheme()">
                <span class="material-symbols-outlined" id="theme-icon">dark_mode</span>
            </button>
        </nav>
    </header>

    <main class="flex-1 flex items-center justify-center p-4 z-10 w-full">
        <div class="relative w-full max-w-3xl min-h-[550px] glass-morphism rounded-3xl overflow-hidden flex <?php echo $current_state; ?> transition-all duration-500 shadow-2xl" id="auth-container">
            
            <div class="w-full md:w-1/2 h-full min-h-[550px] relative flex flex-col justify-center">
                <div class="login-view auth-panel absolute inset-0 flex flex-col justify-center p-6 sm:p-10">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-1">Welcome Back</h2>
                    <p class="text-xs text-slate-500 dark:text-[#c3c6d7] mb-5">Access your account using your credentials.</p>
                    
                    <?php if (!empty($error_msg) && $current_state === 'login-state'): ?>
                        <div class="mb-4 p-3 rounded-xl text-xs bg-red-500/10 border border-red-500/20 text-red-500 font-medium">
                            <?php echo $error_msg; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success_msg) && $redirect_js): ?>
                        <div class="mb-4 p-4 rounded-xl text-sm bg-emerald-500 text-white font-semibold shadow-lg flex items-center gap-2">
                            <span class="material-symbols-outlined animate-spin">sync</span>
                            <?php echo $success_msg; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form class="space-y-4" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                        <input type="hidden" name="action" value="login">

                        <div class="relative group">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-outline group-focus-within:text-primary transition-colors">person</span>
                            <input name="login_input" id="login_input" class="w-full bg-slate-100 dark:bg-surface-variant/20 border border-slate-200 dark:border-outline-variant rounded-xl py-2.5 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-outline focus:ring-2 focus:ring-primary/40 focus:border-transparent outline-none transition-all" placeholder="Email, Username, or Phone" type="text" required/>
                        </div>

                        <div class="relative group">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-outline group-focus-within:text-primary transition-colors">lock</span>
                            <input name="password" id="login_password" class="w-full bg-slate-100 dark:bg-surface-variant/20 border border-slate-200 dark:border-outline-variant rounded-xl py-2.5 pl-10 pr-12 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-outline focus:ring-2 focus:ring-primary/40 focus:border-transparent outline-none transition-all" placeholder="Password" type="password" required/>
                            <button type="button" onclick="togglePasswordVisibility('login_password', 'eye_icon_login')" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-primary transition-colors flex items-center justify-center">
                                <span class="material-symbols-outlined text-xl" id="eye_icon_login">visibility_off</span>
                            </button>
                        </div>
                        
                        <div class="flex justify-between items-center text-[11px] font-medium">
                            <label class="flex items-center gap-1.5 cursor-pointer text-slate-500 dark:text-[#c3c6d7] hover:text-slate-800 dark:hover:text-white">
                                <input name="remember_me" class="rounded w-3.5 h-3.5 border-slate-300 dark:border-outline-variant bg-transparent text-primary focus:ring-primary" type="checkbox"/>
                                <span>Remember me</span>
                            </label>
                            <a class="text-primary hover:underline font-semibold" href="#">Forgot password?</a>
                        </div>
                        
                        <button type="submit" class="w-full bg-primary hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl shadow-md transition-all active:scale-[0.99] flex justify-center items-center gap-1.5 group text-sm">
                            Sign In
                            <span class="material-symbols-outlined transition-transform group-hover:translate-x-0.5 text-base">arrow_forward</span>
                        </button>
                    </form>
                    
                    <div class="mt-5">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="h-px bg-slate-200 dark:bg-outline-variant flex-1"></div>
                            <span class="text-[9px] uppercase tracking-widest font-bold text-slate-400 dark:text-outline">OR CONTINUE WITH</span>
                            <div class="h-px bg-slate-200 dark:bg-outline-variant flex-1"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                           <button type="button" class="flex items-center justify-center gap-2 py-2 rounded-xl border border-slate-200 dark:border-outline-variant hover:bg-slate-100 dark:hover:bg-surface-variant/30 text-slate-700 dark:text-white transition-colors text-xs font-medium">
                            <img alt="Google" class="w-4 h-4 object-contain" src="https://fonts.gstatic.com/s/i/productlogos/googleg/v6/web-24dp/logo_googleg_color_1x_web_24dp.png"/>
                            <span>Google</span>
                            </button>
                            <button type="button" class="flex items-center justify-center gap-2 py-2 rounded-xl border border-slate-200 dark:border-outline-variant hover:bg-slate-100 dark:hover:bg-surface-variant/30 text-slate-700 dark:text-white transition-colors text-xs font-medium">
                            <img alt="Github" class="w-4 h-4 object-contain dark:invert" src="https://github.githubassets.com/favicons/favicon.png"/>
                             <span>Github</span>
                             </button>
                        </div>
                    </div>
                    <div class="mt-4 md:hidden text-center">
                        <button class="text-primary text-xs font-bold underline" onclick="toggleAuth('signup')">Create Account</button>
                    </div>
                </div>
            </div>
            
            <div class="hidden md:flex w-1/2 bg-slate-100/40 dark:bg-white/[0.01] border-l border-slate-200/60 dark:border-white/5 relative overflow-hidden items-center justify-center p-8 visual-panel z-10 auth-panel">
                <div class="text-center space-y-3 relative z-10" id="visual-content">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-white">New here?</h3>
                    <p class="text-xs text-slate-500 dark:text-[#c3c6d7] max-w-[220px] mx-auto">Start your 14-day free trial today. No credit card required.</p>
                    <button class="mt-2 px-5 py-2 rounded-full border-2 border-white text-white hover:bg-white hover:text-black transition-all text-xs font-bold tracking-wide" onclick="toggleAuth('signup')">
                        CREATE ACCOUNT
                    </button>
                </div>
                <div class="absolute inset-0 pointer-events-none">
                    <img alt="Modern Tech Abstract" class="w-full h-full object-cover" src="https://images.unsplash.com/photo-1708898813390-50d28b9d0440?q=80&w=1228&auto=format&fit=crop"/>
                </div>
            </div>

            <div class="signup-view auth-panel flex flex-col justify-center p-6 sm:p-12 text-white overflow-y-auto custom-scroll">
                <button class="absolute top-5 right-5 text-white/60 hover:text-white transition-colors z-50" onclick="toggleAuth('login')">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
                
                <div class="max-w-md mx-auto w-full">
                    <h2 class="text-2xl font-bold mb-0.5">Create Account</h2>
                    <p class="text-xs opacity-80 mb-5">Join our community and start building.</p>

                    <?php if (!empty($error_msg) && $current_state === 'signup-state'): ?>
                        <div class="mb-4 p-3 rounded-xl text-xs bg-black/20 border border-white/20 text-white font-medium">
                            <?php echo $error_msg; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form class="space-y-3.5" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" onsubmit="return validateSignup()">
                        <input type="hidden" name="action" value="register">
                        
                        <div class="grid grid-cols-2 gap-3">
                            <input name="firstname" class="w-full bg-white/10 border border-white/20 rounded-xl py-2.5 px-4 text-xs text-white placeholder:text-white/50 focus:ring-1 focus:ring-white/40 focus:border-transparent outline-none transition-all" placeholder="First Name" type="text" required/>
                            <input name="lastname" class="w-full bg-white/10 border border-white/20 rounded-xl py-2.5 px-4 text-xs text-white placeholder:text-white/50 focus:ring-1 focus:ring-white/40 focus:border-transparent outline-none transition-all" placeholder="Last Name" type="text" required/>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <input name="username" class="w-full bg-white/10 border border-white/20 rounded-xl py-2.5 px-4 text-xs text-white placeholder:text-white/50 focus:ring-1 focus:ring-white/40 focus:border-transparent outline-none transition-all" placeholder="Username" type="text" required/>
                            <input name="phone" class="w-full bg-white/10 border border-white/20 rounded-xl py-2.5 px-4 text-xs text-white placeholder:text-white/50 focus:ring-1 focus:ring-white/40 focus:border-transparent outline-none transition-all" placeholder="Phone Number" type="tel" required/>
                        </div>

                        <div class="relative">
                            <label class="absolute left-4 top-1 text-[9px] opacity-60">Date of Birth</label>
                            <input name="dob" class="w-full bg-white/10 border border-white/20 rounded-xl pt-4 pb-1.5 px-4 text-xs text-white focus:ring-1 focus:ring-white/40 focus:border-transparent outline-none transition-all" type="date" required/>
                        </div>

                        <input name="email" class="w-full bg-white/10 border border-white/20 rounded-xl py-2.5 px-4 text-xs text-white placeholder:text-white/50 focus:ring-1 focus:ring-white/40 focus:border-transparent outline-none transition-all" placeholder="Email Address" type="email" required/>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div class="relative">
                                <input id="signup_password" name="password" class="w-full bg-white/10 border border-white/20 rounded-xl py-2.5 pl-4 pr-10 text-xs text-white placeholder:text-white/50 focus:ring-1 focus:ring-white/40 focus:border-transparent outline-none transition-all" placeholder="Password" type="password" required/>
                                <button type="button" onclick="togglePasswordVisibility('signup_password', 'eye_icon_signup1')" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/50 hover:text-white transition-colors flex items-center justify-center">
                                    <span class="material-symbols-outlined text-base" id="eye_icon_signup1">visibility_off</span>
                                </button>
                            </div>
                            <div class="relative">
                                <input id="signup_confirm_password" name="confirm_password" class="w-full bg-white/10 border border-white/20 rounded-xl py-2.5 pl-4 pr-10 text-xs text-white placeholder:text-white/50 focus:ring-1 focus:ring-white/40 focus:border-transparent outline-none transition-all" placeholder="Confirm Password" type="password" required/>
                                <button type="button" onclick="togglePasswordVisibility('signup_confirm_password', 'eye_icon_signup2')" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/50 hover:text-white transition-colors flex items-center justify-center">
                                    <span class="material-symbols-outlined text-base" id="eye_icon_signup2">visibility_off</span>
                                </button>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full bg-white text-primary hover:bg-slate-100 font-bold py-2.5 rounded-xl shadow-md transition-all active:scale-[0.98] flex justify-center items-center gap-1.5 text-sm">
                                Get Started
                                <span class="material-symbols-outlined text-sm">bolt</span>
                            </button>
                        </div>
                    </form>
                    <button class="mt-4 w-full text-center text-xs opacity-80 hover:opacity-100 transition-opacity" onclick="toggleAuth('login')">
                        Already have account? <span class="font-bold underline ml-1">Log In</span>
                    </button>
                </div>
            </div>

        </div>
    </main>

    <footer class="w-full bg-transparent border-t border-slate-200/50 dark:border-white/5 py-3.5 z-10">
        <div class="flex flex-col sm:flex-row justify-between items-center w-full px-6 max-w-6xl mx-auto gap-2 text-[11px] text-slate-400 dark:text-outline">
            <div class="font-semibold">© 2026 Sindh Agriculture University</div>
            <div class="flex gap-4">
                <a class="hover:text-primary transition-colors" href="#">Privacy</a>
                <a class="hover:text-primary transition-colors" href="#">Terms</a>
                <a class="hover:text-primary transition-colors" href="#">Help</a>
            </div>
        </div>
    </footer>

    <script>
        // Password Show/Hide karne ka logical function
        function togglePasswordVisibility(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const eyeIcon = document.getElementById(iconId);
            
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                eyeIcon.textContent = "visibility"; // Eye open icon
            } else {
                passwordInput.type = "password";
                eyeIcon.textContent = "visibility_off"; // Eye close icon
            }
        }

        function validateSignup() {
            const password = document.getElementById('signup_password').value;
            const confirmPassword = document.getElementById('signup_confirm_password').value;
            if (password !== confirmPassword) {
                alert("Confirm password matching nahi ho raha!");
                return false;
            }
            return true;
        }

        function toggleAuth(state) {
            const container = document.getElementById('auth-container');
            if (state === 'signup') {
                container.classList.remove('login-state');
                container.classList.add('signup-state');
            } else {
                container.classList.remove('signup-state');
                container.classList.add('login-state');
            }
        }

        function toggleTheme() {
            const html = document.documentElement;
            const icon = document.getElementById('theme-icon');
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                icon.textContent = 'light_mode';
            } else {
                html.classList.add('dark');
                icon.textContent = 'dark_mode';
            }
        }
    </script>
</body>
</html>