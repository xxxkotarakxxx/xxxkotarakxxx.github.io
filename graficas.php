<?php
include("db.php");

// Validación de fechas y protección contra SQL injection
$fecha_ini = isset($_GET['fecha_ini']) ? mysqli_real_escape_string($conn, $_GET['fecha_ini']) : '';
$fecha_fin = isset($_GET['fecha_fin']) ? mysqli_real_escape_string($conn, $_GET['fecha_fin']) : '';

$datos = [];
$error = '';

if ($fecha_ini && $fecha_fin) {
    // Validar que las fechas sean correctas y estén en el formato YYYY-MM-DD
    $date_ini_obj = DateTime::createFromFormat('Y-m-d', $fecha_ini);
    $date_fin_obj = DateTime::createFromFormat('Y-m-d', $fecha_fin);

    if (!$date_ini_obj || !$date_fin_obj || $date_ini_obj->format('Y-m-d') !== $fecha_ini || $date_fin_obj->format('Y-m-d') !== $fecha_fin) {
        $error = "Por favor, introduce fechas válidas en formato YYYY-MM-DD.";
    } elseif ($date_ini_obj > $date_fin_obj) {
        $error = "La fecha inicial no puede ser mayor que la fecha final";
    } else {
        // Consulta preparada para mayor seguridad
        $query = "SELECT p.nombre, SUM(v.cantidad) AS total_vendidos, p.stock
                  FROM ventas v
                  INNER JOIN productos p ON v.id_producto = p.id
                  WHERE v.fecha BETWEEN ? AND ?
                  GROUP BY p.id
                  ORDER BY total_vendidos DESC";
        
        $stmt = mysqli_prepare($conn, $query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $fecha_ini, $fecha_fin);
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);

            while ($row = mysqli_fetch_assoc($resultado)) {
                $datos[] = $row;
            }
            
            mysqli_stmt_close($stmt);
        } else {
            $error = "Error al preparar la consulta: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Gráfica de Ventas FunkoPop Mania!</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;900&family=Cardo&family=IM+Fell+English+SC&display=swap" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        /* Reset y fondo */
        body {
            font-family: 'Cardo', serif;
            background: linear-gradient(135deg, #6a0dad, #ffcc00);
            margin: 0;
            padding: 0;
            color: #1a1a1a;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: url('imagenes/funko-background.jpg') no-repeat center center fixed;
            background-size: cover;
            opacity: 0.15;
            z-index: -1;
        }

        .container {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 40px 30px;
            max-width: 900px;
            margin: 60px auto;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(138, 43, 226, 0.7);
            border: 3px solid #8e44ad;
            text-align: center;
        }

        /* Logo y slogan */
        .logo {
            margin-bottom: 30px;
            color: #ffcc00;
            text-shadow: 2px 2px 6px #4b0082;
        }

        .logo h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 3.5rem;
            font-weight: 900;
            letter-spacing: 5px;
            margin: 0;
        }

        .slogan {
            font-size: 1.4rem;
            font-weight: 600;
            font-style: italic;
            margin-top: 6px;
            color: #ffd54f;
        }

        /* Título de la gráfica */
        h2 {
            font-family: 'IM Fell English SC', serif;
            font-size: 2.8rem;
            color: #6a0dad;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: 600;
            color: #4b0082;
            text-align: left;
        }

        input[type="date"] {
            padding: 10px 12px;
            border: 2px solid #8e44ad;
            border-radius: 8px;
            background-color: #f9f9f9;
            color: #1a1a1a;
            font-family: 'Cardo', serif;
            font-size: 16px;
            width: 100%;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }

        input[type="date"]:focus {
            border-color: #ffcc00;
            outline: none;
        }

        /* Botón estilo Funko Pop */
        button {
            background: linear-gradient(45deg, #ffcc00, #8e44ad);
            box-shadow: 0 6px 15px rgba(255, 204, 0, 0.6);
            color: #1a1a1a;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            font-size: 18px;
            padding: 14px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            transition: all 0.25s ease-in-out;
        }

        button:hover {
            background: linear-gradient(45deg, #8e44ad, #ffcc00);
            box-shadow: 0 8px 25px rgba(142, 68, 173, 0.8);
            color: white;
            transform: scale(1.07);
        }

        .chart-container {
            position: relative;
            width: 100%;
            height: 65vh;
            min-height: 450px;
            margin: 40px auto 30px;
            background-color: #f7f0ff;
            border-radius: 15px;
            border: 3px solid #8e44ad;
            box-shadow: 0 8px 20px rgba(138, 43, 226, 0.5);
            padding: 20px;
        }

        #graficaVentas {
            width: 100% !important;
            height: 100% !important;
            border-radius: 10px;
        }

        .error {
            color: #d32f2f;
            font-weight: 700;
            font-size: 1.15rem;
            margin: 18px 0;
            text-align: center;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
                margin: 20px auto;
            }

            .logo h1 {
                font-size: 2.5rem;
            }

            h2 {
                font-size: 2rem;
            }

            .chart-container {
                height: 50vh;
                min-height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>FunkoPop Mania! 🧸</h1>
            <p class="slogan">¡Atrapa tu Funko favorito antes que se agote!</p>
        </div>

        <h2>Gráfica de Productos Vendidos</h2>

        <form method="GET" action="graficas.php">
            <label for="fecha_ini">Fecha Inicial</label>
            <input type="date" id="fecha_ini" name="fecha_ini" required value="<?php echo htmlspecialchars($fecha_ini); ?>" />

            <label for="fecha_fin">Fecha Final</label>
            <input type="date" id="fecha_fin" name="fecha_fin" required value="<?php echo htmlspecialchars($fecha_fin); ?>" />

            <button type="submit">Generar Gráfica</button>
        </form>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($datos)): ?>
            <div class="chart-container">
                <canvas id="graficaVentas"></canvas>
            </div>
            <button onclick="generarPDF()">Descargar Gráfica en PDF</button>
        <?php elseif ($fecha_ini && $fecha_fin && !$error): ?>
            <div class="error">No se encontraron datos para el rango de fechas seleccionado.</div>
        <?php endif; ?>
    </div>

    <script>
        // Configuración de la gráfica
        const datos = <?php echo json_encode($datos); ?>;
        const ctx = document.getElementById('graficaVentas')?.getContext('2d');
        let myChart;

        if (ctx && datos.length > 0) {
            myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: datos.map(d => d.nombre),
                    datasets: [
                        {
                            label: 'Unidades Vendidas',
                            data: datos.map(d => d.total_vendidos),
                            backgroundColor: 'rgba(142, 68, 173, 0.8)', // púrpura intenso
                            borderColor: 'rgba(108, 35, 139, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Stock Disponible',
                            data: datos.map(d => d.stock),
                            backgroundColor: 'rgba(255, 204, 0, 0.8)', // amarillo brillante
                            borderColor: 'rgba(204, 163, 0, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Cantidad de Productos',
                                color: '#6a0dad'
                            },
                            grid: {
                                color: 'rgba(138, 43, 226, 0.1)'
                            },
                            ticks: {
                                color: '#4b0082'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Productos',
                                color: '#6a0dad'
                            },
                            grid: {
                                color: 'rgba(138, 43, 226, 0.1)'
                            },
                            ticks: {
                                color: '#4b0082'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                font: {
                                    family: "'Poppins', sans-serif",
                                    size: 14
                                },
                                color: '#6a0dad'
                            }
                        },
                        tooltip: {
                            bodyFont: {
                                family: "'Cardo', serif"
                            },
                            titleFont: {
                                family: "'IM Fell English SC', serif"
                            },
                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                            titleColor: '#6a0dad',
                            bodyColor: '#1a1a1a',
                            borderColor: '#8e44ad',
                            borderWidth: 1
                        }
                    }
                }
            });
        }

        function generarPDF() {
            const canvas = document.getElementById('graficaVentas');
            if (!canvas) {
                console.error("Canvas 'graficaVentas' no encontrado.");
                return;
            }

            // Mostrar mensaje de carga
            const btnPDF = document.querySelector('button[onclick="generarPDF()"]');
            btnPDF.disabled = true;
            btnPDF.textContent = 'Generando PDF...';

            canvas.toBlob(blob => {
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('landscape');
                const imgData = URL.createObjectURL(blob);

                // Ajustamos tamaño para que quepa en el PDF (reduce tamaño si es necesario)
                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = pdf.internal.pageSize.getHeight();

                const img = new Image();
                img.onload = () => {
                    // Calculamos escala para mantener proporción
                    let imgWidth = img.width;
                    let imgHeight = img.height;
                    const ratio = Math.min(pdfWidth / imgWidth, pdfHeight / imgHeight) * 0.95;
                    imgWidth *= ratio;
                    imgHeight *= ratio;

                    pdf.addImage(img, 'PNG', (pdfWidth - imgWidth) / 2, (pdfHeight - imgHeight) / 2, imgWidth, imgHeight);
                    pdf.save('grafica-funkopop.pdf');

                    btnPDF.disabled = false;
                    btnPDF.textContent = 'Descargar Gráfica en PDF';
                };
                img.src = imgData;
            });
        }
    </script>
</body>
</html>
