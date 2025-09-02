<?php
require_once 'templates/header.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: /sistema_estoque/auth/login.php");
    exit();
}

$id_solicitacao = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_solicitacao) {
    echo "<p class='error'>ID de solicitação inválido.</p>";
    require_once 'templates/footer.php';
    exit();
}

try {
    // Buscar dados da solicitação e nome do requisitante
    $stmt_solicitacao = $pdo->prepare("
        SELECT s.*, u.nome AS nome_usuario, u.email AS email_usuario
        FROM solicitacoes s
        JOIN usuarios u ON s.id_usuario = u.id_usuario
        WHERE s.id_solicitacao = ?
    ");
    $stmt_solicitacao->execute([$id_solicitacao]);
    $solicitacao = $stmt_solicitacao->fetch(PDO::FETCH_ASSOC);

    if (!$solicitacao) {
        throw new Exception("Solicitação não encontrada.");
    }

    // VERIFICAÇÃO DE PERMISSÃO:
    // Um requisitante só pode ver suas próprias solicitações. Um admin pode ver todas.
    if ($_SESSION['perfil'] === 'requisitante' && $solicitacao['id_usuario'] != $_SESSION['id_usuario']) {
        registrar_log($pdo, $_SESSION['id_usuario'], 'Acesso Negado', "Tentativa de ver solicitação #{$id_solicitacao} de outro usuário.");
        throw new Exception("Acesso negado. Você não tem permissão para ver esta solicitação.");
    }

    // Buscar itens da solicitação
    $stmt_itens = $pdo->prepare("
        SELECT si.*, m.nome AS nome_material, m.codigo_interno
        FROM solicitacao_itens si
        JOIN materiais m ON si.id_material = m.id_material
        WHERE si.id_solicitacao = ?
    ");
    $stmt_itens->execute([$id_solicitacao]);
    $itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    echo "<p class='error'>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    require_once 'templates/footer.php';
    exit();
}
?>

<h2>Detalhes da Solicitação Nº <?php echo $solicitacao['id_solicitacao']; ?></h2>

<div class="solicitacao-details">
    <div><strong>Status:</strong> <span class="status-badge status-<?php echo htmlspecialchars($solicitacao['status']); ?>"><?php echo htmlspecialchars(ucfirst($solicitacao['status'])); ?></span></div>
    <div><strong>Requisitante:</strong> <?php echo htmlspecialchars($solicitacao['nome_usuario']); ?> (<?php echo htmlspecialchars($solicitacao['email_usuario']); ?>)</div>
    <div><strong>Data de Criação:</strong> <?php echo date('d/m/Y H:i', strtotime($solicitacao['criado_em'])); ?></div>
    <div><strong>Data Prevista para Uso:</strong> <?php echo date('d/m/Y', strtotime($solicitacao['data_prevista'])); ?> às <?php echo date('H:i', strtotime($solicitacao['hora_prevista'])); ?></div>
    <div><strong>Local Previsto para Uso:</strong> <?php echo htmlspecialchars($solicitacao['local_previsto']); ?></div>
    <?php if (!empty($solicitacao['observacoes'])): ?>
        <div><strong>Observações:</strong> <?php echo nl2br(htmlspecialchars($solicitacao['observacoes'])); ?></div>
    <?php endif; ?>
</div>

<h3>Itens Solicitados</h3>
<div class="table-wrapper">
    <table class="responsive-table">
        <thead>
            <tr>
                <th>Material</th>
                <th>Código Interno</th>
                <th>Qtd. Solicitada</th>
                <th>Qtd. Entregue</th>
                <th>Qtd. Devolvida</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($itens as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['nome_material']); ?></td>
                    <td><?php echo htmlspecialchars($item['codigo_interno']); ?></td>
                    <td><?php echo $item['quantidade_solic']; ?></td>
                    <td><?php echo $item['quantidade_entreg'] ?? 'N/D'; ?></td>
                    <td><?php echo $item['quantidade_dev'] ?? 'N/D'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<a href="javascript:history.back()" class="btn btn-secondary" style="margin-top: 20px;">Voltar</a>

<style>
.solicitacao-details {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.status-badge {
    padding: 3px 8px;
    border-radius: 12px;
    color: white;
    font-weight: bold;
    font-size: 0.9em;
}
.status-pendente { background-color: #ffc107; color: #333; }
.status-aprovado { background-color: #28a745; }
.status-recusado { background-color: #dc3545; }
.status-entregue { background-color: #17a2b8; }
.status-devolvido { background-color: #6c757d; }
</style>

<?php require_once 'templates/footer.php'; ?>