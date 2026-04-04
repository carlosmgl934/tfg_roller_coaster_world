<?php

class FirebaseAuthAdmin
{
    private string $keyFilePath;

    public function __construct(string $keyFilePath = __DIR__ . '/../../config/auth-firebase-adminsdk.json')
    {
        $this->keyFilePath = $keyFilePath;
    }
    private function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    /**
     * Genera un token de acceso de Google (OAuth2) usando la cuenta de servicio (Service Account)
     */
    private function getAccessToken(array $keyData): ?string
    {
        $header = json_encode([
            "alg" => "RS256",
            "typ" => "JWT"
        ]);

        $now = time();
        $payload = json_encode([
            "iss" => $keyData['client_email'],
            "scope" => "https://www.googleapis.com/auth/identitytoolkit",
            "aud" => "https://oauth2.googleapis.com/token",
            "exp" => $now + 3600,
            "iat" => $now
        ]);

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode($payload);

        $signatureInput = $base64UrlHeader . "." . $base64UrlPayload;

        $signature = '';
        if (!openssl_sign($signatureInput, $signature, $keyData['private_key'], OPENSSL_ALGO_SHA256)) {
            error_log("FirebaseAuthAdmin: Error al firmar el JWT con openssl.");
            return null;
        }

        $jwt = $signatureInput . "." . $this->base64UrlEncode($signature);

        // Pedir el token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            return $data['access_token'] ?? null;
        }

        error_log("FirebaseAuthAdmin: Error al obtener access token de Google. HTTP $httpCode - $response");
        return null;
    }

    /**
     * Borra un usuario de Firebase Authentication usando su firebase_uid
     */
    public function deleteUser(string $uid): array
    {
        if (!file_exists($this->keyFilePath)) {
            return ['success' => false, 'error' => 'No se encuentra el archivo JSON de la Service Account.'];
        }

        $keyData = json_decode(file_get_contents($this->keyFilePath), true);
        if (!$keyData || !isset($keyData['private_key'])) {
            return ['success' => false, 'error' => 'Archivo de Service Account inválido.'];
        }

        $accessToken = $this->getAccessToken($keyData);
        if (!$accessToken) {
            return ['success' => false, 'error' => 'No se pudo obtener el token de acceso.'];
        }

        $projectId = $keyData['project_id'];
        $deleteUrl = "https://identitytoolkit.googleapis.com/v1/projects/{$projectId}/accounts:delete";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $deleteUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$accessToken}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'localId' => $uid
        ]));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode === 200) {
            return ['success' => true, 'error' => ''];
        }

        // Si fue un 400 y el error es USER_NOT_FOUND, no es un error real para nosotros (ya estaba borrado)
        $data = json_decode($response, true);
        $errorMsg = $data['error']['message'] ?? $response ?? 'Error desconocido';

        if ($httpCode === 400 && str_contains($errorMsg, 'USER_NOT_FOUND')) {
            return ['success' => true, 'error' => ''];
        }

        error_log("FirebaseAuthAdmin: Error borrando usuario $uid. HTTP $httpCode - $errorMsg");
        return ['success' => false, 'error' => $errorMsg];
    }
}
