<?php
// Código de verificación por correo (compartido por clientes y empleados).
require_once __DIR__ . '/auth.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

const CODIGO_VIGENCIA_SEG = 600; // 10 minutos
const CODIGO_MAX_INTENTOS = 5;
const CODIGO_REENVIO_SEG  = 30;

/** Genera el código, lo envía por correo y lo guarda (hasheado) en sesión. */
function enviarCodigoVerificacion(string $correo): array {
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        return [false, "Ingresa un correo electrónico válido."];
    }

    $ultimo = $_SESSION['codigo_enviado_en'] ?? 0;
    if (time() - $ultimo < CODIGO_REENVIO_SEG) {
        $resta = CODIGO_REENVIO_SEG - (time() - $ultimo);
        return [false, "Espera $resta segundos para solicitar otro código."];
    }

    require_once __DIR__ . "/../assets/libs/PHPMailer/src/Exception.php";
    require_once __DIR__ . "/../assets/libs/PHPMailer/src/PHPMailer.php";
    require_once __DIR__ . "/../assets/libs/PHPMailer/src/SMTP.php";
    $cfg = require __DIR__ . "/correo.php";

    $codigo = (string) random_int(100000, 999999);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $cfg['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['username'];
        $mail->Password   = $cfg['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $cfg['port'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($cfg['from'], $cfg['fromName']);
        $mail->addAddress($correo);

        $mail->isHTML(true);
        $mail->Subject = 'Tu código de verificación - Barber-Box';
        $mail->Body    = "
            <div style='background-color:#121212;color:#ffffff;padding:25px;font-family:Arial,sans-serif;border-radius:8px;max-width:500px;margin:auto;'>
                <h2 style='color:#d90429;margin-top:0;'>Barber-Box</h2>
                <p>Hola,</p>
                <p>Ingresa el siguiente código en el formulario para validar tu correo:</p>
                <div style='background-color:#1e1e1e;border:1px solid #0077b6;padding:15px;text-align:center;font-size:28px;font-weight:bold;letter-spacing:5px;color:#ffffff;margin:20px 0;'>$codigo</div>
                <p style='color:#a0a0a0;font-size:12px;'>El código vence en 10 minutos. Si no lo solicitaste, ignora este mensaje.</p>
            </div>";
        $mail->AltBody = "Tu código de verificación de Barber-Box es: $codigo";
        $mail->send();
    } catch (MailException $e) {
        return [false, "No se pudo enviar el correo: " . $mail->ErrorInfo];
    }

    $_SESSION['codigo_verificacion'] = password_hash($codigo, PASSWORD_DEFAULT);
    $_SESSION['correo_verificacion'] = strtolower($correo);
    $_SESSION['codigo_expira']       = time() + CODIGO_VIGENCIA_SEG;
    $_SESSION['codigo_intentos']     = 0;
    $_SESSION['codigo_enviado_en']   = time();

    return [true, "Código enviado. Revisa tu bandeja de entrada."];
}

/** Devuelve null si el código es válido para ese correo; si no, el mensaje de error. */
function validarCodigoVerificacion(string $correo, string $codigoIngresado): ?string {
    if (!isset($_SESSION['codigo_verificacion'])) {
        return "Primero solicita el código de verificación.";
    }
    if (time() > ($_SESSION['codigo_expira'] ?? 0)) {
        return "El código expiró. Solicita uno nuevo.";
    }
    if (($_SESSION['codigo_intentos'] ?? 0) >= CODIGO_MAX_INTENTOS) {
        return "Demasiados intentos. Solicita un nuevo código.";
    }
    if (strtolower($correo) !== ($_SESSION['correo_verificacion'] ?? '')) {
        return "El correo no coincide con el que recibió el código.";
    }
    if (!password_verify(trim($codigoIngresado), $_SESSION['codigo_verificacion'])) {
        $_SESSION['codigo_intentos'] = ($_SESSION['codigo_intentos'] ?? 0) + 1;
        return "El código de verificación es incorrecto.";
    }
    return null;
}

function consumirCodigoVerificacion(): void {
    unset($_SESSION['codigo_verificacion'], $_SESSION['correo_verificacion'],
          $_SESSION['codigo_expira'], $_SESSION['codigo_intentos'], $_SESSION['codigo_enviado_en']);
}
