<?php

require_once './includes/functions.php';

use PHPUnit\Framework\TestCase;

class UnitTests extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_SERVER = [];
    }

    // Test redirect function
    public function testRedirect()
    {
        ob_start();

        try {
            redirect('/test-path');
        } catch (\Exception $e) {
            // Suppress exception if headers are already sent
        }

        $headers = headers_list();
        ob_end_clean();

        $this->assertContains('Location: /test-path', $headers);
    }

    // Test redirectToReferer function
    public function testRedirectToReferer()
    {
        $_SERVER['HTTP_REFERER'] = '/previous-page';

        ob_start();

        try {
            redirectToReferer();
        } catch (\Exception $e) {
            // Suppress exception if headers are already sent
        }

        $headers = headers_list();
        ob_end_clean();

        $this->assertContains('Location: /previous-page', $headers);
    }

    // Test flash function - Setting and retrieving flash messages
    public function testFlashSetAndGet()
    {
        flash('success', 'Operation successful');
        $this->assertEquals('Operation successful', flash('success'));

        // Ensure the message is removed after retrieval
        $this->assertNull(flash('success'));
    }

    // Test generateSecret function - Generate a 32-character secret
    public function testGenerateSecret()
    {
        $secret = generateSecret(32);
        $this->assertEquals(32, strlen($secret));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    // Test getQRCodeUrl function - Generates a valid QR code URL
    public function testGetQRCodeUrl()
    {
        $label = 'test@example.com';
        $secret = 'JBSWY3DPEHPK3PXP';

        $url = getQRCodeUrl($label, $secret);

        $this->assertStringContainsString('otpauth://totp/Enterprise%20Pro:test%40example.com', $url);
        $this->assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $url);
        $this->assertStringContainsString('issuer=Enterprise%20Pro', $url);
    }

    // Test verifyCode function - Valid code should return true
    public function testVerifyCodeSuccess()
    {
        $secret = 'JBSWY3DPEHPK3PXP';

        // Generate a valid code using the same logic as the function
        $code = $this->generateTOTP($secret);

        $this->assertTrue(verifyCode($secret, $code));
    }

    // Test verifyCode function - Invalid code should return false
    public function testVerifyCodeFail()
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $invalidCode = '123456';

        $this->assertFalse(verifyCode($secret, $invalidCode));
    }

    // Test base32_decode function - Decode a valid base32-encoded string
    public function testBase32Decode()
    {
        $input = 'JBSWY3DPEHPK3PXP';
        $expectedOutput = "\x48\x65\x6C\x6C\x6F\x21\x21"; // Binary representation of "Hello!!"

        $this->assertEquals($expectedOutput, base32_decode($input));
    }

    // Test base32_decode function - Invalid characters should return false
    public function testBase32DecodeInvalidChars()
    {
        $this->assertFalse(base32_decode('JBSWY3DPEHPK3P!'));
    }

    // Utility function to generate a valid TOTP code for testing
    private function generateTOTP($secret)
    {
        $key = base32_decode($secret);
        $time = floor(time() / 30); // 30-second window

        // Generate hash using HMAC-SHA1 based on the time window
        $timeBytes = pack('N*', 0) . pack('N*', $time);
        $hash = hash_hmac('sha1', $timeBytes, $key, true);

        // Extract dynamic offset from the hash
        $offset = ord($hash[19]) & 0xF;

        // Convert the hash to an integer
        $binary = ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF);

        // Get the last 6 digits of the binary number as the OTP
        $otp = $binary % 1000000;

        // Return the OTP as a zero-padded 6-digit string
        return str_pad($otp, 6, '0', STR_PAD_LEFT);
    }
}
