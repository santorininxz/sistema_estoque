<?php
require_once '../templates/header.php';
check_login('requisitante');

$success_message = '';
$error_message = '';

// Lógica de processamento do formulário quando enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Sanitização e validação dos dados de entrada
    $data_prevista = trim(filter_input(INPUT_POST, 'data_prevista'));
    $hora_prevista = trim(filter_input(INPUT_POST, 'hora_prevista'));
    $local_previsto = trim(filter_input(INPUT_POST, 'local_previsto', FILTER_SANITIZE_STRING));
    $observacoes = trim(filter_input(INPUT_POST, 'observacoes', FILTER_SANITIZE_STRING));
    $materiais_post = $_POST['materiais'] ?? [];
    $quantidades_post = $_POST['quantidades'] ?? [];

    // 2. LÓGICA DE AGREGAÇÃO PARA EVITAR ITENS DUPLICADOS
    $itens_agregados = [];
    if (is_array($materiais_post)) {
        foreach ($materiais_post as $index => $id_material) {
            $id_material = filter_var($id_material, FILTER_VALIDATE_INT);
            $quantidade = filter_var($quantidades_post[$index], FILTER_VALIDATE_INT);

            if ($id_material && $quantidade > 0) {
                if (isset($itens_agregados[$id_material])) {
                    // Se o material já foi adicionado, apenas soma a nova quantidade
                    $itens_agregados[$id_material] += $quantidade;
                } else {
                    // Adiciona o material pela primeira vez
                    $itens_agregados[$id_material] = $quantidade;
                }
            }
        }
    }

    // 3. Verifica se há itens válidos antes de prosseguir
    if (empty($itens_agregados)) {
        $error_message = "Você deve adicionar pelo menos um item válido à solicitação.";
    } else {
        try {
            // Inicia uma transação para garantir que tudo seja salvo corretamente
            $pdo->beginTransaction();

            // Insere a solicitação principal
            $sql_solicitacao = "INSERT INTO solicitacoes (id_usuario, data_prevista, hora_prevista, local_previsto, observacoes) VALUES (?, ?, ?, ?, ?)";
            $stmt_solicitacao = $pdo->prepare($sql_solicitacao);
            $stmt_solicitacao->execute([$_SESSION['id_usuario'], $data_prevista, $hora_prevista, $local_previsto, $observacoes]);
            $id_solicitacao = $pdo->lastInsertId();

            // Insere os itens da solicitação (usando a lista agregada)
            $sql_item = "INSERT INTO solicitacao_itens (id_solicitacao, id_material, quantidade_solic) VALUES (?, ?, ?)";
            $stmt_item = $pdo->prepare($sql_item);

            foreach ($itens_agregados as $id_material => $quantidade_total) {
                $stmt_item->execute([$id_solicitacao, $id_material, $quantidade_total]);
            }

            // Registra a ação no log de auditoria
            registrar_log($pdo, $_SESSION['id_usuario'], 'Criação de Solicitação', "ID da Solicitação: {$id_solicitacao}");
            
            // Confirma as alterações no banco de dados
            $pdo->commit();
            $success_message = "Solicitação nº {$id_solicitacao} criada com sucesso! <a href='../ver_solicitacao.php?id={$id_solicitacao}'>Ver detalhes</a>";

        } catch (Exception $e) {
            // Em caso de erro, desfaz todas as alterações
            $pdo->rollBack();
            $error_message = "Erro ao criar solicitação: " . $e->getMessage();
        }
    }
}

// Busca os materiais disponíveis para preencher o formulário
$materiais_db = $pdo->query("SELECT id_material, nome, quantidade FROM materiais WHERE status = 1 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Criar Nova Solicitação de Material</h2>

<?php if ($success_message): ?><p class="success"><?php echo $success_message; // Mensagem já contém link, não precisa de htmlspecialchars ?></p><?php endif; ?>
<?php if ($error_message): ?><p class="error"><?php echo htmlspecialchars($error_message); ?></p><?php endif; ?>

<form action="criar_solicitacao.php" method="post" id="form-solicitacao">
    <div class="form-group">
        <label for="data_prevista">Data Prevista de Uso</label>
        <input type="date" id="data_prevista" name="data_prevista" required min="<?php echo date('Y-m-d'); ?>">
    </div>
    <div class="form-group">
        <label for="hora_prevista">Hora Prevista de Uso</label>
        <input type="time" id="hora_prevista" name="hora_prevista" required>
    </div>
    <div class="form-group">
        <label for="local_previsto">Local de Uso</label>
        <input type="text" id="local_previsto" name="local_previsto" placeholder="Ex: Laboratório 1, Sala 3" required>
    </div>
    <div class="form-group">
        <label for="observacoes">Observações</label>
        <textarea id="observacoes" name="observacoes" rows="3" placeholder="Qualquer informação adicional relevante"></textarea>
    </div>

    <h3>Itens da Solicitação</h3>
    <div id="itens-container">
        <div class="item-row">
            <select name="materiais[]" class="material-select" required>
                <option value="">Selecione um material</option>
                <?php foreach ($materiais_db as $material): ?>
                    <option value="<?php echo $material['id_material']; ?>"><?php echo htmlspecialchars($material['nome']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="quantidades[]" class="quantidade-input" placeholder="Qtd." required min="1" style="width: 100px;">
            <span class="stock-info"></span>
            <button type="button" class="btn btn-danger remove-item">Remover</button>
        </div>
    </div>

    <div style="display:flex; gap: 10px; margin-top: 10px;">
        <button type="button" id="add-item" class="btn btn-secondary">Adicionar Outro Item</button>
    </div>
    <hr>
    <div style="display:flex; gap: 10px;">
        <button type="submit" id="submit-btn">Enviar Solicitação</button>
        <a href="/sistema_estoque/requisitante/" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<style>
.item-row { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; flex-wrap: wrap; }
.item-row select { flex: 1; min-width: 200px; }
.stock-info { font-size: 0.9em; min-width: 150px; color: #6c757d; }
.item-row .btn-danger { padding: 8px 12px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const itensContainer = document.getElementById('itens-container');
    const addItemBtn = document.getElementById('add-item');
    const submitBtn = document.getElementById('submit-btn');

    async function checkStock(materialSelect, quantidadeInput, stockInfoSpan) {
        const materialId = materialSelect.value;
        const quantidadeDesejada = parseInt(quantidadeInput.value, 10);
        
        if (!materialId || isNaN(quantidadeDesejada) || quantidadeDesejada <= 0) {
            stockInfoSpan.textContent = '';
            validateAllFields();
            return;
        }

        try {
            const response = await fetch(`/sistema_estoque/api/get_stock.php?id=${materialId}`);
            const data = await response.json();

            if (data.error) {
                stockInfoSpan.textContent = 'Erro ao verificar.'; stockInfoSpan.style.color = 'red';
            } else {
                const estoqueDisponivel = data.quantidade;
                stockInfoSpan.textContent = `Disponível: ${estoqueDisponivel}`;
                if (quantidadeDesejada > estoqueDisponivel) {
                    stockInfoSpan.textContent += ' - Insuficiente!';
                    stockInfoSpan.style.color = 'red';
                } else {
                    stockInfoSpan.style.color = 'green';
                }
            }
            validateAllFields();
        } catch (error) {
            stockInfoSpan.textContent = 'Erro de conexão.'; stockInfoSpan.style.color = 'red';
            validateAllFields();
        }
    }
    
    function validateAllFields() {
        let allValid = true;
        const selectedMaterials = new Set();

        document.querySelectorAll('.item-row').forEach(row => {
            const select = row.querySelector('.material-select');
            const stockInfoSpan = row.querySelector('.stock-info');

            // Valida estoque insuficiente
            if (stockInfoSpan.style.color === 'red') {
                allValid = false;
            }
            // Valida itens duplicados
            if (select.value) {
                if (selectedMaterials.has(select.value)) {
                    allValid = false;
                    select.style.borderColor = 'red';
                } else {
                    selectedMaterials.add(select.value);
                    select.style.borderColor = '#ccc';
                }
            }
        });
        submitBtn.disabled = !allValid;
    }

    itensContainer.addEventListener('input', function(e) {
        const target = e.target;
        if (target.classList.contains('quantidade-input') || target.classList.contains('material-select')) {
            const itemRow = target.closest('.item-row');
            const materialSelect = itemRow.querySelector('.material-select');
            const quantidadeInput = itemRow.querySelector('.quantidade-input');
            const stockInfoSpan = itemRow.querySelector('.stock-info');
            checkStock(materialSelect, quantidadeInput, stockInfoSpan);
            if (target.classList.contains('material-select')) {
                validateAllFields(); // Valida duplicidade ao mudar a seleção
            }
        }
    });

    itensContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item')) {
            if (itensContainer.children.length > 1) {
                e.target.closest('.item-row').remove();
            } else {
                const firstRow = itensContainer.querySelector('.item-row');
                firstRow.querySelector('select').selectedIndex = 0;
                firstRow.querySelector('input').value = '';
                firstRow.querySelector('.stock-info').textContent = '';
                firstRow.querySelector('.stock-info').style.color = '';
            }
            validateAllFields();
        }
    });

    addItemBtn.addEventListener('click', function() {
        const firstItemRow = itensContainer.firstElementChild;
        const newItemRow = firstItemRow.cloneNode(true);
        newItemRow.querySelector('select').selectedIndex = 0;
        newItemRow.querySelector('input').value = '';
        newItemRow.querySelector('.stock-info').textContent = '';
        newItemRow.querySelector('.stock-info').style.color = '';
        newItemRow.querySelector('.material-select').style.borderColor = '#ccc';
        itensContainer.appendChild(newItemRow);
        validateAllFields();
    });

    validateAllFields(); // Executa uma validação inicial
});
</script>

<?php
require_once '../templates/footer.php';
?>