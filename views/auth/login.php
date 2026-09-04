<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo"><span class="dot"></span>Radius</div>
                <h1 style="font-size:18px; margin:0 0 4px;">Sistem Analisis Radius Cabang</h1>
                <p class="text-muted" style="margin:0;">Internal tool — tim marketing</p>
            </div>

            <?php if ($error = flash("error")): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="<?php echo APP_URL; ?>/do-login">
                <div class="form-group" style="text-align:left;">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars(old("email")); ?>" required autofocus>
                </div>

                <div class="form-group" style="text-align:left;">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-full">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
