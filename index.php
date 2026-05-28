<?php
// incluir dependencias
require_once("connection/connect.php");
require_once("functions/product_functions.php");

// Establecer conexion con la base de datos
$db = new Database();
$pdo = $db->conectar();

$tipos = obtenerTodosLosTipos ($pdo);

if ($pdo === null) {
	die('Error de conexion a la base de datos');
}

$accion = $_GET['accion'] ?? 'menu';
$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // SCRIPT PARA CREAR
    if (isset($_POST['crear'])) {
        $nombre = trim($_POST['nombre'] ?? '');
        $precio = (isset($_POST['precio']) && $_POST['precio'] !== '') ? floatval($_POST['precio']) : null;
		$tipo_id = isset ($_POST['tipo_id']) && $_POST['tipo_id'] !== '' ? intval($_POST['tipo_id']) : null;

		// Validacion de imagen
		$imagen = null;
		$alert_img = '';

		//Definimos ruta de la carpeta
		$rutaDestino = __DIR__ . '/img/';

		// Verificamos si existe, y si no la creamos auto
		if (!is_dir($rutaDestino)) {
			mkdir($rutaDestino, 0777, true);
		}

		if (isset($_FILES['imagen']) && $_FILES ['imagen']['error'] === 0)
			{
				$ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
				$max = 3 * 1024 * 1024; //3MB
				if ($ext !== 'png'){
					$alert_img = "Solo se permiten archivos .png";
				} elseif ($_FILES['imagen']['size'] > $max) {
					$alert_img = "El archivo no debe superar los 3MB";
				} else {
					$nombre_img = uniqid('img_') . '.png';
					if (move_uploaded_file($_FILES['imagen']['tmp_name'], __DIR__ . '/img/' . $nombre_img)) {
						$imagen = $nombre_img;
					} else
					{
						$alert_img = "Error al guardar la imagen en la carpeta /img/";
					}
				}
			} else {
				$alert_img = "No se cargo ninguna imagen. El producto se registrara sin foto";
			}

        if ($nombre !== '' && $alert_img === '') {
            $id_nuevo = insertarProducto($pdo, $nombre, $precio, $tipo_id, $imagen);
            $mensaje = $id_nuevo ? "Producto creado con éxito. ID: $id_nuevo" : "Error al insertar";
        } else {
            $mensaje = "El nombre es obligatorio";
        }
    }

    // SCRIPT PARA ACTUALIZAR
    if (isset($_POST['actualizar'])) {
        $id = intval($_POST['id']);
        $nombre = trim($_POST['nombre'] );
        $precio = (isset($_POST['precio']) && $_POST['precio'] !== '') ? floatval($_POST['precio']) : null;
        $tipo_id = isset ($_POST['tipo_id']) && $_POST['tipo_id'] !== '' ? intval($_POST['tipo_id']) : null;

		// Validacion de imagen
		$imagen = null;
		$alert_img = '';

		if (isset($_FILES['imagen']) && $_FILES ['imagen']['error'] === 0)
			{
				$ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
				$max = 3 * 1024 * 1024; //3MB
				if ($ext !== 'png'){
					$alert_img = "Solo se permiten archivos .png";
				} elseif ($_FILES['imagen']['size'] > $max) {
					$alert_img = "El archivo no debe superar los 3MB";
				} else {
					$nombre_img = uniqid('img_') . '.png';
					if (move_uploaded_file($_FILES['imagen']['tmp_name'], __DIR__ . '/img/' . $nombre_img)) {
						$imagen = $nombre_img;
                        $old = obtenerProductoPorId($pdo, $id);
                        if (!empty($old['image']) && file_exists(__DIR__. '/img/'. $nombre_img)){
                            unlink(__DIR__ . '/img/'. $old['image']);
                        }
                        $imagen = $nombre_img;
                        } else
                        {
                            $alert_img = "Error al guardar la imagen en la carpeta /img/";
                        }
				}
			} 
            elseif (isset($_POST['borrar_imagen'])) {
                $imagen = '';
			}

        if ($id > 0 && $nombre !== '' && $alert_img === '') {
            $ok = actualizarProducto($pdo, $id, $nombre, $precio,$tipo_id, $imagen);
            $mensaje = $ok ? "Producto actualizado correctamente" : "No se encontraro producto";
        } else {
            $mensaje = "Datos inválidos para actualizar";
        }
    }

    // SCRIPT PARA ELIMINAR
    if (isset($_POST['eliminar'])) {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id > 0) {
            $ok = eliminarProducto($pdo, $id);
            $mensaje = $ok ? "Producto eliminado correctamente" : "No se encontró el ID del producto para eliminar";
        } else {
            $mensaje = "El ID proporcionado es inválido";
        }
    }
}

if ($accion === "listar") {
    $productos = obtenerTodosLosProductos($pdo);
}

$producto_editar = null;
if ($accion === 'editar_form') {
    $id_buscar = intval($_GET['id'] ?? 0);
    if ($id_buscar > 0) {
        $producto_editar = obtenerProductoPorId($pdo, $id_buscar);
    }
}
elseif (isset($_POST['crear_tipo'])) {
	$nombre_tipo = trim($_POST['nombre_tipo']);
	if ($nombre_tipo !== '') {
		$res = crearTipo($pdo, $nombre_tipo);
		$mensaje = $res ? " Tipo creado correctamente" : "Error al crear tipo";
	}
}
elseif (isset($_POST['actualizar_tipo'])) {
	$id_tipo = intval($_POST['id_tipo']);
	$nombre_tipo = trim($_POST['nombre_tipo']);
	if ($id_tipo > 0 && $nombre_tipo !== '') {
		$res = actualizarTipo($pdo,$id_tipo, $nombre_tipo);
		$mensaje = $res ? " Tipo actualizado" : "Error al actualizar";
	}
}
elseif (isset($_POST['eliminar_tipo'])) {
	$id_tipo = intval($_POST['id_tipo']);
	if ($id_tipo > 0) {
		$res = eliminarTipo($pdo,$id_tipo);
		$mensaje = $res ? " Tipo eliminado" : "Error al eliminar";
	}
}
?>


<!DOCTYPE html>
<html lang="es">

<head>
	<meta charset="UTF-8">
	<title>CRUD Simple</title>
	<!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap 5 JS (necesario para alertas y componentes interactivos) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-light">
	<div class="container py-4">
		<h1 class="mb-4 text-center"> CRUD de Productos </h1>
		<?php if ($mensaje): ?>
			<p><strong><?= $mensaje ?></strong></p>
			<p><a href="?accion=menu">- volver al menu</a></p>
		<?php else: ?>
			<?php if ($accion === 'menu'): ?>
				<p><strong>Seleccione una opción:</strong></p>

				<ul>
					<li class="list-group-item"><a href="?accion=listar" class="text-decoration-none text-primary">Listar todos los productos</a></li>
					<li class="list-group-item"><a href="?accion=crear_form" class="text-decoration-none text-primary">Crear nuevo producto</a></li>
					<li class="list-group-item"><a href="?accion=editar_form" class="text-decoration-none text-primary">Actualizar productos</a></li>
					<li class="list-group-item"><a href="?accion=eliminar_form" class="text-decoration-none text-primary">Eliminar productos</a></li>
					<li class="list-group-item"><a href="?accion=tipos" class="text-decoration-none text-warning">Gestionar tipos de productos</a></li>
				</ul>

			<?php elseif ($accion === 'listar'):  ?>
				<h2 class="mb-3"> Listado de Productos </h2>
				<?php if (count($productos ?? []) > 0): ?>
					<table class="table table-striped table-over table-bordered align-middle">
						<thead class="table-dark">
						<tr>
							<th>ID</th>
							<th>Imagen</th>
							<th>Nombre</th>
							<th>Tipo</th>
							<th>Precio</th>
						</tr>
						</thead>

						<?php foreach ($productos ?? [] as $p):  ?>
							<tr>
								<td><?= htmlspecialchars($p['product_id']) ?> </td>
								<td>
									<?php if ($p['image']): ?>
										<img src="img/<?= htmlspecialchars($p['image']) ?>"
										style="max-height: 50px; border-radius:4px;" alt="Producto">
										<?php else: ?>
											<span class="text-muted">Sin Foto</span>
										<?php endif; ?>
								</td>
								<td><?= htmlspecialchars($p['product_name']) ?> </td>
								<td><?= htmlspecialchars($p['type_name'] ?? 'Sin tipo') ?></td>
								<td>$<?= number_format($p['product_price'], 2) ?> </td>
							</tr>
						<?php endforeach; ?>
					</table>
				<?php else: ?>
					<p>No hay productos registrados. </p>
				<?php endif; ?>
				<p><a href="?accion=menu" class="btn btn-secondary mt-3">- volver al menu</a></p>

				<!-- ACA VIENE EL FORMULARIO DE INSERCCIÓN -->
			<?php elseif ($accion === 'crear_form'): ?>
				<div class="card shadow-sm">
					<div class="card-body">
						<h2 class="card-title mb-3">Nuevo Producto</h2>
						<form action="" method="POST" enctype="multipart/form-data">
							<div class="mb-3">
								<label for="" class="form-label">Nombre Producto: </label> 
								<input type="text" name="nombre" class="form-control" required>
							</div>
							<div class="mb-3">
								<label for="" class="form-label">Precio Producto: </label>
								<input type="number" class="form-control" step="0.01" name="precio">
							</div>
							<div class="mb-3">
								<label class="form-label">Tipo:</label>
								<select name="tipo_id" class="form-select">
									<option value=""> -- Sin Tipo -- </option>
									<?php foreach ($tipos as $t): ?>
										<option value="<?= $t['type_id'] ?>"><?= htmlspecialchars($t['type_name']) ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="mb-3">
								<label class="form-label">Imagen (.png, max 3MB): </label>
								<input type="file" name="imagen" class="form-control" accept=".png">
							</div>
							<button type="submit" name="crear" class="btn btn-primary">Guardar</button>
							<a href="?accion=menu" class="btn btn-outline-secondary">Cancelar</a>
					</form>
					</div>
				</div>

				<!-- ESTE ES EL SCRIPT PARA ACTUALIZAR UN PRODUCTO -->
			<?php elseif ($accion === 'editar_form' && !$producto_editar): ?>
				<h2>Actualizar Producto <i class="fa-solid fa-pencil"></i></h2>
				<p>Ingrese el ID del Producto a Editar</p>
				<form action="" method="GET">
					<input type="hidden" name="accion" value="editar_form">
                    <label for="">ID: <input type="number" name="id" required min="1"></label>
					<button type="submit">Buscar</button>
				</form>
				<p><a href="?accion=menu" class="btn btn-secondary mt-3">- Volver a menu</a></p>

				
			<?php elseif ($producto_editar): ?>
				<div class="card shadow-sm">
					<div class="card-body">
						<h2 class="card title mb-3">Editar Producto #<?= $producto_editar['product_id'] ?></h2>
						<form action="" method="POST" enctype="multipart/form-data">
						<div class="mb-3">
							<label class="form-label"> ID </label> 
                            <input type="number" name="id" value="<?= $producto_editar ['product_id'] ?>" readonly> 
							
						</div>
						<div class="mb-3">
							<label class="form-label" for="">Nombre: </label>
							<input type="text" class="form-control" name="nombre" value="<?= $producto_editar['product_name'] ?>" required><br><br>
						</div>
						<div class="mb-3">
							<label class="form-label">Precio: </label>
							<input type="number" class="form-control step="0.01" name="precio" value="<?= $producto_editar['product_price'] ?>"><br><br>
						</div>
						<div class="mb-3">
							<label class="form-label">Tipo:</label>
							<select name="tipo_id" class="form-select">
								<option value=""> -- Sin Tipo -- </option>
								<?php foreach ($tipos as $t): ?>
									<option value="<?= $t['type_id'] ?>" <?= $producto_editar['type_id'] == $t['type_id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['type_name']) ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="mb-3">
							<label class="form-label"> Imagen Actual : </label>
							<?php if ($producto_editar['image']) : ?>
								<img src="img/<?= htmlspecialchars($producto_editar['image']) ?>"
								style="max-height:100px; border-radius:4px;" class="d-block mb-2">
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="borrar_imagen" id="delImg">
								<label class="form-check-label" for="delImg">Eliminar imagen actual</label>
							</div>
						<?php else: ?>
							<span class="text-muted">Sin Imagen</span>
						<?php endIf ?>
						<label class="form-label">Imagen (.png, max 3MB): </label>
						<input type="file" name="imagen" class="form-control" accept=".png">
						</div>
						<button type="submit" name="actualizar" class="btn btn-outline-secondary">Actualizar</button>
						<a href="?accion=menu" class="btn btn-outline-secondary">Cancelar</a>
				</form>
					</div>
				</div>
				

				<!-- Scrip para eliminar registro formulario -->
				<?php elseif ($accion === 'eliminar_form'): ?>
                    <h2>Eliminar producto <i class="fa-solid fa-trash-can"></i></h2>
                    <p>Ingrese el ID del producto a eliminar</p>
                    <form action="" method="POST">
						<div>
							<label class="form-label" for=""> ID: </label>
							<input type="number" class="form-control" name="id" required min="1">
						</div>
						<br>
                        <button class="btn btn-outline-secondary" type="submit" name="eliminar" onclick="return confirm('¿Esta seguro de eliminar?')">Eliminar</button>
                    </form>
                    <p><a href="?accion=menu" class="btn btn-secondary mt-3">-Volver al menu</a></p>

					<!-- GESTION DE TIPOS -->
					 <?php elseif ($accion === 'tipos'): 
						$lista_tipos = obtenerTodosLosTipos($pdo);
					?>
						<h2> Tipos de Producto</h2>

						<!-- FORMULARIO RAPIDO PARA AGREGAR-->
						 <div class="card mb-3 bg-light">
							<div class="card-body">
								<form method="POST" class="d-flex gap-2 align-items-center">
									<input type="text" name="nombre_tipo" class="form-control" placeholder="Nuevo tipo (ej:Ropa)" required>
									<button type="submit" name="crear_tipo" class="btn btn-success"> Agregar</button>
								</form>
							</div>
						 </div>
						 

				<!--Tabla de Tipos-->
				<?php if (count($lista_tipos) > 0): ?>
					<table class="table table-hover">
						<thead>
							<tr>
								<th>ID</th>
								<th>Nombre del Tipo</th>
								<th>Acciones</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($lista_tipos as $tipo): ?>
								<tr>
									<td><?= $tipo['type_id'] ?></td>
									<td>
										<!--Formulario inline para editar-->
										<form method="POST" class="d-flex gap-2">
											<input type="hidden" name="id_tipo" value="<?= $tipo['type_id'] ?>">
											<input type="text" name="nombre_tipo" value="<?= htmlspecialchars($tipo['type_name']) ?>" class="form-control form-control-sm" required>
											<button type="submit" name="actualizar_tipo" class="btn btn-sm btn-primary">actualizar</button>
										</form>
									</td>
									<td>
										<form method="POST" onsubmit="return confirm('¿Eliminar este tipo? Los productos perderán su categoría.')">
											<input type="hidden" name="id_tipo" value="<?= $tipo['type_id'] ?>">
											<button type="submit" name="eliminar_tipo" class="btn btn-sm btn-danger">eliminar</button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else: ?>
					<p>No hay tipos registrados</p>
				<?php endif; ?>	

				<a href="?accion=menu" class="btn btn-secondary mt-3">Volver al menu</a>
			<?php endif; ?>
		<?php endif; ?>
</body>

</html>