<?php
header('Content-Type: application/json');

$filename = 'dados_pdv_unificados.csv';

// Função para ler o arquivo CSV
function read_csv($filename) {
    $data = [];
    if (!file_exists($filename)) {
        // Se o arquivo não existir, retorna um array vazio para evitar erro
        return $data;
    }
    if (($handle = fopen($filename, "r")) !== FALSE) {
        while (($row = fgetcsv($handle, 1000, ";")) !== FALSE) {
            $data[] = $row;
        }
        fclose($handle);
    }
    return $data;
}

// Função para escrever no arquivo CSV
function write_csv($filename, $data) {
    // Garante que o diretório existe e tem permissões de escrita
    $dir = dirname($filename);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true); // Cria o diretório recursivamente com permissões totais
    }

    if (($handle = fopen($filename, "w")) !== FALSE) {
        foreach ($data as $row) {
            fputcsv($handle, $row, ";");
        }
        fclose($handle);
        // Define permissões para o arquivo recém-criado/modificado
        chmod($filename, 0666); 
    } else {
        error_log("Erro: Não foi possível abrir o arquivo CSV para escrita: " . $filename);
        return false;
    }
    return true;
}

// Lida com as requisições
$action = $_GET['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_data') {
    // Retorna os dados para a página
    $data = read_csv($filename);
    // Para compatibilidade, se o CSV estiver vazio ou não tiver o formato JSON esperado,
    // retorne uma estrutura padrão para o frontend.
    if (empty($data)) {
        echo json_encode([
            'products' => [],
            'sales' => [],
            'cashMovements' => [],
            'workingCapitalAssets' => [],
            'workingCapitalLiabilities' => [],
            'markupData' => [
                'revenue' => 0,
                'variableExpensesDetails' => [],
                'fixedExpensesDetails' => [],
                'profitMargin' => 0
            ]
        ]);
    } else {
        // Assume que o CSV contém uma única linha JSON serializada
        // ou que o front-end espera uma estrutura de arrays de arrays
        // Se o front-end espera um JSON complexo, o PHP precisa deserializar.
        // Pelo seu JS, parece que ele espera um JSON completo.
        // Vamos assumir que o CSV armazena uma única linha com o JSON completo.
        // Isso é uma simplificação, um CSV não é um banco de dados JSON.
        // Para um sistema mais robusto, cada tipo de dado deveria ter seu próprio CSV
        // ou você usaria um banco de dados real.

        // Tentativa de ler o CSV como um JSON completo (se foi salvo assim)
        $full_data_string = '';
        if (isset($data[0][0])) {
            $full_data_string = $data[0][0]; // Pega a primeira célula da primeira linha
        }
        $decoded_data = json_decode($full_data_string, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_data)) {
            echo json_encode($decoded_data);
        } else {
            // Se não for um JSON válido, retorna uma estrutura padrão vazia
            error_log("Dados no CSV não são JSON válidos ou estão vazios. Retornando estrutura padrão.");
            echo json_encode([
                'products' => [],
                'sales' => [],
                'cashMovements' => [],
                'workingCapitalAssets' => [],
                'workingCapitalLiabilities' => [],
                'markupData' => [
                    'revenue' => 0,
                    'variableExpensesDetails' => [],
                    'fixedExpensesDetails' => [],
                    'profitMargin' => 0
                ]
            ]);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_data') {
    // Recebe os dados da página e os salva no arquivo
    $input = file_get_contents('php://input');
    $newData = json_decode($input, true);

    if ($newData) {
        // Salva o JSON completo como uma única linha no CSV
        $data_to_save = [[json_encode($newData)]]; // Envolve em um array de array para fputcsv
        if (write_csv($filename, $data_to_save)) {
            echo json_encode(['success' => true, 'message' => 'Dados salvos com sucesso!']);
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(['success' => false, 'message' => 'Erro ao escrever no arquivo CSV. Verifique as permissões.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dados inválidos recebidos.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método ou ação não permitida.']);
}
?>
