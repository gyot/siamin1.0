<?php

namespace App\Http\Controllers\Api;

use App\Models\PushToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PushNotificationController extends BaseApiController
{
    protected $modelClass = PushToken::class;

    public function registerToken(Request $request)
    {
        $request->validate([
            'id_pegawai' => 'nullable|exists:pegawai,id_pegawai',
            'id_akun_peserta' => 'nullable|exists:akun_peserta,id_akun_peserta',
            'token' => 'required|string|max:500',
            'device_type' => 'nullable|in:android,ios,web',
        ]);

        $pushToken = PushToken::updateOrCreate(
            ['token' => $request->token],
            [
                'id_pegawai' => $request->id_pegawai,
                'id_akun_peserta' => $request->id_akun_peserta,
                'device_type' => $request->device_type ?? 'android',
                'is_active' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Token berhasil didaftarkan.',
            'data' => $pushToken,
        ]);
    }

    public function removeToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string|max:500',
        ]);

        PushToken::where('token', $request->token)->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Token berhasil dihapus.',
        ]);
    }

    /**
     * Kirim notifikasi ke semua device token milik user tertentu.
     */
    public static function sendToUser($idPegawai, $title, $body, $data = [])
    {
        if (!$idPegawai) return false;

        $tokens = PushToken::where('id_pegawai', $idPegawai)
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();

        return self::sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Kirim notifikasi FCM HTTP v1 API ke daftar token.
     */
    public static function sendToTokens(array $tokens, $title, $body, $data = [])
    {
        if (empty($tokens)) return false;

        $serviceAccountPath = config('services.fcm.service_account');

        if (!$serviceAccountPath || !file_exists($serviceAccountPath)) {
            Log::warning('[PushNotification] Service account file tidak ditemukan.');
            return false;
        }

        try {
            $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);

            // 1. Buat JWT
            $jwt = self::createJWT($serviceAccount);

            // 2. Tukar JWT dengan access token
            $accessToken = self::getAccessToken($jwt);

            if (!$accessToken) {
                Log::error('[PushNotification] Gagal mendapatkan access token.');
                return false;
            }

            // 3. Kirim ke semua token
            $success = 0;
            foreach ($tokens as $token) {
                $result = self::sendToToken($accessToken, $token, $title, $body, $data, $serviceAccount['project_id']);
                if ($result) $success++;
            }

            Log::info("[PushNotification] Terkirim: {$success}/" . count($tokens));
            return $success > 0;
        } catch (\Exception $e) {
            Log::error('[PushNotification] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Buat JWT untuk autentikasi Google.
     */
    private static function createJWT($serviceAccount)
    {
        $now = time();
        $header = self::base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = self::base64UrlEncode(json_encode([
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signatureInput = $header . '.' . $payload;
        openssl_sign($signatureInput, $signature, $serviceAccount['private_key'], 'sha256WithRSAEncryption');
        $signature = self::base64UrlEncode($signature);

        return $signatureInput . '.' . $signature;
    }

    /**
     * Tukar JWT dengan access token Google.
     */
    private static function getAccessToken($jwt)
    {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }

    /**
     * Kirim notifikasi ke satu FCM token via HTTP v1 API.
     */
    private static function sendToToken($accessToken, $token, $title, $body, $data, $projectId)
    {
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => array_merge(
                    array_map('strval', $data),
                    ['click_action' => 'FLUTTER_NOTIFICATION_CLICK']
                ),
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                        'channel_id' => 'simaik_notifications',
                    ],
                ],
            ],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            Log::error("[PushNotification] FCM error {$httpCode}: {$response}");
            return false;
        }

        return true;
    }

    private static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
