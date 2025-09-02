<?php
require_once '../templates/header.php';
check_login('admin');

$success_message = '';
$error_message = '';

// Bloco de código para processar a atualização de status via formulário POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_solicitacao'])) {
    $id_solicitacao = filter_input(INPUT_POST, 'id_solicitacao', FILTER_VALIDATE_INT);
    $novo_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    
    if ($id_solicitacao && in_array($novo_status, ['pendente', 'aprovado', 'recusado', 'entregue', 'devolvido'])) {
        try {
            $pdo->beginTransaction();

            // Busca o status atual da solicitação para validação
            $stmt_old = $pdo->prepare("SELECT status FROM solicitacoes WHERE id_solicitacao = ? FOR UPDATE");
            $stmt_old->execute([$id_solicitacao]);
            $status_antigo = $stmt_old->fetchColumn();

            // Evita processamento desnecessário se o status for o mesmo
            if ($status_antigo === $novo_status) {
                throw new Exception("A solicitação já está com o status '{$novo_status}'. Nenhuma alteração foi feita.");
            }

            // Define as regras de transição de status permitidas (a "esteira")
            $allowed_transitions = [
                'pendente' => ['aprovado', 'recusado', 'entregue'],
                'aprovado' => ['entregue', 'recusado'],
                'recusado' => [], // De 'recusado' não se pode ir para nenhum outro estado
                'entregue' => ['devolvido'],
                'devolvido' => [] // De 'devolvido' também não
            ];

            // Valida se a mudança de status é permitida
            if (!in_array($novo_status, $allowed_transitions[$status_antigo])) {
                throw new Exception("Ação inválida: não é possível alterar o status de '{$status_antigo}' para '{$novo_status}'.");
            }

            // Lógica para quando o status muda para "Entregue"
            if ($novo_status === 'entregue' && $status_antigo !== 'entregue') {
                $itens_stmt = $pdo->prepare("
                    SELECT si.id_material, si.quantidade_solic, m.nome AS nome_material, m.quantidade AS quantidade_atual
                    FROM solicitacao_itens si JOIN materiais m ON si.id_material = m.id_material
                    WHERE si.id_solicitacao = ?
                ");
                $itens_stmt->execute([$id_solicitacao]);
                $itens_para_entrega = $itens_stmt->fetchAll(PDO::FETCH_ASSOC);

                // VERIFICAÇÃO DE ESTOQUE CRÍTICA NO LADO DO SERVIDOR
                foreach ($itens_para_entrega as $item) {
                    if ($item['quantidade_solic'] > $item['quantidade_atual']) {
                        throw new Exception(
                            "Estoque insuficiente para o item '<strong>" . htmlspecialchars($item['nome_material']) . "</strong>'. " .
                            "Solicitado: {$item['quantidade_solic']}, Disponível: {$item['quantidade_atual']}. Operação cancelada."
                        );
                    }
                }

                // Se passou na verificação, prossegue com a baixa de estoque
                foreach ($itens_para_entrega as $item) {
                    $pdo->prepare("UPDATE materiais SET quantidade = quantidade - ? WHERE id_material = ?")->execute([$item['quantidade_solic'], $item['id_material']]);
                    $pdo->prepare("UPDATE solicitacao_itens SET quantidade_entreg = ? WHERE id_solicitacao = ? AND id_material = ?")->execute([$item['quantidade_solic'], $id_solicitacao, $item['id_material']]);
                }
                registrar_log($pdo, $_SESSION['id_usuario'], 'Entrega de Material', "Solicitação ID: {$id_solicitacao}");
            }
            
            // Lógica para quando o status muda para "Devolvido"
            elseif ($novo_status === 'devolvido' && $status_antigo === 'entregue') {
                $itens_stmt = $pdo->prepare("
                    SELECT si.id_material, si.quantidade_entreg 
                    FROM solicitacao_itens si JOIN materiais m ON si.id_material = m.id_material
                    WHERE si.id_solicitacao = ? AND m.tipo = 'reutilizavel'
                ");
                $itens_stmt->execute([$id_solicitacao]);
                $itens_reutilizaveis = $itens_stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($itens_reutilizaveis as $item) {
                    $pdo->prepare("UPDATE materiais SET quantidade = quantidade + ? WHERE id_material = ?")->execute([$item['quantidade_entreg'], $item['id_material']]);
                    $pdo->prepare("UPDATE solicitacao_itens SET quantidade_dev = ? WHERE id_solicitacao = ? AND id_material = ?")->execute([$item['quantidade_entreg'], $id_solicitacao, $item['id_material']]);
                }
                registrar_log($pdo, $_SESSION['id_usuario'], 'Devolução de Material', "Solicitação ID: {$id_solicitacao}");
            }
            
            // Atualiza o status da solicitação no banco
            $pdo->prepare("UPDATE solicitacoes SET status = ? WHERE id_solicitacao = ?")->execute([$novo_status, $id_solicitacao]);
            registrar_log($pdo, $_SESSION['id_usuario'], 'Mudança de Status de Solicitação', "ID: {$id_solicitacao}, de '{$status_antigo}' para '{$novo_status}'");

            $pdo->commit();
            $success_message = "Status da solicitação #{$id_solicitacao} atualizado com sucesso!";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = $e->getMessage();
        }
    }
}

// Bloco para buscar os dados para exibição na página
// CORREÇÃO: Usando LEFT JOIN para garantir que todas as solicitações sejam listadas
$stmt_solicitacoes = $pdo->query("
    SELECT s.*, u.nome as nome_usuario
    FROM solicitacoes s
    LEFT JOIN usuarios u ON s.id_usuario = u.id_usuario
    ORDER BY s.criado_em DESC
");
$solicitacoes = $stmt_solicitacoes->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Gerenciar Solicitações</h2>

<?php if ($success_message): ?><p class="success"><?php echo htmlspecialchars($success_message); ?></p><?php endif; ?>
<?php if ($error_message): ?><p class="error"><?php echo $error_message; // Permite HTML como <strong> na msg de erro ?></p><?php endif; ?>

<div class="table-wrapper">
    <table class="responsive-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Requisitante</th>
                <th>Data Prevista</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($solicitacoes)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px;">Nenhuma solicitação encontrada.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($solicitacoes as $s): ?>
                <tr>
                    <td><?php echo $s['id_solicitacao']; ?></td>
                    <td>
                        <?php echo htmlspecialchars($s['nome_usuario'] ?? 'Usuário Removido'); ?>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($s['data_prevista'])); ?></td>
                    <td><strong class="status-badge status-<?php echo htmlspecialchars($s['status']); ?>"><?php echo htmlspecialchars(ucfirst($s['status'])); ?></strong></td>
                    <td style="display:flex; flex-wrap: wrap; gap: 5px; align-items: center;">
                        <form action="gerenciar_solicitacoes.php" method="POST" style="display:inline-flex; gap: 5px; margin: 0;">
                            <input type="hidden" name="id_solicitacao" value="<?php echo $s['id_solicitacao']; ?>">
                            <select name="status">
                                <option value="pendente" <?php if($s['status'] == 'pendente') echo 'selected'; ?>>Pendente</option>
                                <option value="aprovado" <?php if($s['status'] == 'aprovado') echo 'selected'; ?>>Aprovado</option>
                                <option value="recusado" <?php if($s['status'] == 'recusado') echo 'selected'; ?>>Recusado</option>
                                <option value="entregue" <?php if($s['status'] == 'entregue') echo 'selected'; ?>>Entregue</option>
                                <option value="devolvido" <?php if($s['status'] == 'devolvido') echo 'selected'; ?>>Devolvido</option>
                            </select>
                            <button type="submit" class="btn btn-secondary" style="padding: 8px 12px;">Alterar</button>
                        </form>
                        <a href="../ver_solicitacao.php?id=<?php echo $s['id_solicitacao']; ?>" class="btn">Ver Detalhes</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php 
require_once '../templates/footer.php'; 
?>