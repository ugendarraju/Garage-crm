<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Generate a WhatsApp Click-to-Chat URL
     * 
     * @param string $phone The phone number with country code
     * @param string $message The text message to send
     * @return string
     */
    public static function generateLink(string $phone, string $message): string
    {
        // Clean phone number (remove spaces, +, etc)
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        
        $encodedMessage = urlencode($message);
        
        return "https://wa.me/{$cleanPhone}?text={$encodedMessage}";
    }

    /**
     * Get Login Credentials Link
     */
    public static function getLoginCredentialsLink(string $phone, string $name, string $email, string $password): string
    {
        $message = "Hello $name,\n\nYour account has been successfully created and your service request is approved!\n\nHere are your login details:\nEmail: $email\nPassword: $password\n\nPlease log in to check your vehicle's status and updates.\n\nThank you!";
        return self::generateLink($phone, $message);
    }
    
    /**
     * Get Service Request Approved Link (Existing User)
     */
    public static function getServiceRequestApprovedLink(string $phone, string $name, string $brand, string $model): string
    {
        $message = "Hello {$name}, your service request for your {$brand} {$model} has been APPROVED!";
        return self::generateLink($phone, $message);
    }

    /**
     * Get Password Reset Link
     */
    public static function getPasswordResetLink(string $phone, string $name, string $newPassword): string
    {
        $message = "Hello $name,\n\nYour password has been successfully reset by the administration.\n\nYour new password is: $newPassword\n\nPlease log in and you can change it in your Profile settings.\n\nThank you!";
        return self::generateLink($phone, $message);
    }

    /**
     * Get Job Card Update Link
     */
    public static function getJobCardUpdateLink(string $phone, string $name, string $jobNumber, string $status): string
    {
        $message = "Hello {$name},\n\nThe status of your Job Card ({$jobNumber}) has been updated to: ". strtoupper($status) .".";
        return self::generateLink($phone, $message);
    }
}
