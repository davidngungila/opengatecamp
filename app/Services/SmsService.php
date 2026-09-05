<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private string $baseUrl = 'https://messaging-service.co.tz';
    private string $token;
    private string $senderId;

    public function __construct(?string $token = null, ?string $senderId = null)
    {
        $this->token    = $token    ?? Setting::get('sms.api_token', '');
        $this->senderId = $senderId ?? Setting::get('sms.sender_id', 'TMCS MoCU');
    }

    public function isConfigured(): bool
    {
        return $this->token !== '';
    }

    public function send(string $phone, string $message): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'status' => 'NOT_CONFIGURED', 'api_message_id' => null, 'raw' => []];
        }

        $to = $this->formatPhone($phone);

        $payload = [
            'from'    => $this->senderId,
            'to'      => $to,
            'text'    => $message,
            'smsCount' => 1,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])->timeout(15)->post($this->baseUrl.'/api/sms/v2/text/single', $payload);

            $body = $response->json();

            if ($response->successful() && isset($body['messages'][0])) {
                $msg = $body['messages'][0];

                return [
                    'success'       => in_array($msg['status']['id'] ?? 0, [50, 51, 52, 73, 88]),
                    'status'        => $msg['status']['name'] ?? 'UNKNOWN',
                    'api_message_id' => $msg['messageId'] ?? null,
                    'raw'           => $body,
                ];
            }

            return ['success' => false, 'status' => 'API_ERROR_'.$response->status(), 'api_message_id' => null, 'raw' => $body];
        } catch (\Exception $e) {
            Log::error('SMS send failed', ['error' => $e->getMessage()]);

            return ['success' => false, 'status' => 'EXCEPTION', 'api_message_id' => null, 'raw' => ['error' => $e->getMessage()]];
        }
    }

    /**
     * Send bulk SMS to multiple recipients using the multi endpoint.
     *
     * @param  array   $phones  Array of phone numbers (local format)
     * @param  string  $message Text body
     * @return array   ['success_count' => int, 'fail_count' => int, 'results' => array]
     */
    public function sendBulk(array $phones, string $message): array
    {
        if (! $this->isConfigured()) {
            return ['success_count' => 0, 'fail_count' => count($phones), 'results' => []];
        }

        $messages = array_map(fn ($phone) => [
            'from'  => $this->senderId,
            'to'    => $this->formatPhone($phone),
            'text'  => $message,
            'smsCount' => 1,
        ], $phones);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])->timeout(30)->post($this->baseUrl.'/api/sms/v2/text/multi', [
                'messages' => $messages,
            ]);

            $body = $response->json();
            $results = [];
            $successCount = 0;
            $failCount = 0;

            if ($response->successful() && isset($body['messages'])) {
                foreach ($body['messages'] as $msg) {
                    $ok = in_array($msg['status']['id'] ?? 0, [50, 51, 52, 73, 88]);
                    $results[] = [
                        'to'        => $msg['to'] ?? '?',
                        'success'   => $ok,
                        'status'    => $msg['status']['name'] ?? 'UNKNOWN',
                        'messageId' => $msg['messageId'] ?? null,
                    ];
                    $ok ? $successCount++ : $failCount++;
                }
            } else {
                $failCount = count($phones);
                $results[] = ['to' => '*', 'success' => false, 'status' => 'API_ERROR_'.$response->status(), 'messageId' => null];
            }

            return ['success_count' => $successCount, 'fail_count' => $failCount, 'results' => $results];
        } catch (\Exception $e) {
            Log::error('Bulk SMS failed', ['error' => $e->getMessage()]);

            return [
                'success_count' => 0,
                'fail_count'    => count($phones),
                'results'       => [['to' => '*', 'success' => false, 'status' => 'EXCEPTION', 'messageId' => null]],
            ];
        }
    }

    /**
     * Query the provider's report endpoint for a delivered message.
     *
     * @param  string  $messageId  The provider message ID returned on send
     * @return array   ['success' => bool, 'status' => 'delivered'|'undelivered'|'pending'|'unknown'|..., 'raw' => array]
     */
    public function getDelivery(string $messageId): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'status' => 'NOT_CONFIGURED', 'raw' => []];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])->timeout(15)->get($this->baseUrl.'/api/v2/reports', ['messageId' => $messageId]);

            $body = $response->json() ?: [];

            if (! $response->successful()) {
                return ['success' => false, 'status' => 'API_ERROR_'.$response->status(), 'raw' => $body];
            }

            return [
                'success' => true,
                'status'  => $this->parseDeliveryStatus($body),
                'raw'     => $body,
            ];
        } catch (\Exception $e) {
            Log::error('SMS delivery check failed', ['error' => $e->getMessage()]);

            return ['success' => false, 'status' => 'EXCEPTION', 'raw' => ['error' => $e->getMessage()]];
        }
    }

    private function parseDeliveryStatus(array $body): string
    {
        $report = data_get($body, 'reports.0', $body);
        $statusName = strtoupper((string) (data_get($report, 'status.name') ?? data_get($body, 'status.name', '')));
        $statusId = data_get($report, 'status.id') ?? data_get($body, 'status.id');

        $deliveredCount = (int) (data_get($report, 'deliveredCount') ?? data_get($body, 'deliveredCount', 0));
        $notDeliveredCount = (int) (data_get($report, 'notDeliveredCount') ?? data_get($body, 'notDeliveredCount', 0));

        if ($deliveredCount > 0 && $notDeliveredCount === 0) {
            return 'delivered';
        }
        if ($deliveredCount === 0 && $notDeliveredCount > 0) {
            return 'undelivered';
        }

        if ($statusName !== '') {
            return match ($statusName) {
                'DELIVRD', 'DELIVERED', 'SUCCESS', 'COMPLETED' => 'delivered',
                'UNDELIV', 'UNDELIVERED', 'EXPIRED', 'REJECTD', 'FAILED', 'UNKNOWN' => 'undelivered',
                'ACCEPTD', 'ACCEPT', 'ENROUTE', 'SENTTODLR', 'PENDING', 'SENT' => 'pending',
                default => 'unknown',
            };
        }

        if ($statusId !== null) {
            return match ((int) $statusId) {
                88 => 'delivered',
                50, 51, 52, 73, 74 => 'pending',
                default => 'unknown',
            };
        }

        return 'unknown';
    }

    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-]/', '', $phone);

        if (str_starts_with($phone, '+255')) {
            return substr($phone, 1);
        }
        if (str_starts_with($phone, '255')) {
            return $phone;
        }
        if (str_starts_with($phone, '0')) {
            return '255'.substr($phone, 1);
        }

        return $phone;
    }
}
