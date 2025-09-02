<?php
require_once '../templates/header.php';
check_login('requisitante');

// Buscar o histórico de solicitações do usuário logado
$id_usuario = $_SESSION['id_usuario'];
$stmt = $pdo->prepare("
    SELECT id_solicitacao, data_prevista, status, criado_em 
    FROM solicitacoes 
    WHERE id_usuario = ? 
    ORDER BY criado_em DESC
");
$stmt->execute([$id_usuario]);
$solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Minhas Solicitações</h2>

<?php if (empty($solicitacoes)): ?>
    <p>Você ainda não fez nenhuma solicitação.</p>
    <a href="criar_solicitacao.php" class="btn">Criar minha primeira solicitação</a>
<?php else: ?>
    <div class="table-wrapper">
        <table class="responsive-table">
            <thead>
                <tr>
                    <th>Nº da Solicitação</th>
                    <th>Data de Criação</th>
                    <th>Data Prevista de Uso</th>
                    <th>Status</th>
                    <th>Ações</th> <!-- NOVA COLUNA -->
                </tr>
            </thead>
            <tbody>
                <?php foreach ($solicitacoes as $s): ?>
                <tr>
                    <td><?php echo $s['id_solicitacao']; ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($s['criado_em'])); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($s['data_prevista'])); ?></td>
                    <td><strong class="status-badge status-<?php echo htmlspecialchars($s['status']); ?>"><?php echo htmlspecialchars(ucfirst($s['status'])); ?></strong></td>
                    <td>
                        <!-- **NOVO LINK AQUI** -->
                        <a href="../ver_solicitacao.php?id=<?php echo $s['id_solicitacao']; ?>" class="btn">Ver Detalhes</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once '../templates/footer.php'; ?>