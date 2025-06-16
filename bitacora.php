<?php
include("db.php");


// Consulta para obtener los datos de la bitácora
$sql = "SELECT id, usuario, fecha, estado FROM bitacora ORDER BY fecha DESC";
$resultado = $conn->query($sql);

// Verificar si hubo un error en la consulta
if (!$resultado) {
    die("Error al consultar la bitácora: " . $conn->error);
}
?>


?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bitácora</title>
    <title>Usuarios</title>
    
</head>



 
<body>
    <div class="container">
        <h1> Bitácora </h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                  
                </tr>
            </thead>
            <tbody>
                <?php while ($fila = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?= $fila['id'] ?></td>
                        <td><?= htmlspecialchars($fila['usuario']) ?></td>
                        <td><?= $fila['fecha'] ?></td>
                        <td><?= $fila['estado'] ?></td>
                   
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="index.php" class="btn-volver">regresar</a>
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
  background: white;
  padding: 30px;
  border-radius: 15px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  width: 90%;
  max-width: 800px;
}

h1 {
  text-align: center;
  color: #ff6b6b;
  margin-bottom: 20px;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}

th, td {
  padding: 12px 15px;
  text-align: center;
  border-bottom: 1px solid #ddd;
}

th {
  background-color: #ff6b6b;
  color: white;
}

tr:hover {
  background-color: #ffe0e0;
}

.btn-volver {
  display: inline-block;
  text-decoration: none;
  background-color: #ff4b5c;
  color: white;
  padding: 10px 20px;
  border-radius: 10px;
  transition: background-color 0.3s ease;
}

.btn-volver:hover {
  background-color: #ff6b6b;
}

</style>
