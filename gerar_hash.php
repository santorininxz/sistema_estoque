<?php
// Defina a senha que você deseja usar
$senha_para_testar = 'admin123';

// Gera o hash seguro
$hash = password_hash($senha_para_testar, PASSWORD_DEFAULT);

// Exibe o hash
echo "<h1>Hash Gerado</h1>";
echo "<p>Sua senha '<strong>" . htmlspecialchars($senha_para_testar) . "</strong>' gerou o seguinte hash:</p>";
echo "<textarea rows='3' cols='80' readonly>" . htmlspecialchars($hash) . "</textarea>";
echo "<p>Copie o código acima e siga as instruções para atualizar o banco de dados.</p>";
?>