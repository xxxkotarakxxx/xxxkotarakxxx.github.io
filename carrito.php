<?php
// Iniciar buffer de salida inmediatamente
ob_start();
session_start();

// Configuración de errores (solo para desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include("db.php");

// Incluir PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Inicializar carrito
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Función para limpiar buffers de salida
function cleanOutputBuffers() {
    while (ob_get_level()) {
        ob_end_clean();
    }
}

// Agregar producto al carrito
if (isset($_POST['agregar'])) {
    $id = intval($_POST['agregar']);
    $stmt = $conn->prepare("SELECT id, nombre, precio, imagen, stock FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $producto = $stmt->get_result()->fetch_assoc();

    if ($producto && $producto['stock'] > 0) {
        $existe = false;
        foreach ($_SESSION['carrito'] as &$item) {
            if ($item['id'] == $id) {
                $item['cantidad'] += 1;
                $existe = true;
                break;
            }
        }
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
        $_SESSION['mensaje'] = 'El Funko Pop no está disponible en stock';
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Eliminar producto del carrito
if (isset($_POST['eliminar'])) {
    $id = intval($_POST['eliminar']);
    foreach ($_SESSION['carrito'] as $key => &$item) {
        if ($item['id'] == $id) {
            if ($item['cantidad'] > 1) {
                $item['cantidad'] -= 1;
            } else {
                unset($_SESSION['carrito'][$key]);
            }
            break;
        }
    }
    $_SESSION['carrito'] = array_values($_SESSION['carrito']);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Generar ticket y actualizar stock
if (isset($_POST['generar_ticket'])) {
    // Limpiar cualquier salida potencial
    cleanOutputBuffers();
    
    $fecha = date("Y-m-d");
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        foreach ($_SESSION['carrito'] as $item) {
            $id_producto = $item['id'];
            $cantidad = $item['cantidad'];
            
            // Verificar stock disponible
            $stmt = $conn->prepare("SELECT stock FROM productos WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $id_producto);
            $stmt->execute();
            $stock = $stmt->get_result()->fetch_assoc()['stock'];
            
            if ($stock < $cantidad) {
                throw new Exception("No hay suficiente stock para {$item['nombre']}");
            }
            
            // Registrar venta
            $stmt = $conn->prepare("INSERT INTO ventas (id_producto, cantidad, fecha) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $id_producto, $cantidad, $fecha);
            $stmt->execute();
            
            // Actualizar stock
            $conn->query("UPDATE productos SET stock = stock - $cantidad WHERE id = $id_producto");
        }
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        cleanOutputBuffers();
        die("<script>alert('Error al procesar la compra: ".addslashes($e->getMessage())."'); window.location.href='index.php';</script>");
    }

    // Generar PDF
    require('fpdf/fpdf.php');
    $pdf = new FPDF();
    $pdf->AddPage();

    // Configuración del PDF
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->Cell(0, 10, utf8_decode("Funko Manía - Ticket de Compra 🎮"), 0, 1, 'C');
    $pdf->SetDrawColor(255, 105, 180); // Rosa neón
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(4);
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, 'Fecha: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
    $pdf->Ln(5);

    // Tabla de productos
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(230, 230, 250); // Lavanda
    $pdf->Cell(35, 10, 'Imagen', 1, 0, 'C', true);
    $pdf->Cell(65, 10, 'Funko Pop', 1, 0, 'C', true);
    $pdf->Cell(25, 10, 'Precio', 1, 0, 'C', true);
    $pdf->Cell(25, 10, 'Cantidad', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'Total', 1, 1, 'C', true);

    $total = 0;
    $pdf->SetFont('Arial', '', 11);

    foreach ($_SESSION['carrito'] as $productoInfo) {
        $rutaImagen = file_exists($productoInfo['imagen']) ? $productoInfo['imagen'] : 'no_imagen.png';
        $y = $pdf->GetY();

        $pdf->Cell(35, 40, $pdf->Image($rutaImagen, $pdf->GetX() + 4, $y + 3, 28), 1);
        $pdf->MultiCell(65, 10, utf8_decode($productoInfo['nombre']), 1);
        $pdf->SetXY(100, $y);
        $pdf->Cell(25, 40, "$" . number_format($productoInfo['precio'], 2), 1, 0, 'C');
        $pdf->Cell(25, 40, $productoInfo['cantidad'], 1, 0, 'C');
        $pdf->Cell(30, 40, "$" . number_format($productoInfo['precio'] * $productoInfo['cantidad'], 2), 1, 1, 'C');

        $total += $productoInfo['precio'] * $productoInfo['cantidad'];
    }

    // Total final
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Ln(5);
    $pdf->Cell(150, 10, 'Total a Pagar:', 1, 0, 'R');
    $pdf->Cell(30, 10, "$" . number_format($total, 2), 1, 1, 'C');

    // Mensaje final
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(10);
    $pdf->MultiCell(0, 10, utf8_decode("¡Gracias por tu compra en Funko Manía!\nColecciona todos tus personajes favoritos.\n¡Hasta pronto! 🎮"), 0, 'C');

    // Guardar PDF en servidor temporalmente
    if (!file_exists('tmp')) {
        mkdir('tmp', 0777, true);
    }
    $filename = 'ticket_' . date('Ymd_His') . '.pdf';
    $pdf_path = __DIR__ . '/tmp/' . $filename;
    $pdf->Output($pdf_path, 'F');

    // Enviar por correo si se solicitó
    if (isset($_POST['enviar_correo']) && !empty($_POST['correo_cliente'])) {
        $correo_cliente = filter_var($_POST['correo_cliente'], FILTER_VALIDATE_EMAIL);
        
        if ($correo_cliente) {
            $mail = new PHPMailer(true);
            
            try {
                // Configuración del servidor SMTP
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'urielangosta1@gmail.com';
                $mail->Password = 'dckq cyva qenb qarz';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                
                // Remitente y destinatario
                $mail->setFrom('ventas@funkomania.com', 'Funko Manía');
                $mail->addAddress($correo_cliente);
                
                // Contenido del correo
                $mail->isHTML(true);
                $mail->Subject = 'Tu ticket de compra en Funko Manía';
                $mail->Body    = '
                    <h1 style="color: #ff1493;">¡Gracias por tu compra en Funko Manía!</h1>
                    <p>Adjuntamos tu ticket de compra. ¡Esperamos que disfrutes tus nuevos Funkos!</p>
                    <p><em>"Colecciona tus personajes favoritos"</em></p>
                    <p>¡Hasta pronto! 🎮</p>
                ';
                $mail->AltBody = 'Gracias por tu compra en Funko Manía. Adjunto encontrarás tu ticket de compra.';
                
                // Adjuntar PDF
                $mail->addAttachment($pdf_path, $filename);
                
                $mail->send();
                $_SESSION['mensaje'] = 'Ticket enviado al correo electrónico proporcionado';
            } catch (Exception $e) {
                $_SESSION['mensaje'] = 'Error al enviar el correo: '.$e->getMessage();
            }
        } else {
            $_SESSION['mensaje'] = 'Por favor ingresa un correo electrónico válido';
        }
        
        // Si solo queríamos enviar por correo, redirigir
        if (isset($_POST['solo_correo'])) {
            unlink($pdf_path);
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        }
    }

    // Limpiar carrito
    $_SESSION['carrito'] = [];

    // Descargar PDF
    $pdf->Output('D', $filename);
    
    // Eliminar archivo temporal
    unlink($pdf_path);
    exit;
}

// Limpiar buffer antes de enviar HTML
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Funko Manía - Venta de Funko Pop</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700;900&family=Bangers&display=swap" rel="stylesheet">
  <style>
    :root {
      --color-primario: #ff1493; /* Rosa neón */
      --color-secundario: #00bfff; /* Azul brillante */
      --color-fondo: #f8f8ff; /* Blanco fantasma */
      --color-texto: #333333;
      --color-borde: #d3d3d3;
      --color-boton: #ff1493;
      --color-boton-hover: #ff69b4;
      --color-carrito: #00bfff;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background-color: var(--color-fondo);
      color: var(--color-texto);
      line-height: 1.6;
      padding: 20px;
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
      background-color: white;
      border-radius: 15px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }
    
    h1, h2, h3 {
      font-family: 'Bangers', cursive;
      color: var(--color-primario);
      letter-spacing: 1px;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    h1 {
      font-size: 2.5rem;
      text-align: center;
      margin-bottom: 30px;
      color: var(--color-primario);
    }
    
    h2 {
      font-size: 2rem;
      margin: 20px 0;
      border-bottom: 3px solid var(--color-primario);
      padding-bottom: 10px;
      display: inline-block;
    }
    
    h3 {
      font-size: 1.5rem;
      margin: 15px 0;
      color: var(--color-carrito);
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
      background-color: white;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
    }
    
    th, td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid var(--color-borde);
    }
    
    th {
      background-color: var(--color-primario);
      color: white;
      font-weight: 700;
      text-transform: uppercase;
      font-size: 0.9rem;
    }
    
    tr:hover {
      background-color: rgba(255, 20, 147, 0.05);
    }
    
    img {
      border-radius: 5px;
      object-fit: cover;
    }
    
    button, input[type="submit"] {
      background-color: var(--color-boton);
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 50px;
      cursor: pointer;
      font-weight: bold;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
    }
    
    button:hover, input[type="submit"]:hover {
      background-color: var(--color-boton-hover);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(255, 20, 147, 0.3);
    }
    
    input[type="email"] {
      padding: 10px 15px;
      border: 2px solid var(--color-borde);
      border-radius: 50px;
      width: 100%;
      max-width: 400px;
      font-family: 'Poppins', sans-serif;
    }
    
    ul {
      list-style: none;
      margin: 20px 0;
    }
    
    li {
      display: flex;
      align-items: center;
      padding: 15px;
      background-color: white;
      margin-bottom: 10px;
      border-radius: 10px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    
    li img {
      margin-right: 15px;
    }
    
    .total {
      font-size: 1.3rem;
      font-weight: bold;
      color: var(--color-primario);
    }
    
    .mensaje {
      background-color: var(--color-primario);
      color: white;
      padding: 15px;
      border-radius: 5px;
      margin-bottom: 20px;
      text-align: center;
    }
    
    .botones-compra {
      display: flex;
      gap: 15px;
      margin-top: 20px;
    }
    
    .botones-compra button {
      flex: 1;
    }
    
    @media (max-width: 768px) {
      .botones-compra {
        flex-direction: column;
      }
      
      li {
        flex-direction: column;
        align-items: flex-start;
      }
      
      li img {
        margin-bottom: 10px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>🎮 Funko Manía 🎮</h1>
    <p style="text-align: center; margin-bottom: 30px; font-size: 1.2rem;">¡Colecciona tus personajes favoritos!</p>
    
    <?php if (isset($_SESSION['mensaje'])): ?>
      <div class="mensaje">
        <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?>
      </div>
    <?php endif; ?>
    
    <h2>Catálogo de Funko Pop</h2>
    <form method="POST">
      <table>
        <tr>
          <th>Imagen</th>
          <th>Nombre</th>
          <th>Precio</th>
          <th>Stock</th>
          <th>Agregar</th>
        </tr>
        <?php
        $resultado = $conn->query("SELECT * FROM productos");
        while ($fila = $resultado->fetch_assoc()) {
            $imagen = (!empty($fila['imagen']) && file_exists($fila['imagen'])) ? $fila['imagen'] : 'no_imagen.png';
            echo "<tr>
                    <td><img src='$imagen' width='80' height='80' style='object-fit: contain;'></td>
                    <td>{$fila['nombre']}</td>
                    <td>\${$fila['precio']}</td>
                    <td>{$fila['stock']}</td>
                    <td><button type='submit' name='agregar' value='{$fila['id']}'>+ Carrito</button></td>
                  </tr>";
        }
        ?>
      </table>
    </form>

    <?php if (!empty($_SESSION['carrito'])): ?>
      <h2 style="color: var(--color-carrito);">Tu Carrito de Compras</h2>
      <ul>
        <?php
        $total = 0;
        foreach ($_SESSION['carrito'] as $productoInfo) {
            echo "<li>
                    <img src='{$productoInfo['imagen']}' width='60' height='60' style='object-fit: contain;'>
                    <span style='flex-grow: 1;'><strong>{$productoInfo['nombre']}</strong><br>
                    \$" . number_format($productoInfo['precio'], 2) . " x {$productoInfo['cantidad']}</span>
                    <form method='POST' style='display:inline; margin-left: 10px;'>
                        <button type='submit' name='eliminar' value='{$productoInfo['id']}' style='background-color: #ff4757;'>-</button>
                    </form>
                    <form method='POST' style='display:inline; margin-left: 5px;'>
                        <button type='submit' name='agregar' value='{$productoInfo['id']}' style='background-color: #2ed573;'>+</button>
                    </form>
                  </li>";
            $total += $productoInfo['precio'] * $productoInfo['cantidad'];
        }
        ?>
      </ul>
      <p style="font-size: 1.5rem; text-align: right; margin: 20px 0;">
        <strong>Total: <span style="color: var(--color-primario);">$<?php echo number_format($total, 2); ?></span></strong>
      </p>
      
      <form method="POST">
        <div style="margin: 20px 0;">
          <label for="correo_cliente" style="display: block; margin-bottom: 8px; font-size: 1.1rem;">
            ¿Quieres recibir el ticket en tu correo? 📧
          </label>
          <input type="email" id="correo_cliente" name="correo_cliente" 
                 placeholder="tucorreo@ejemplo.com" style="padding: 12px 20px; font-size: 1rem;">
        </div>
        
        <div class="botones-compra">
          <button type="submit" name="generar_ticket" style="background-color: var(--color-primario);">
            📥 Descargar Ticket
          </button>
          
          <button type="submit" name="generar_ticket" style="background-color: var(--color-carrito);">
            <input type="hidden" name="enviar_correo" value="1">
            ✉️ Enviar por Correo
          </button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>