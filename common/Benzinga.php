<?php
/**
 * Benzinga Corporate Guidance API Client (minimal)
 */

require_once __DIR__ . '/../config.php';

class BenzingaClient {
    private string $apiKey;
    private string $baseUrl;

    public function __construct(?string $apiKey = null, ?string $baseUrl = null) {
        $this->apiKey = $apiKey ?? (defined('BENZINGA_API_KEY') ? (string)BENZINGA_API_KEY : '');
        $this->baseUrl = rtrim($baseUrl ?? (defined('BENZINGA_BASE_URL') ? (string)BENZINGA_BASE_URL : 'https://api.benzinga.com/api/v2.1'), '/');
    }

    public function hasApiKey(): bool {
        return !empty($this->apiKey);
    }

    /**
     * Fetch Corporate Guidance
     * @param string $from YYYY-MM-DD
     * @param string $to YYYY-MM-DD
     * @param int $page
     * @param int $pageSize
     * @return array{data?:array,success:bool,error?:string}
     */
    public function getGuidance(string $from, string $to, int $page = 1, int $pageSize = 50): array {
        $url = $this->baseUrl . '/calendar/guidance?'
             . http_build_query([
                 'token' => $this->apiKey,
                 'date_from' => $from,
                 'date_to' => $to,
                 'page' => $page,
                 'pagesize' => $pageSize
             ]);

        $ctx = stream_context_create(['http' => [
            'method' => 'GET',
            'timeout' => 20,
            'header' => [
                'User-Agent: EarningsTable/1.0',
                'Accept: application/json'
            ]
        ]]);

        $json = @file_get_contents($url, false, $ctx);
        if ($json === false) {
            return ['success' => false, 'error' => 'Request failed'];
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return ['success' => false, 'error' => 'Invalid JSON'];
        }
        return ['success' => true, 'data' => $data];
    }
}
?>



