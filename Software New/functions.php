<?php
function redirect($path)
{
    header('Location: ' . $path);
    die();
}

function redirectToReferer()
{
    redirect($_SERVER['HTTP_REFERER']);
}



function flash($name, $message = null)
{
    if (!$message) {
        $message = $_SESSION['flash'][$name] ?? null;
        unset($_SESSION['flash'][$name]);
        return $message;
    }

    $_SESSION['flash'][$name] = $message;
}


function generateSecret($length = 32)
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 character set
    $secret = '';
    for ($i = 0; $i < $length; $i++) {
        $secret .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $secret;
}

function getQRCodeUrl($label, $secret)
{
    $issuer = 'Enterprise Pro';
    return "https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=otpauth://totp/"
        . urlencode($issuer) . ':' . urlencode($label)
        . "?secret={$secret}&issuer=" . urlencode($issuer);
}

function verifyCode($secret, $code)
{
    $key = base32_decode($secret);
    $time = floor(time() / 30); // 30-second window

    // Generate HMAC hash based on the time window
    $timeBytes = pack('N*', 0) . pack('N*', $time);
    $hash = hash_hmac('sha1', $timeBytes, $key, true);

    // Extract the dynamic offset
    $offset = ord($hash[19]) & 0xF;
    $binary = ((ord($hash[$offset]) & 0x7F) << 24) |
        ((ord($hash[$offset + 1]) & 0xFF) << 16) |
        ((ord($hash[$offset + 2]) & 0xFF) << 8) |
        (ord($hash[$offset + 3]) & 0xFF);

    // Take the last 6 digits of the binary number
    $otp = $binary % 1000000;

    return str_pad($otp, 6, '0', STR_PAD_LEFT) === $code;
}

function base32_decode($input)
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $output = '';
    $buffer = 0;
    $bitsLeft = 0;

    foreach (str_split($input) as $char) {
        $buffer = ($buffer << 5) | strpos($alphabet, $char);
        $bitsLeft += 5;

        if ($bitsLeft >= 8) {
            $output .= chr(($buffer >> ($bitsLeft - 8)) & 0xFF);
            $bitsLeft -= 8;
        }
    }

    return $output;
}
