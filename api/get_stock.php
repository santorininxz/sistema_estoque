<?php
header('Content-Type: application/json');
require_once '../config.php';

// Apenas permitir acesso se o usuário estiver logado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['error' => 'Acesso não autorizado']);
    exit();
}

$id_material = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_material) {
    echo json_encode(['error' => 'ID de material inválido']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT quantidade FROM materiais WHERE id_material = ?");
    $stmt->execute([$id_material]);
    $material = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($material) {
        echo json_encode(['quantidade' => $material['quantidade']]);
    } else {
        echo json_encode(['quantidade' => 0]);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro ao consultar o banco de dados.']);
}
?>