
<?php
session_start();
header('Content-Type: application/json');

// Importar las clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'libs/PHPMailer/Exception.php';
require 'libs/PHPMailer/PHPMailer.php';
require 'libs/PHPMailer/SMTP.php';

// Validar que llegue el correo desde JavaScript
$correoDestino = $_POST['email'] ?? '';

if (!filter_var($correoDestino, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Ingresa un correo electrónico válido.']);
    exit;
}

// 1. Generar código aleatorio de 6 dígitos y guardarlo en sesión
$codigo = rand(100000, 999999);
$_SESSION['codigo_verificacion'] = $codigo;
$_SESSION['correo_verificacion'] = $correoDestino;

$mail = new PHPMailer(true);

try {
    // 2. Configuración del servidor SMTP de Gmail
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'tu_correo_barberia@gmail.com'; // TU GMAIL
    $mail->Password   = 'xxxx xxxx xxxx xxxx';          // TU CONTRASEÑA DE APLICACIÓN (16 letras)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    // 3. Remitente y Destinatario
    $mail->setFrom('tu_correo_barberia@gmail.com', 'BarberBox');
    $mail->addAddress($correoDestino);

    // 4. Contenido del correo (Diseño HTML adaptado a tu barbería)
    $mail->isHTML(true);
    $mail->Subject = 'Tu código de verificación - BarberBox';
    $mail->Body    = "
        <div style='background-color: #121212; color: #ffffff; padding: 25px; font-family: Arial, sans-serif; border-radius: 8px; max-width: 500px; margin: auto;'>
            <h2 style='color: #d90429; margin-top: 0;'>BarberBox</h2>
            <p>Hola,</p>
            <p>Has solicitado un código para validar tu cuenta. Ingresa el siguiente código en el formulario:</p>
            <div style='background-color: #1e1e1e; border: 1px solid #0077b6; padding: 15px; text-align: center; font-size: 28px; font-weight: bold; letter-spacing: 5px; color: #ffffff; margin: 20px 0;'>
                {$codigo}
            </div>
            <p style='color: #a0a0a0; font-size: 12px;'>Si no solicitaste este código, puedes ignorar este mensaje.</p>
        </div>
    ";

    $mail->send();
    echo json_encode(['status' => 'success', 'mensaje' => 'Código enviado correctamente. Revisa tu bandeja.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Error al enviar el correo: ' . $mail->ErrorInfo]);
} 