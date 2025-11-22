<?php
require_once __DIR__ . '/../../bootstrap.php';

// Protege a página: só usuários logados podem acessar
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// Busca os dados atuais do usuário
$user_id = $_SESSION['user_id'] ?? 0;
$sql = "SELECT nome, email, foto_perfil FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
// Define um avatar padrão se o usuário não tiver foto
$user_avatar = $user['foto_perfil'] ? htmlspecialchars($user['foto_perfil']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['nome']) . '&background=1f2937&color=d1d5db&size=128';
$stmt->close();

?>