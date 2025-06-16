<?php
include("db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $clave = $_POST['contrasena'] ?? '';
    $rol = $_POST['rol'] ?? 'consultor'; // Captura el rol del formulario

    if (empty($usuario) || empty($clave)) {
        die("❌ Usuario y contraseña son obligatorios.");
    }

    // Verifica si el usuario existe
    $sql_check = "SELECT id FROM usuarios WHERE usuario = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $usuario);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        die("❌ El usuario ya existe.");
    }

    // Hash de la contraseña
    $clave_hash = password_hash($clave, PASSWORD_DEFAULT);

    // Registra al usuario
    $sql_insert = "INSERT INTO usuarios (usuario, contrasena, rol) VALUES (?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("sss", $usuario, $clave_hash, $rol);

    if ($stmt_insert->execute()) {
        header("Location: index.php?registro=exitoso");
        exit();
    } else {
        die("❌ Error al registrar: " . $conn->error);
    }

    $stmt_check->close();
    $stmt_insert->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registro</title>
    
</head>
<body>
    <div class="login-container">
        <h2>Registro de Usuario</h2>
        <form method="POST" action="registro.php">
            <input type="text" name="usuario" placeholder="Usuario" required>
            <input type="password" name="contrasena" placeholder="Contraseña" required>
            <select name="rol" required>
                <option value="admin">Administrador</option>
                <option value="consultor" selected>Consultor</option>
            </select>
            <button type="submit">Registrar</button>
        </form>
      
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

.login-container {
  background: white;
  padding: 30px 40px;
  border-radius: 20px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  text-align: center;
  max-width: 400px;
  width: 100%;
}

h2 {
  color: #ff4b5c;
  margin-bottom: 20px;
}

form input[type="text"],
form input[type="password"],
form select {
  width: 90%;
  padding: 12px 15px;
  margin: 10px 0;
  border: 1px solid #ccc;
  border-radius: 10px;
  outline: none;
}

form select {
  background-color: #f9f9f9;
}

button {
  background-color: #ff4b5c;
  color: white;
  padding: 12px 20px;
  margin: 10px 5px;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-size: 16px;
  transition: background-color 0.3s ease;
}

button:hover {
  background-color: #ff6b7d;
}

a {
  color: #4b7cff;
  text-decoration: none;
}

a:hover {
  text-decoration: underline;
}

p {
  margin-top: 15px;
}

</style>