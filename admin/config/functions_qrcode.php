<?php
require_once '../config/auth.php';
require_once __DIR__ . '/../../../../vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Common\EccLevel;

// Verificar se o usuário está logado
verificaLogin();

// Validar e processar os dados do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar entradas
    $pixKey = trim($_POST['pixKey'] ?? '');
    $amount = trim($_POST['amount'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    
    // Validação básica
    if (empty($pixKey)) {
        $errorMessage = 'A chave PIX é obrigatória.';
        return;
    }
    
    if (empty($amount)) {
        $errorMessage = 'O valor é obrigatório.';
        return;
    }
    
    // Converter o valor para o formato correto (ponto como separador decimal)
    $amount = str_replace(',', '.', $amount);
    
    // Validar se o valor é um número válido
    if (!is_numeric($amount) || $amount <= 0) {
        $errorMessage = 'O valor deve ser um número positivo.';
        return;
    }
    
    // Formatar o valor com 2 casas decimais
    $amount = number_format((float)$amount, 2, '.', '');
    
    try {
        // Gerar o payload PIX
        $pixCode = generatePixPayload($pixKey, $amount, $name, $city, $description);
        
        // Gerar o QR Code
        $qrCodeImage = generateQRCode($pixCode);
        
        $successMessage = 'QR Code PIX gerado com sucesso!';
    } catch (Exception $e) {
        $errorMessage = 'Erro ao gerar o QR Code: ' . $e->getMessage();
    }
}

/**
 * Gera o payload para o PIX seguindo as especificações do Banco Central
 */
function generatePixPayload($pixKey, $amount, $name = '', $city = '', $description = '') {
    // ID do Payload Format Indicator, obrigatório
    $payload = '000201';
    
    // ID do Point of Initiation Method, se fixo 11, se variável 12
    $payload .= '010212';
    
    // Merchant Account Information para PIX
    $payload .= '26';
    
    // Indica que é uma conta PIX
    $merchantAccountInfo = '0014BR.GOV.BCB.PIX';
    
    // Adiciona a chave PIX
    $merchantAccountInfo .= '01' . strlen($pixKey) . $pixKey;
    
    // Descrição (opcional)
    if (!empty($description)) {
        $merchantAccountInfo .= '02' . strlen($description) . $description;
    }
    
    // Adiciona o tamanho e o valor da informação da conta
    $payload .= strlen($merchantAccountInfo) . $merchantAccountInfo;
    
    // Merchant Category Code
    $payload .= '52040000';
    
    // Transaction Currency - 986 para BRL
    $payload .= '5303986';
    
    // Transaction Amount
    $payload .= '54' . sprintf('%02d', strlen($amount)) . $amount;
    
    // Country Code - BR
    $payload .= '5802BR';
    
    // Merchant Name
	$merchantName = '5913Beneficiario';        // valor padrão

	if (!empty($name)) {
		$merchantName = '59' . sprintf('%02d', strlen($name)) . $name;
	}

	$payload .= $merchantName;
		
    // Merchant City
	$merchantCity = '6008BRASILIA';

	if (!empty($city)) {
		$merchantCity = '60' . sprintf('%02d', strlen($city)) . $city;
	}

	$payload .= $merchantCity;
    
    // Additional Data Field
    $payload .= '62070503***';
    
    // CRC16 (checksum) - adicionamos '6304' por enquanto como placeholder
    $payload .= '6304';
    
    // Calcula o CRC16 e adiciona ao final
    $crc = calculateCRC16($payload);
    $payload = substr($payload, 0, -4) . sprintf('%04X', $crc);
    
    return $payload;
}

/**
 * Função para calcular o CRC16 conforme a especificação do PIX
 */
function calculateCRC16($str) {
    // CRC16 CCITT-FALSE
    $crc = 0xFFFF;
    $strlen = strlen($str);
    
    for ($c = 0; $c < $strlen; $c++) {
        $crc ^= ord(substr($str, $c, 1)) << 8;
        
        for ($i = 0; $i < 8; $i++) {
            $crc = $crc << 1;
			
			if ($crc & 0x8000) {
                $crc = ($crc << 1) ^ 0x1021;
            } 
        }
    }
    
    return $crc & 0xFFFF;
}

/**
 * Gera o QR Code usando a biblioteca PHP QR Code
 */
function generateQRCode($data) {
    // Se você estiver usando a biblioteca chillerlan/php-qrcode
    if (class_exists('chillerlan\QRCode\QRCode')) {
        
		$arquivo = 'arquivo_' . date('Ymd_His') . '.txt';;
		$conteudo = 'tem classe chillerlan\php-qrcode\QRCode';
		file_put_contents($arquivo, $conteudo); 			
		
		$options = new QROptions([
			'version'      => 5,
			'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
			'eccLevel'     => QRCode::ECC_L,
			'scale'        => 10,
			'imageBase64'  => true,
		]);

		$qrcode = new QRCode($options);
		return $qrcode->render($data);
    }
    
    // Alternativa usando a biblioteca PHP QR Code (se a chillerlan não estiver disponível)
    if (class_exists('chillerlan\QRCode\QRCode')) {

		// Caminho para a biblioteca PHP QR Code
        require_once 'lib/phpqrcode/qrlib.php';
        
        // Cria um arquivo temporário para o QR Code
        $tempDir = sys_get_temp_dir();
        $fileName = tempnam($tempDir, 'qrcode_');
        $fileName .= '.png';
        
        // Gera o QR Code
		$qrcode = new \QRcode();
        $qrcode->png($data, $fileName, 'L', 10, 2);
        
        // Converte para base64
        $imageData = file_get_contents($fileName);
        $base64 = 'data:image/png;base64,' . base64_encode($imageData);
        
        // Remove o arquivo temporário
        unlink($fileName);
        
        return $base64;
    }
    
    throw new Exception('Nenhuma biblioteca de QR Code encontrada.');
}
?>
