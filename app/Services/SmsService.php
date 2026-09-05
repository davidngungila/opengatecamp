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
     * Query the provider's logs endpoint for a delivered message.
     *
     * @param  string                         $messageId  The provider message ID returned on send
     * @param  string|null                    $to         Destination phone (255…) used to narrow the search
     * @param  string|null                    $from       Sender ID used to narrow the search
     * @param  string|\DateTimeInterface|null $sentAt     Date/time the message was sent (for the sentSince/sentUntil range)
     * @return array       ['success' => bool, 'status' => 'delivered'|'undelivered'|'pending'|'unknown'|..., 'raw' => array]
     */
    public function getDelivery(string $messageId, ?string $to = null, ?string $from = null, null|string|\DateTimeInterface $sentAt = null): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'status' => 'NOT_CONFIGURED', 'raw' => []];
        }

        try {
            $params = [];
            if ($to) {
                $params['to'] = $this->formatPhone($to);
            }
            if ($from) {
                $params['from'] = $from;
            }
            if ($sentAt) {
                $date = $sentAt instanceof \DateTimeInterface
                    ? \Illuminate\Support\Carbon::parse($sentAt)
                    : \Illuminate\Support\Carbon::parse($sentAt);
                $params['sentSince'] = $date->copy()->subDay()->format('Y-m-d');
                $params['sentUntil'] = $date->copy()->addDay()->format('Y-m-d');
            }
            $params['limit'] = 100;

            $attempts = 0;
            do {
                $attempts++;
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$this->token,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ])->timeout(15)->get($this->baseUrl.'/api/v2/logs', $params);

                if (! $response->successful() && $response->status() === 429 && $attempts < 3) {
                    usleep(1500000 * $attempts);
                    continue;
                }

                break;
            } while (true);

            $body = $response->json() ?: [];

            if (! $response->successful()) {
                return ['success' => false, 'status' => 'API_ERROR_'.$response->status(), 'raw' => $body];
            }

            return [
                'success' => true,
                'status'  => $this->parseDeliveryStatus($body, $messageId),
                'raw'     => $body,
            ];
        } catch (\Exception $e) {
            Log::error('SMS delivery check failed', ['error' => $e->getMessage()]);

            return ['success' => false, 'status' => 'EXCEPTION', 'raw' => ['error' => $e->getMessage()]];
        }
    }

    private function parseDeliveryStatus(array $body, ?string $messageId = null): string
    {
        $logs = $body['results'] ?? $body['logs'] ?? $body['data'] ?? $body['messages'] ?? [];

        if (isset($logs[0]) && is_array($logs[0])) {
            $report = null;

            if ($messageId !== null) {
                foreach ($logs as $log) {
                    if (in_array($messageId, [(string) ($log['messageId'] ?? ''), (string) ($log['id'] ?? '')], true)) {
                        $report = $log;

                        break;
                    }
                }
            }

            $report = $report ?? $logs[0];
        } else {
            $report = $body;
        }

        return $this->statusFromLog($report);
    }

    private function statusFromLog(array $report): string
    {
        if ($this->applyDeliveryStatus($report['delivery'] ?? null)) {
            return $this->applyDeliveryStatus($report['delivery'] ?? null);
        }

        return $this->statusByObject($report);
    }

    private function applyDeliveryStatus(mixed $delivery): ?string
    {
        if (is_string($delivery) && trim($delivery) !== '') {
            return $this->statusByValue(strtoupper(trim($delivery)));
        }

        if (is_array($delivery)) {
            return $this->statusByObject($delivery);
        }

        return null;
    }

    private function statusByValue(string $status): ?string
    {
        if ($status === '') {
            return null;
        }

        return match (true) {
            in_array($status, ['DELIVRD', 'DELIVERED', 'SUCCESS', 'COMPLETED', 'DELIVERED_TO_HANDSET'], true) => 'delivered',
            in_array($status, ['UNDELIV', 'UNDELIVERED', 'EXPIRED', 'REJECTD', 'REJECTED', 'FAILED', 'NOT_DELIVERED', 'UNAVAILABLE', 'UNKNOWN'], true) => 'undelivered',
            in_array($status, ['ACCEPTD', 'ACCEPTED', 'ACCEPT', 'ENROUTE', 'ENROUTE (SENT)', 'SENTTODLR', 'SENT', 'PENDING', 'DELIVERED_TO_SMSC'], true) => 'pending',
            default => null,
        };
    }

    private function statusByObject(array $report): string
    {
        $statusName = strtoupper((string) data_get($report, 'status.name'));
        $statusGroup = strtoupper((string) data_get($report, 'status.groupName'));
        $statusId = data_get($report, 'status.id');

        $resolved = $this->statusByValue($statusName) ?? $this->statusByValue($statusGroup);

        if ($resolved !== null) {
            return $resolved;
        }

        if ($statusId !== null) {
            return match ((int) $statusId) {
                73, 88 => 'delivered',
                50, 51, 52, 74 => 'pending',
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
