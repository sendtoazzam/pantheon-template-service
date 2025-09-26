<?php

namespace App\Services;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class TwoFactorService
{
    protected $google2fa;

    public function __construct(Google2FA $google2fa)
    {
        $this->google2fa = $google2fa;
    }

    /**
     * Setup 2FA for a user
     */
    public function setup(User $user)
    {
        $secret = $this->google2fa->generateSecretKey();
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        // Generate backup codes
        $backupCodes = $this->generateBackupCodes();

        // Store temporarily in cache for verification
        Cache::put("2fa_setup:{$user->id}", [
            'secret' => $secret,
            'backup_codes' => $backupCodes
        ], 600); // 10 minutes

        return [
            'qr_code' => $qrCodeUrl,
            'secret' => $secret,
            'backup_codes' => $backupCodes
        ];
    }

    /**
     * Verify and enable 2FA
     */
    public function verifyAndEnable(User $user, string $code)
    {
        $setupData = Cache::get("2fa_setup:{$user->id}");
        
        if (!$setupData) {
            return ['success' => false, 'message' => '2FA setup session expired'];
        }

        $isValid = $this->google2fa->verifyKey($setupData['secret'], $code);

        if (!$isValid) {
            return ['success' => false, 'message' => 'Invalid verification code'];
        }

        // Enable 2FA
        $user->update([
            'two_factor_secret' => encrypt($setupData['secret']),
            'two_factor_enabled' => true,
            'two_factor_backup_codes' => encrypt(json_encode($setupData['backup_codes']))
        ]);

        // Clear setup data
        Cache::forget("2fa_setup:{$user->id}");

        return ['success' => true, 'message' => '2FA enabled successfully'];
    }

    /**
     * Verify 2FA code
     */
    public function verifyCode(User $user, string $code)
    {
        if (!$user->two_factor_enabled) {
            return false;
        }

        $secret = decrypt($user->two_factor_secret);

        // Try TOTP code first
        if ($this->google2fa->verifyKey($secret, $code)) {
            return true;
        }

        // Try backup codes
        $backupCodes = json_decode(decrypt($user->two_factor_backup_codes), true);
        $key = array_search($code, $backupCodes);

        if ($key !== false) {
            // Remove used backup code
            unset($backupCodes[$key]);
            $user->update([
                'two_factor_backup_codes' => encrypt(json_encode(array_values($backupCodes)))
            ]);
            return true;
        }

        return false;
    }

    /**
     * Disable 2FA
     */
    public function disable(User $user, string $code)
    {
        if (!$user->two_factor_enabled) {
            return ['success' => false, 'message' => '2FA is not enabled'];
        }

        // Verify code before disabling
        if (!$this->verifyCode($user, $code)) {
            return ['success' => false, 'message' => 'Invalid verification code'];
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_backup_codes' => null
        ]);

        return ['success' => true, 'message' => '2FA disabled successfully'];
    }

    /**
     * Generate backup codes
     */
    private function generateBackupCodes($count = 8)
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(md5(uniqid()), 0, 8));
        }
        return $codes;
    }

    /**
     * Regenerate backup codes
     */
    public function regenerateBackupCodes(User $user)
    {
        if (!$user->two_factor_enabled) {
            return ['success' => false, 'message' => '2FA is not enabled'];
        }

        $backupCodes = $this->generateBackupCodes();
        
        $user->update([
            'two_factor_backup_codes' => encrypt(json_encode($backupCodes))
        ]);

        return ['success' => true, 'backup_codes' => $backupCodes];
    }
}
