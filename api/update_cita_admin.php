<?php
session_start();
require_once '../config/Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$id_cita = $input['id_cita'] ?? 0;
$fecha = trim($input['fecha_cita'] ?? '');
$hora = trim($input['hora_inicio'] ?? '');

if (!$id_cita || empty($fecha) || empty($hora)) {
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
    exit;
}

$hora = substr($hora, 0, 5);

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    echo json_encode(['status' => 'error', 'message' => 'Fecha inválida']);
    exit;
}

if (!preg_match('/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/', $hora)) {
    echo json_encode(['status' => 'error', 'message' => 'Hora inválida (use HH:MM)']);
    exit;
}

$db = Database::getInstance();

try {
    $stmt = $db->prepare("SELECT id_doctor FROM citas WHERE id_cita = ?");
    $stmt->execute([$id_cita]);
    $cita = $stmt->fetch();
    
    if (!$cita) {
        echo json_encode(['status' => 'error', 'message' => 'Cita no encontrada']);
        exit;
    }
    
    $id_doctor = $cita['id_doctor'];

    $check = $db->prepare("SELECT id_cita FROM citas WHERE id_doctor = ? AND fecha_cita = ? AND hora_inicio = ? AND estado_cita != 'Cancelada' AND id_cita != ?");
    $check->execute([$id_doctor, $fecha, $hora, $id_cita]);
    
    if ($check->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'El horario seleccionado ya no está disponible']);
        exit;
    }

    $update = $db->prepare("UPDATE citas SET fecha_cita = ?, hora_inicio = ? WHERE id_cita = ?");
    $update->execute([$fecha, $hora, $id_cita]);

    echo json_encode(['status' => 'success', 'message' => 'Cita actualizada correctamente']);
    
} catch (Exception $e) {
    error_log("Error en update_cita_admin.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error interno del servidor']);
}
?>