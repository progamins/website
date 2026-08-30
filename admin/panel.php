<?php
session_start();
require_once "db_config.php";
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) { header("Location: login.php"); exit; }
$totalP = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM productos"))["t"];
$totalC = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM categorias"))["t"];
$totalCo = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM colecciones"))["t"];
$totalT = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM testimonios"))["t"];
$sinStock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM productos WHERE stock = 0"))["t"];
$dest = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM productos WHERE destacado = 1"))["t"];
$productos = mysqli_fetch_all(mysqli_query($conn, "SELECT p.*, c.nombre as cat_nombre FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id ORDER BY p.id DESC LIMIT 8"), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh;display:flex}
.sb{width:260px;background:linear-gradient(180deg,#0f172a 0%,#1e293b 100%);border-right:1px solid rgba(200,162,85,.15);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:100;transition:transform .3s cubic-bezier(.4,0,.2,1)}
.sb-h{padding:24px 20px;border-bottom:1px solid rgba(200,162,85,.1)}
.sb-h h2{font-size:1.2rem;color:#c8a255;font-weight:700}
.sb-h span{font-size:.75rem;color:#64748b;text-transform:uppercase;letter-spacing:1px}
.sb-n{flex:1;padding:16px 12px;overflow-y:auto}
.sb-n a{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:12px;color:#94a3b8;text-decoration:none;font-size:.875rem;font-weight:500;transition:all .2s ease;margin-bottom:4px}
.sb-n a:hover,.sb-n a.act{background:rgba(200,162,85,.1);color:#c8a255}
.sb-n a.act{background:rgba(200,162,85,.15);box-shadow:inset 3px 0 0 #c8a255}
.sb-n .st{font-size:.65rem;color:#475569;text-transform:uppercase;letter-spacing:2px;padding:16px 16px 8px;font-weight:700}
.sb-f{padding:16px 12px;border-top:1px solid rgba(200,162,85,.1)}
.sb-f a{display:flex;align-items:center;gap:10px;padding:10px 16px;border-radius:10px;color:#94a3b8;text-decoration:none;font-size:.8rem;transition:all .2s}
.sb-f a:hover{background:rgba(200,162,85,.1);color:#c8a255}
.mn{margin-left:260px;flex:1;min-height:100vh;display:flex;flex-direction:column}
.tb{background:#1e293b;border-bottom:1px solid rgba(200,162,85,.1);padding:16px 32px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:50;backdrop-filter:blur(12px)}
.tb h1{font-size:1.4rem;font-weight:700;color:#f1f5f9}
.mt{display:none;background:rgba(200,162,85,.1);border:none;color:#c8a255;width:40px;height:40px;border-radius:10px;cursor:pointer;font-size:1.1rem}
.tb-r .usr{display:flex;align-items:center;gap:10px}
.tb-r .av{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#c8a255,#a07c3a);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem}
.ct{padding:32px;flex:1}
.al{background:linear-gradient(135deg,rgba(245,158,11,.15),rgba(239,68,68,.1));border:1px solid rgba(245,158,11,.3);border-radius:16px;padding:16px 24px;display:flex;align-items:center;gap:12px;margin-bottom:28px;color:#fbbf24;font-weight:500;font-size:.9rem;animation:slideIn .4s ease}
.sg{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:28px}
.sc{background:#1e293b;border:1px solid rgba(200,162,85,.1);border-radius:16px;padding:24px;display:flex;flex-direction:column;gap:12px;transition:all .3s cubic-bezier(.4,0,.2,1);animation:fadeUp .5s ease both}
.sc:nth-child(2){animation-delay:.1s}.sc:nth-child(3){animation-delay:.2s}.sc:nth-child(4){animation-delay:.3s}.sc:nth-child(5){animation-delay:.4s}.sc:nth-child(6){animation-delay:.5s}
.sc:hover{transform:translateY(-4px);box-shadow:0 12px 40px rgba(0,0,0,.3);border-color:rgba(200,162,85,.25)}
.ic{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.1rem}
.ic.g{background:rgba(16,185,129,.15);color:#10b981}
.ic.b{background:rgba(59,130,246,.15);color:#3b82f6}
.ic.gr{background:rgba(200,162,85,.15);color:#c8a255}
.ic.p{background:rgba(139,92,246,.15);color:#8b5cf6}
.ic.r{background:rgba(239,68,68,.15);color:#ef4444}
.nm{font-size:2rem;font-weight:800;color:#f1f5f9;line-height:1}
.lb{font-size:.8rem;color:#64748b;text-transform:uppercase;letter-spacing:1px;font-weight:600}
.cd{background:#1e293b;border:1px solid rgba(200,162,85,.1);border-radius:16px;margin-bottom:28px;overflow:hidden}
.cd-h{padding:20px 24px;border-bottom:1px solid rgba(200,162,85,.1);display:flex;justify-content:space-between;align-items:center}
.cd-h h3{font-size:1rem;color:#f1f5f9;font-weight:700}
.cd-b{padding:20px 24px}
.btn{background:linear-gradient(135deg,#c8a255,#a07c3a);color:#fff;padding:10px 20px;border-radius:10px;text-decoration:none;font-size:.85rem;font-weight:600;display:inline-flex;align-items:center;gap:8px;transition:all .2s;border:none;cursor:pointer}
.btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(200,162,85,.3)}
.qg{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.qc{display:flex;align-items:center;gap:14px;padding:16px;border-radius:12px;text-decoration:none;color:inherit;transition:all .2s;border:1px solid transparent}
.qc:hover{background:rgba(200,162,85,.05);border-color:rgba(200,162,85,.15);transform:translateX(4px)}
.qi{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1rem}
.qc h4{font-size:.85rem;font-weight:700;color:#f1f5f9}
.qc p{font-size:.75rem;color:#64748b;margin-top:2px}
table{width:100%;border-collapse:separate;border-spacing:0}
thead th{padding:14px 16px;text-align:left;font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:1.5px;font-weight:700;background:rgba(15,23,42,.5);border-bottom:1px solid rgba(200,162,85,.1)}
tbody tr{transition:background .2s}
tbody tr:hover{background:rgba(200,162,85,.03)}
tbody td{padding:14px 16px;border-bottom:1px solid rgba(200,162,85,.05);font-size:.85rem;color:#cbd5e1}
@media(max-width:1024px){.sb{transform:translateX(-100%)}
.sb.open{transform:translateX(0)}
.mn{margin-left:0}.mt{display:flex}.sg{grid-template-columns:repeat(2,1fr)}.qg{grid-template-columns:repeat(2,1fr)}}
@media(max-width:600px){.sg{grid-template-columns:1fr}.qg{grid-template-columns:1fr}.ct{padding:16px}.tb{padding:12px 16px}}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
@keyframes slideIn{from{opacity:0;transform:translateX(-20px)}to{opacity:1;transform:translateX(0)}}
</style>
</head>
<body>
<aside class="sb" id="sb">
<div class="sb-h"><h2>Chollo &amp; Glam</h2><span>Panel Admin</span></div>
<nav class="sb-n">
<a href="panel.php" class="act"><i class="fas fa-th-large"></i> Dashboard</a>
<div class="st">Gestion</div>
<a href="panel.php"><i class="fas fa-box"></i> Productos</a>
<a href="gestionar_categorias.php"><i class="fas fa-tags"></i> Categorias</a>
<a href="coleccion.php"><i class="fas fa-layer-group"></i> Colecciones</a>
<div class="st">Marketing</div>
<a href="oferta_flash.php"><i class="fas fa-bolt"></i> Ofertas Flash</a>
<a href="instagram.php"><i class="fab fa-instagram"></i> Instagram</a>
<a href="testimonio.php"><i class="fas fa-star"></i> Testimonios</a>
</nav>
<div class="sb-f">
<a href="../index.php" target="_blank"><i class="fas fa-store"></i> Ver Tienda</a>
<a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesion</a>
</div>
</aside>
<div class="mn">
<div class="tb">
<div style="display:flex;align-items:center;gap:12px">
<button class="mt" onclick="document.getElementById('sb').classList.toggle('open')"><i class="fas fa-bars"></i></button>
<h1>Dashboard</h1>
</div>
<div class="tb-r"><div class="usr"><div class="av">A</div><span><?php echo $_SESSION["admin_name"] ?? "Admin"; ?></span></div></div>
</div>
<div class="ct"><?php if($sinStock > 0): ?><div class="al"><i class="fas fa-exclamation-triangle"></i><span><?=$sinStock?> producto(s) sin stock.</span></div><?php endif; ?>
<div class="sg">
<div class="sc"><div class="ic g"><i class="fas fa-box"></i></div><div class="nm"><?php echo $totalP; ?></div><div class="lb">Productos</div></div>
<div class="sc"><div class="ic b"><i class="fas fa-tags"></i></div><div class="nm"><?php echo $totalC; ?></div><div class="lb">Categorias</div></div>
<div class="sc"><div class="ic gr"><i class="fas fa-layer-group"></i></div><div class="nm"><?php echo $totalCo; ?></div><div class="lb">Colecciones</div></div>
<div class="sc"><div class="ic p"><i class="fas fa-star"></i></div><div class="nm"><?php echo $totalT; ?></div><div class="lb">Testimonios</div></div>
<div class="sc"><div class="ic r"><i class="fas fa-exclamation-circle"></i></div><div class="nm"><?php echo $sinStock; ?></div><div class="lb">Sin Stock</div></div>
<div class="sc"><div class="ic g"><i class="fas fa-fire"></i></div><div class="nm"><?php echo $dest; ?></div><div class="lb">Destacados</div></div>
</div>
<div class="cd">
<div class="cd-h"><h3><i class="fas fa-bolt" style="color:#c8a255;margin-right:8px"></i> Acciones Rapidas</h3></div>
<div class="cd-b">
<div class="qg">
<a href="panel.php?action=new" class="qc"><div class="qi g"><i class="fas fa-plus"></i></div><div><h4>Nuevo Producto</h4><p>Anadir al catalogo</p></div></a>
<a href="gestionar_categorias.php" class="qc"><div class="qi b"><i class="fas fa-folder-plus"></i></div><div><h4>Categorias</h4><p>Crear o editar</p></div></a>
<a href="coleccion.php" class="qc"><div class="qi gr"><i class="fas fa-images"></i></div><div><h4>Colecciones</h4><p>Administrar</p></div></a>
<a href="oferta_flash.php" class="qc"><div class="qi r"><i class="fas fa-fire"></i></div><div><h4>Ofertas Flash</h4><p>Crear ofertas</p></div></a>
<a href="testimonio.php" class="qc"><div class="qi p"><i class="fas fa-comment-dots"></i></div><div><h4>Testimonios</h4><p>Gestionar</p></div></a>
<a href="instagram.php" class="qc"><div class="qi g"><i class="fab fa-instagram"></i></div><div><h4>Instagram</h4><p>Feed social</p></div></a>
</div>
</div>
</div>
<div class="cd">
<div class="cd-h"><h3><i class="fas fa-box" style="color:#c8a255;margin-right:8px"></i> Ultimos Productos</h3>
<a href="panel.php?action=new" class="btn"><i class="fas fa-plus"></i> Nuevo</a></div>
<div class="cd-b" style="padding:0"><div style="overflow-x:auto">
<table>
<thead><tr><th>ID</th><th>Img</th><th>Nombre</th><th>Categoria</th><th>Precio</th><th>Stock</th><th>Dest.</th><th>Acciones</th></tr></thead>
<tbody>
<?php foreach($productos as $p): ?>
<tr>
<td><strong>#<?php echo $p["id"]; ?></strong></td>
<td><?php if($p["imagen"]): ?><img src="../<?php echo $p["imagen"]; ?>" style="width:44px;height:44px;border-radius:10px;object-fit:cover" alt=""><?php else: ?><div style="width:44px;height:44px;border-radius:10px;background:#f0f2f5;display:flex;align-items:center;justify-content:center;color:#999"><i class="fas fa-image"></i></div><?php endif; ?></td>
<td><strong><?php echo htmlspecialchars($p["nombre"]); ?></strong></td>
<td><span style="padding:4px 10px;border-radius:20px;font-size:.75rem;font-weight:600;background:rgba(59,130,246,.1);color:#3b82f6"><?php echo htmlspecialchars($p["cat_nombre"] ?? "-"); ?></span></td>
<td><strong><?php echo number_format($p["precio_actual"],2,",","."); ?> EUR</strong></td>
<td><?php if($p["stock"] == 0): ?><span style="padding:4px 10px;border-radius:20px;font-size:.75rem;font-weight:600;background:rgba(239,68,68,.1);color:#ef4444">Sin stock</span><?php elseif($p["stock"] < ($p["stock_minimo"] ?? 5)): ?><span style="padding:4px 10px;border-radius:20px;font-size:.75rem;font-weight:600;background:rgba(245,158,11,.1);color:#f59e0b">Bajo (<?php echo $p["stock"]; ?>)</span><?php else: ?><span style="padding:4px 10px;border-radius:20px;font-size:.75rem;font-weight:600;background:rgba(16,185,129,.1);color:#10b981"><?php echo $p["stock"]; ?></span><?php endif; ?></td>
<td><?php if($p["destacado"]): ?><i class="fas fa-fire" style="color:#c8a255"></i><?php else: ?><span style="color:#999">-</span><?php endif; ?></td>
<td><div style="display:flex;gap:6px">
<a href="editar_producto.php?id=<?php echo $p["id"]; ?>" style="width:32px;height:32px;border-radius:8px;background:rgba(59,130,246,.1);color:#3b82f6;display:flex;align-items:center;justify-content:center;text-decoration:none;font-size:.85rem" title="Editar"><i class="fas fa-pen"></i></a>
<form method="POST" action="eliminar.php" style="display:inline" onsubmit="return confirm('Eliminar?')"><input type="hidden" name="id" value="<?php echo $p["id"]; ?>"><button type="submit" style="width:32px;height:32px;border-radius:8px;background:rgba(239,68,68,.1);color:#ef4444;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.85rem" title="Eliminar"><i class="fas fa-trash"></i></button></form>
</div></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div></div>
</div>
</div>
</div>
</body>
</html>