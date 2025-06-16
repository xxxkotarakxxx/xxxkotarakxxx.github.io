<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gráfica de Ventas</title>
    <link href="https://fonts.googleapis.com/css2?family=Cardo&family=IM+Fell+English+SC&display=swap" rel="stylesheet">
    <style>


    </style>
</head>
<body>
    <div class="container">
        <h2>Selecciona un Rango de Fechas</h2>


<!-- Formulario -->
<form method="GET" action="graficas.php">
    <label>Fecha ini. <input type="date" name="fecha_ini" required value="<?php echo htmlspecialchars($fecha_ini); ?>"></label>
    <label>Fecha Final <input type="date" name="fecha_fin" required value="<?php echo htmlspecialchars($fecha_fin); ?>"></label>
    <button type="submit">Enviar</button>
</form>

<!-- Caja de la gráfica -->
<div class="grafica-box">
    <canvas id="graficaVentas" width="800" height="400"></canvas>
</div>