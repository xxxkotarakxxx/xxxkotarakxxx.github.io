



<!DOCTYPE html>
<html>
<head>
  <title>Login</title>
  

</head>
<link rel="stylesheet" href="segundo/estilo.css">


<body>
  <div class="login-container">
  <h2>Bienvenido a la tienda de muñecos funkos </h2>
  
  <form action="login.php" method="POST">
    Usuario: <input type="text" name="usuario"><br>
    Contraseña: <input type="password" name="contrasena"><br>

    <button type="submit">Inicio</button>
    <a href="registro.php"><button type="button">Registrate</button></a>
  </form>
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
form input[type="password"] {
  width: 90%;
  padding: 12px 15px;
  margin: 10px 0;
  border: 1px solid #ccc;
  border-radius: 10px;
  outline: none;
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

a button {
  background-color: #4b7cff;
}

a button:hover {
  background-color: #6b92ff;
}
</style>
