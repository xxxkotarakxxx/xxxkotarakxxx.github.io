<?php
session_start();
include("db.php");

// Inicializar carrito
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Agregar producto
if (isset($_POST['agregar'])) {
    $id = intval($_POST['agregar']);
    
    // Obtener la información del producto
    $query = $conn->query("SELECT id, nombre, precio, imagen, stock FROM productos WHERE id = $id");
    $producto = $query->fetch_assoc();

    if ($producto && $producto['stock'] > 0) {
        // Verificar si el producto ya está en el carrito
        $existe = false;
        foreach ($_SESSION['carrito'] as &$item) {
            if ($item['id'] == $id) {
                // Si ya existe, incrementamos la cantidad
                $item['cantidad'] += 1;
                $existe = true;
                break;
            }
        }

        // Si no existe, lo agregamos con cantidad 1
        if (!$existe) {
            $_SESSION['carrito'][] = [
                'id' => $producto['id'],
                'nombre' => $producto['nombre'],
                'precio' => $producto['precio'],
                'imagen' => $producto['imagen'],
                'cantidad' => 1
            ];
        }
    } else {
        echo "<script>alert('El producto no está disponible en stock');</script>";
    }
}

// Eliminar producto
if (isset($_POST['eliminar'])) {
    $id = intval($_POST['eliminar']);
    foreach ($_SESSION['carrito'] as $key => &$item) {
        if ($item['id'] == $id) {
            // Si hay más de una unidad, reducimos la cantidad
            if ($item['cantidad'] > 1) {
                $item['cantidad'] -= 1;
            } else {
                // Si solo queda una unidad, lo eliminamos del carrito
                unset($_SESSION['carrito'][$key]);
            }
            break;
        }
    }
    $_SESSION['carrito'] = array_values($_SESSION['carrito']); // Reindexar el arreglo
}

// GENERAR PDF
if (isset($_POST['generar_ticket']) && !empty($_SESSION['carrito'])) {
    require('fpdf/fpdf.php');
    $pdf = new FPDF();
    $pdf->AddPage();

    // Fondo y color del título
    $pdf->SetFillColor(255, 204, 0);  // Color amarillo brillante
    $pdf->SetTextColor(100, 100, 100);  // Texto gris oscuro
    $pdf->SetFont('Arial', 'B', 18);  // Usamos Arial (puedes probar otras fuentes si las tienes)
    $pdf->Cell(0, 10, '🧾 Ticket de Compra - Funkos y Más', 0, 1, 'C', true);
    $pdf->Ln(5);

    // Fecha en color gris oscuro
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->SetTextColor(150, 150, 150);  // Gris suave
    $pdf->Cell(0, 10, 'Fecha: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
    $pdf->Ln(10);

    // Encabezados de la tabla con fondo llamativo
    $pdf->SetTextColor(255, 255, 255);  // Texto blanco
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(255, 0, 0);  // Rojo brillante para las cabeceras
    $pdf->Cell(20, 10, 'Imagen', 1, 0, 'C', true);
    $pdf->Cell(80, 10, 'Producto', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'Precio', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'Cantidad', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'Total', 1, 1, 'C', true);

    $pdf->SetTextColor(50, 50, 50);  // Texto en gris oscuro
    $pdf->SetFont('Arial', '', 12);

    // Agregar productos y sus detalles
    $total = 0;
    foreach ($_SESSION['carrito'] as $productoInfo) {
        // Imagen del producto
        $imagen = $productoInfo['imagen'];
        if (file_exists($imagen)) {
            // Redimensionamos la imagen para que se ajuste al formato del ticket
            $pdf->Image($imagen, $pdf->GetX(), $pdf->GetY(), 15);
        }
        $pdf->Cell(20, 15, '', 0, 0, 'C');  // Espacio para la imagen

        // Detalles del producto
        $pdf->Cell(80, 15, $productoInfo['nombre'], 1);
        $pdf->Cell(30, 15, "$" . number_format($productoInfo['precio'], 2), 1, 0, 'C');
        $pdf->Cell(30, 15, $productoInfo['cantidad'], 1, 0, 'C');
        $pdf->Cell(30, 15, "$" . number_format($productoInfo['precio'] * $productoInfo['cantidad'], 2), 1, 1, 'C');
        
        $total += $productoInfo['precio'] * $productoInfo['cantidad'];
    }

    $pdf->Ln(10);

    // Total con fondo brillante y texto blanco
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(255, 255, 255);  // Texto blanco
    $pdf->SetFillColor(0, 102, 204);  // Fondo azul brillante
    $pdf->Cell(160, 10, 'Total:', 1, 0, 'R', true);  // Total a la derecha
    $pdf->Cell(30, 10, "$" . number_format($total, 2), 1, 1, 'C', true);

    // Pie de página con un agradecimiento en color gris
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(50, 50, 50);  // Color gris oscuro para el pie
    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'Gracias por tu compra. ¡Nos vemos pronto! 🛍️', 0, 1, 'C');

    // Guardar y generar el archivo PDF
    $filename = 'ticket_' . date('Ymd_His') . '.pdf';
    $pdf->Output('D', $filename);
    exit;
}

?>


<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Catalogo de Funkos</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    body {
      margin: 0;
      padding: 0;
      background: url('imagenes/funko.jpg') no-repeat center center fixed;
      background-size: cover;
      font-family: 'Poppins', sans-serif;
      color: #333;
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      text-align: center;
      height: 100vh;
      padding-top: 50px;
    }

    h2, h3 {
      color: #FF6347;
      margin-top: 20px;
      font-size: 2rem;
    }

    .container {
      width: 90%;
      max-width: 1000px;
      background-color: rgba(255, 255, 255, 0.8);
      padding: 20px;
      border-radius: 20px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    form {
      background-color: white;
      padding: 20px;
      border-radius: 15px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      margin-bottom: 30px;
      width: 100%;
      max-width: 500px;
      margin: 0 auto;
    }

    table {
      width: 100%;
      max-width: 1000px;
      border-collapse: collapse;
      background-color: white;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      margin-bottom: 40px;
    }

    table th, table td {
      padding: 15px;
      text-align: center;
      border-bottom: 1px solid #ccc;
    }

    table th {
      background-color: #FF6347;
      color: white;
      font-weight: bold;
      font-size: 1.1rem;
    }

    table tr:nth-child(even) {
      background-color: #f2f2f2;
    }

    table td img {
      border-radius: 10px;
    }

    button {
      background-color: #FF6347;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-size: 1rem;
      transition: background-color 0.3s ease;
      width: 100%;
    }

    button:hover {
      background-color: #FF4500;
    }

    .carrito-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
      border-bottom: 1px solid #ddd;
      padding-bottom: 10px;
    }

    .carrito-item img {
      width: 50px;
      height: 50px;
      margin-right: 10px;
    }

    .total {
      font-size: 1.5rem;
      font-weight: bold;
      color: #FF6347;
      margin-top: 20px;
    }

  </style>
</head>
<body>
  <div class="container">
    <h2>Catalogo de Funkos - ¡Colección Única!</h2>

    <!-- Tabla productos -->
    <form method="POST">
      <table>
        <tr><th>Imagen</th><th>Nombre</th><th>Precio</th><th>Stock</th><th>Agregar</th></tr>
        <?php
        $resultado = $conn->query("SELECT * FROM productos");
        while ($fila = $resultado->fetch_assoc()) {
            $imagen = (!empty($fila['imagen']) && file_exists($fila['imagen'])) ? $fila['imagen'] : 'no_imagen.png';
            echo "<tr>
                    <td><img src='$imagen' width='80' height='80'></td>
                    <td>{$fila['nombre']}</td>
                    <td>{$fila['precio']}</td>
                    <td>{$fila['stock']}</td>
                    <td><button type='submit' name='agregar' value='{$fila['id']}'>Agregar</button></td>
                  </tr>";
        }
        ?>
      </table>
    </form>

    <!-- Carrito -->
    <?php if (!empty($_SESSION['carrito'])): ?>
      <h3>Carrito:</h3>
      <div class="carrito">
        <?php
        $total = 0;
        foreach ($_SESSION['carrito'] as $productoInfo) {
            echo "<div class='carrito-item'>
                    <img src='{$productoInfo['imagen']}' alt='{$productoInfo['nombre']}'>
                    <div>
                      <p>{$productoInfo['nombre']} - $" . number_format($productoInfo['precio'], 2) . " x {$productoInfo['cantidad']}</p>
                    </div>
                    <form method='POST' style='display:inline'>
                        <button type='submit' name='eliminar' value='{$productoInfo['id']}'>Eliminar</button>
                    </form>
                    <form method='POST' style='display:inline'>
                        <button type='submit' name='agregar' value='{$productoInfo['id']}'>Agregar otra unidad</button>
                    </form>
                  </div>";
            $total += $productoInfo['precio'] * $productoInfo['cantidad'];
        }
        ?>
      </div>

      <p class="total">Total: $<?php echo number_format($total, 2); ?></p>
      <form method="POST">
        <button type="submit" name="generar_ticket">Generar Ticket PDF</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
