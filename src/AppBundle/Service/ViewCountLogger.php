<?php

namespace AppBundle\Service;

use Symfony\Component\HttpFoundation\Request;

class ViewCountLogger
{
    private $logDir;

    public function __construct($logDir)
    {
        $this->logDir = rtrim($logDir, '/\\');
    }

    public function logView($postId, Request $request)
    {
        $userAgent = $request->headers->get('User-Agent', '');
        
        if ($this->isBot($userAgent)) {
            return;
        }

        $logFile = $this->logDir . '/view_counts.log';
        // Append postId to log with newline, using exclusive lock to prevent truncation from concurrent requests
        file_put_contents($logFile, $postId . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function isBot($userAgent)
    {
        $bots = [
            'googlebot',
            'bingbot',
            'yandexbot',
            'baiduspider',
            'slurp',
            'duckduckbot',
            'facebookexternalhit',
            'twitterbot',
            'linkedinbot',
            'whatsapp',
            'telegrambot',
            'zalobot',
            'semrushbot',
            'ahrefsbot',
            'mj12bot',
            'dotbot',
            'rogerbot',
            'screaming frog',
            'bytespider',
            'petalbot',
            'crawl',
            'spider',
            'bot/',
            'bot;',
        ];

        $userAgent = strtolower($userAgent);
        foreach ($bots as $bot) {
            if (strpos($userAgent, $bot) !== false) {
                return true;
            }
        }

        return empty($userAgent);
    }
}
