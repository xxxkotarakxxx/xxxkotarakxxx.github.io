<?php
include("db.php");

// Verificar conexión
if (!$conn) {
    die("Error de conexión a la base de datos");
}

// Eliminar usuario si se hace clic en "Eliminar"
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: usuarios.php");
    exit;
}

// Obtener usuarios de la base de datos
$resultado = $conn->query("SELECT id, usuario, rol FROM usuarios");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios</title>
    
</head>
<body>
    <div class="container">
        <h1>Usuarios Registrados</h1>

        <table border="1">
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
            <?php while ($fila = $resultado->fetch_assoc()): ?>
                <tr>
                    <td><?= $fila['id'] ?></td>
                    <td><?= htmlspecialchars($fila['usuario']) ?></td>
                    <td><?= htmlspecialchars($fila['rol']) ?></td>
                    <td>
                        <a href="editar_usuario.php?id=<?= $fila['id'] ?>" class="btn-editar">Editar</a>
                        <a href="usuarios.php?eliminar=<?= $fila['id'] ?>" class="btn-eliminar" onclick="return confirm('¿Seguro que quieres eliminar este usuario?');"> Eliminar</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>

        <a href="admin.php" class="btn-volver">Volver </a>
    </div>
</body>
</html>
<style>
   body {
  margin: 0;
  padding: 0;
  background: url('imagenes/funko.jpg') no-repeat center center fixed;
  background-size: cover;
    margin: 0;
    padding: 0;
    color: red;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    text-align: center;

  font-family: 'Poppins', sans-serif;
 
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  min-height: 100vh;
  padding-top: 50px;
}

.container {
  background-color: white;
  padding: 40px;
  border-radius: 15px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
  text-align: center;
}

h1 {
  margin-bottom: 30px;
  color: #4b79a1;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}

table th, table td {
  padding: 12px;
  border: 1px solid #ccc;
  text-align: center;
}

table th {
  background-color: #4b79a1;
  color: white;
}

table tr:nth-child(even) {
  background-color: #f2f2f2;
}

.btn-editar, .btn-eliminar, .btn-volver {
  display: inline-block;
  padding: 10px 20px;
  margin: 5px;
  border-radius: 10px;
  text-decoration: none;
  color: white;
  transition: background-color 0.3s ease;
}

.btn-editar {
  background-color: #4caf50;
}

.btn-editar:hover {
  background-color: #45a049;
}

.btn-eliminar {
  background-color: #f44336;
}

.btn-eliminar:hover {
  background-color: #e53935;
}

.btn-volver {
  background-color: #4b79a1;
}

.btn-volver:hover {
  background-color: #5c90c0;
}

</style>