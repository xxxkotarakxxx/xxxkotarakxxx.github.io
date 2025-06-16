<!DOCTYPE html>
<html>
<head>
  <title>admin menu</title>
  <link rel="stylesheet" href="estilos.css">
</head>



<body>
  <h2>Bienvenido Admin</h2>
  
  <ul>
    <li><a href="usuarios.php">Usuarios</a></li>
    <li><a href="productos.php">Productos</a></li>
    <li><a href="bitacora.php">Bitácora</a></li>
  </ul>
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

h2 {
  color: #ff4b5c;
  margin-bottom: 30px;
  font-size: 28px;
}

ul {
  list-style-type: none;
  padding: 0;
}

ul li {
  margin: 15px 0;
}

ul li a {
  background-color: #ff4b5c;
  color: white;
  padding: 12px 25px;
  border-radius: 12px;
  text-decoration: none;
  font-size: 18px;
  transition: background-color 0.3s ease;
  display: inline-block;
}

ul li a:hover {
  background-color: #ff6b7d;
}

</style>