<?php
session_start();
require_once '../includes/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    if ($email && $pass) {
        $user = obtener_registro("SELECT * FROM usuarios WHERE email = ? AND activo = 1", [$email]);
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_name'] = $user['nombre'];
            header('Location: panel.php');
            exit;
        } else {
            $error = 'Email o contrasena incorrectos';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Chollo &amp; Glam</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#1a2332,#0d1520);padding:20px}
.card{background:#fff;border-radius:20px;padding:48px 40px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.logo{text-align:center;margin-bottom:32px}
.logo h1{font-family:'Playfair Display',serif;font-size:1.8rem;color:#1a2332;margin-bottom:4px}
.logo p{color:#999;font-size:.9rem}
.err{background:#fef2f2;color:#dc2626;padding:12px 16px;border-radius:10px;font-size:.9rem;margin-bottom:20px;display:flex;align-items:center;gap:8px;border:1px solid #fecaca}
.fg{margin-bottom:20px}
.fg label{display:block;font-weight:600;font-size:.85rem;color:#333;margin-bottom:8px}
.fg input{width:100%;padding:14px 16px;border:2px solid #e8e8e8;border-radius:12px;font-size:1rem;font-family:inherit;transition:all .3s;outline:none}
.fg input:focus{border-color:#c8a255;box-shadow:0 0 0 3px rgba(200,162,85,.15)}
.fg input::placeholder{color:#bbb}
.btn{width:100%;padding:14px;background:linear-gradient(135deg,#c8a255,#a07d35);color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:600;font-family:inherit;cursor:pointer;transition:all .3s;display:flex;align-items:center;justify-content:center;gap:8px}
.btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(200,162,85,.4)}
.back{text-align:center;margin-top:24px}
.back a{color:#999;font-size:.85rem;text-decoration:none}
.back a:hover{color:#c8a255}
.hint{text-align:center;margin-top:20px;padding:12px;background:#f8f8f8;border-radius:10px;font-size:.8rem;color:#888}
.hint strong{color:#666}
</style>
</head>
<body>
<div class="card">
<div class="logo"><h1>Chollo &amp; Glam</h1><p>Panel de Administracion</p></div>
<?php if($error): ?><div class="err"><i class="fas fa-exclamation-circle"></i> <?=$error?></div><?php endif; ?>
<form method="POST">
<div class="fg"><label><i class="fas fa-envelope"></i> Email</label><input type="email" name="email" placeholder="admin@cholloyglam.com" required></div>
<div class="fg"><label><i class="fas fa-lock"></i> Contrasena</label><input type="password" name="password" placeholder="********" required></div>
<button type="submit" class="btn"><i class="fas fa-sign-in-alt"></i> Iniciar Sesion</button>
</form>
<div class="hint"><strong>Demo:</strong> admin@cholloyglam.com / password</div>
<div class="back"><a href="../index.php"><i class="fas fa-arrow-left"></i> Volver a la tienda</a></div>
</div>
</body>
</html>
