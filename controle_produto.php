<?php
// ---------- CONEXÃO COM O BANCO ----------
$conn = new mysqli("localhost", "root", "", "produtos_db");
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$mensagem = "";

// ---------- AÇÕES (formulários enviados por POST) ----------
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Troca vírgula por ponto para aceitar "3800,00"
    $descricao = trim($_POST["descricao"] ?? "");
    $categoria = $_POST["categoria"] ?? "";
    $compra    = (float) str_replace(",", ".", $_POST["valor_compra"] ?? "0");
    $venda     = (float) str_replace(",", ".", $_POST["valor_venda"] ?? "0");
    $qtd       = (int) ($_POST["estoque"] ?? 0);
    $acao      = $_POST["acao"] ?? "";

    // ----- CADASTRAR -----
    if ($acao == "cadastrar") {
        if ($descricao == "" || $categoria == "" || $compra <= 0 || $venda <= 0 || $qtd < 0) {
            $mensagem = "Preencha todos os campos corretamente para cadastrar.";
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO produtos (descricao, categoria, valor_compra, valor_venda, estoque)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("ssddi", $descricao, $categoria, $compra, $venda, $qtd);
            $stmt->execute();
            $stmt->close();
            $mensagem = "Produto cadastrado com sucesso!";
        }
    }

    // ----- VENDER (baixa no estoque) -----
    // Usa a Descrição e a Qtd. Estoque como quantidade vendida
    if ($acao == "vender") {
        if ($descricao == "" || $qtd <= 0) {
            $mensagem = "Para vender, informe a descrição do produto e a quantidade (maior que zero).";
        } else {
            $stmt = $conn->prepare("SELECT id, estoque FROM produtos WHERE descricao = ? LIMIT 1");
            $stmt->bind_param("s", $descricao);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $produto = $resultado->fetch_assoc();
            $stmt->close();

            if (!$produto) {
                $mensagem = "Produto não encontrado.";
            } elseif ($produto["estoque"] < $qtd) {
                $mensagem = "Estoque insuficiente! Disponível: " . $produto["estoque"];
            } else {
                $stmt = $conn->prepare("UPDATE produtos SET estoque = estoque - ? WHERE id = ?");
                $stmt->bind_param("ii", $qtd, $produto["id"]);
                $stmt->execute();
                $stmt->close();
                $mensagem = "Venda realizada! Baixa de $qtd unidade(s) no estoque.";
            }
        }
    }

    // ----- EXCLUIR -----
    if ($acao == "excluir") {
        $id = (int) ($_POST["id"] ?? 0);
        $stmt = $conn->prepare("DELETE FROM produtos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $mensagem = "Produto excluído.";
    }
}

// ---------- CONSULTA FILTRADA (GET) ----------
$busca = trim($_GET["busca"] ?? "");
$filtro = "%" . $busca . "%";

$stmt = $conn->prepare(
    "SELECT * FROM produtos
     WHERE descricao LIKE ? OR categoria LIKE ?
     ORDER BY descricao"
);
$stmt->bind_param("ss", $filtro, $filtro);
$stmt->execute();
$lista = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Controle de Produtos</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f1f5f9; }
        .caixa { background: #fff; width: 850px; margin: 30px auto; padding: 25px; border-radius: 8px; }
        h2 { border-bottom: 2px solid #2563eb; padding-bottom: 8px; }
        .linha { display: flex; gap: 15px; margin-bottom: 15px; }
        .campo { display: flex; flex-direction: column; flex: 1; font-weight: bold; font-size: 13px; }
        input, select { padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; }
        .btn { background: #2563eb; color: #fff; border: none; padding: 10px 20px;
               font-weight: bold; border-radius: 4px; cursor: pointer; margin-right: 10px; }
        .msg { background: #dbeafe; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #f1f5f9; text-align: left; padding: 10px; }
        td { padding: 10px; border-bottom: 1px solid #eee; }
        .btn-x { background: #dc2626; color: #fff; border: none; border-radius: 50%;
                 width: 28px; height: 28px; cursor: pointer; font-weight: bold; }
        .busca { display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body>
<div class="caixa">

    <h2>Cadastro de Produto</h2>

    <?php if ($mensagem != "") { ?>
        <div class="msg"><?php echo htmlspecialchars($mensagem); ?></div>
    <?php } ?>

    <form method="post" action="controle_produto.php">
        <div class="linha">
            <div class="campo">Descrição
                <input type="text" name="descricao">
            </div>
            <div class="campo">Categoria
                <select name="categoria">
                    <option value="Informática">Informática</option>
                    <option value="Eletrônicos">Eletrônicos</option>
                    <option value="Móveis">Móveis</option>
                    <option value="Papelaria">Papelaria</option>
                    <option value="Outros">Outros</option>
                </select>
            </div>
            <div class="campo">Valor Compra (R$)
                <input type="text" name="valor_compra" placeholder="3800,00">
            </div>
            <div class="campo">Valor Venda (R$)
                <input type="text" name="valor_venda" placeholder="4120,00">
            </div>
        </div>

        <div class="linha">
            <div class="campo" style="max-width:200px;">Qtd. Estoque
                <input type="number" name="estoque" min="0">
            </div>
        </div>

        <button class="btn" type="submit" name="acao" value="cadastrar">CADASTRAR PRODUTO</button>
        <button class="btn" type="submit" name="acao" value="vender">VENDER PRODUTO</button>
    </form>

    <div class="busca">
        <h2 style="flex:1;">Inventário</h2>
        <form method="get" action="controle_produto.php">
            <input type="text" name="busca" placeholder="pesquisar por descrição ou categoria"
                   value="<?php echo htmlspecialchars($busca); ?>" size="35">
            <button class="btn" type="submit">Buscar</button>
        </form>
    </div>

    <table>
        <tr>
            <th>Descrição</th>
            <th>Categoria</th>
            <th>Venda</th>
            <th>Lucro Unit.</th>
            <th>Estoque</th>
            <th>Ações</th>
        </tr>
        <?php while ($p = $lista->fetch_assoc()) {
            $lucro = $p["valor_venda"] - $p["valor_compra"]; ?>
            <tr>
                <td><?php echo htmlspecialchars($p["descricao"]); ?></td>
                <td><?php echo htmlspecialchars($p["categoria"]); ?></td>
                <td>R$ <?php echo number_format($p["valor_venda"], 2, ",", "."); ?></td>
                <td>R$ <?php echo number_format($lucro, 2, ",", "."); ?></td>
                <td><?php echo $p["estoque"]; ?></td>
                <td>
                    <form method="post" action="controle_produto.php"
                          onsubmit="return confirm('Excluir este produto?');">
                        <input type="hidden" name="id" value="<?php echo $p["id"]; ?>">
                        <button class="btn-x" type="submit" name="acao" value="excluir">X</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    </table>

</div>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
