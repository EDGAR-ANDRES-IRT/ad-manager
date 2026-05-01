<?php
/**
 * Cliente HTTP para consumir la API Flask.
 * Reemplaza completamente a winrm.php — el PHP ya NO toca WinRM.
 */
class ApiClient {

    private string $base;
    private int    $timeout;

    public function __construct() {
        $this->base    = rtrim(API_BASE, '/');
        $this->timeout = 30;
    }

    // ── Métodos HTTP ─────────────────────────────────────────

    public function get(string $path, array $params = []): array {
        $url = $this->base . $path;
        if ($params) {
            $url .= '?' . http_build_query($params);
        }
        return $this->request('GET', $url);
    }

    public function post(string $path, array $body = []): array {
        return $this->request('POST', $this->base . $path, $body);
    }

    public function put(string $path, array $body = []): array {
        return $this->request('PUT', $this->base . $path, $body);
    }

    public function delete(string $path, array $body = []): array {
        return $this->request('DELETE', $this->base . $path, $body);
    }

    // ── Core ─────────────────────────────────────────────────

    private function request(string $method, string $url, array $body = []): array {
        $ch = curl_init($url);
        $headers = ['Content-Type: application/json', 'Accept: application/json'];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => $method,
        ]);

        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return [
                'status'  => 'error',
                'message' => "No se pudo conectar a la API Flask: $curlErr",
                'data'    => [],
            ];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [
                'status'  => 'error',
                'message' => "Respuesta inválida de la API (HTTP $httpCode)",
                'data'    => [],
            ];
        }

        return $decoded;
    }

    // ── Helpers de conveniencia ───────────────────────────────

    /** Retorna ['success'=>bool, 'data'=>array, 'error'=>string] */
    public function getData(string $path, array $params = []): array {
        $r = $this->get($path, $params);
        return [
            'success' => ($r['status'] ?? '') === 'success',
            'data'    => $r['data'] ?? [],
            'error'   => $r['message'] ?? '',
        ];
    }

    public function sendAction(string $method, string $path, array $body = []): array {
        $r = match(strtoupper($method)) {
            'POST'   => $this->post($path, $body),
            'PUT'    => $this->put($path, $body),
            'DELETE' => $this->delete($path, $body),
            default  => $this->get($path),
        };
        return [
            'success' => ($r['status'] ?? '') === 'success',
            'message' => $r['data']['message'] ?? $r['message'] ?? '',
            'error'   => ($r['status'] ?? '') !== 'success'
                         ? ($r['message'] ?? 'Error desconocido')
                         : '',
        ];
    }
}
