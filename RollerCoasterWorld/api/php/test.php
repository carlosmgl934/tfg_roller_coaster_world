<?php
$path = 'C:/xampp/htdocs/tfg/tfg_roller_coaster_world/RollerCoasterWorld/api/database/db_conexion.php';

echo "<pre>";
echo "Ruta absoluta probada: " . $path . "\n";
echo "Existe? " . (file_exists($path) ? 'SÍ' : 'NO') . "\n";
echo "Es legible? " . (is_readable($path) ? 'SÍ' : 'NO') . "\n";

if (file_exists($path)) {
  echo "Intentando require_once...\n";
  try {
    require_once $path;
    echo "require_once OK\n";
    echo "Intentando instanciar DBConexion...\n";
    $db = new DBConexion();
    echo "Instancia creada OK\n";
  } catch (Throwable $e) {
    echo "Error fatal: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString();
  }
} else {
  echo "El archivo NO se encuentra en disco";
}
echo "</pre>";
?>