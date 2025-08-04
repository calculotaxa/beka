<?php
header('Content-Type: application/json');

$filename = 'dados_pdv_unificados.csv';

// Função para ler o arquivo CSV
function read_csv($filename) {
    $data = [];
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
    if (($handle = fopen($filename, "w")) !== FALSE) {
        foreach ($data as $row) {
            fputcsv($handle, $row, ";");
        }
        fclose($handle);
    }
}

// Lida com as requisições
$action = $_GET['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_data') {
    // Retorna os dados para a página
    $data = read_csv($filename);
    echo json_encode($data);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_data') {
    // Recebe os dados da página e os salva no arquivo
    $input = file_get_contents('php://input');
    $newData = json_decode($input, true);

    if ($newData) {
        write_csv($filename, $newData);
        echo json_encode(['success' => true, 'message' => 'Dados salvos com sucesso!']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dados inválidos recebidos.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método ou ação não permitida.']);
}
?>