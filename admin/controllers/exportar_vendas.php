<?php
/**
 * controllers/exportar_vendas.php
 * Exporta os pedidos de um período em CSV (separador ";" e UTF-8 com BOM,
 * para abrir direto no Excel em português).
 */

require_once __DIR__ . '/../models/pedidos.php';
require_once __DIR__ . '/../models/relatorios.php';

exigir_login();

[$de, $ate] = Relatorio::periodo($_GET['de'] ?? null, $_GET['ate'] ?? null);
$pedidos    = (new Relatorio())->pedidosDoPeriodo($de, $ate);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="vendas_' . $de . '_a_' . $ate . '.csv"');

$saida = fopen('php://output', 'w');
fwrite($saida, "\xEF\xBB\xBF");

fputcsv($saida, ['Pedido', 'Data', 'Cliente', 'E-mail', 'Pagamento', 'Status', 'Itens', 'Total (R$)'], ';');

foreach ($pedidos as $p) {
    fputcsv($saida, [
        $p['id'],
        date('d/m/Y H:i', strtotime($p['created_at'])),
        $p['cliente_nome'],
        $p['cliente_email'],
        Pedido::PAGAMENTOS[$p['pagamento']],
        Pedido::STATUS[$p['status']],
        $p['itens'],
        number_format((float)$p['total'], 2, ',', ''),
    ], ';');
}

fclose($saida);
exit;
