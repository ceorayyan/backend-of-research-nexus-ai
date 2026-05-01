<?php

namespace App\Utils;

class PasswordGenerator
{
    /**
     * Generate a random secure password
     * 
     * @param int $length Password length (default: 12)
     * @return string Generated password
     */
    public static function generate(int $length = 12): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $allChars = $uppercase . $lowercase . $numbers . $special;
        $password = '';
        
        // Ensure at least one character from each category
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        // Fill the rest randomly
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Shuffle the password
        $password = str_shuffle($password);
        
        return $password;
    }
}
