<?php

require_once '../config/auth.php';
require_once __DIR__ . '/../../../../vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Common\EccLevel;
use Exception;

verificaLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pixKey      = trim($_POST['pixKey']      ?? '');
    $amount      = trim($_POST['amount']      ?? '');
    $description = trim($_POST['description'] ?? '');
    $name        = trim($_POST['name']        ?? '');
    $city        = trim($_POST['city']        ?? '');

    if (empty($pixKey)) {
        $errorMessage = 'A chave PIX é obrigatória.';
        return;
    }

    if (empty($amount)) {
        $errorMessage = 'O valor é obrigatório.';
        return;
    }

    $amount = str_replace(',', '.', $amount);

    if (!is_numeric($amount) || $amount <= 0) {
        $errorMessage = 'O valor deve ser um número positivo.';
        return;
    }

    $amount = number_format((float)$amount, 2, '.', '');

    try {
        $pixCode     = generatePixPayload($pixKey, $amount, $name, $city, $description);
        $qrCodeImage = generateQRCode($pixCode);
        $successMessage = 'QR Code PIX gerado com sucesso!';
    } catch (Exception $e) {
        $errorMessage = 'Erro ao gerar o QR Code: ' . $e->getMessage();
    }
}

function generatePixPayload($pixKey, $amount, $name = '', $city = '', $description = '')
{
    $payload = '000201';
    $payload .= '010212';
    $payload .= '26';

    $merchantAccountInfo  = '0014BR.GOV.BCB.PIX';
    $merchantAccountInfo .= '01' . sprintf('%02d', strlen($pixKey)) . $pixKey;

    if (!empty($description)) {
        $merchantAccountInfo .= '02' . sprintf('%02d', strlen($description)) . $description;
    }

    $payload .= sprintf('%02d', strlen($merchantAccountInfo)) . $merchantAccountInfo;
    $payload .= '52040000';
    $payload .= '5303986';
    $payload .= '54' . sprintf('%02d', strlen($amount)) . $amount;
    $payload .= '5802BR';

    $merchantName = '5913Beneficiario';
    if (!empty($name)) {
        $merchantName = '59' . sprintf('%02d', strlen($name)) . $name;
    }
    $payload .= $merchantName;

    $merchantCity = '6008BRASILIA';
    if (!empty($city)) {
        $merchantCity = '60' . sprintf('%02d', strlen($city)) . $city;
    }
    $payload .= $merchantCity;

    $payload .= '62070503***';
    $payload .= '6304';

    $crc = calculateCRC16($payload);
    $payload = substr($payload, 0, -4) . sprintf('%04X', $crc);

    return $payload;
}

function calculateCRC16($str)
{
    $crc    = 0xFFFF;
    $strlen = strlen($str);

    for ($c = 0; $c < $strlen; $c++) {
        $crc ^= ord(substr($str, $c, 1)) << 8;

        for ($i = 0; $i < 8; $i++) {
            $crc = ($crc & 0x8000)
                ? ($crc << 1) ^ 0x1021
                : $crc << 1;
        }
    }

    return $crc & 0xFFFF;
}

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
function generateQRCode(string $data): string
{
    $options = new QROptions([
        'version'     => 5,
        'outputType'  => QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel'    => EccLevel::L,
        'scale'       => 10,
        'imageBase64' => true,
    ]);

    $qrcode = new QRCode($options);
    return $qrcode->render($data);
}
