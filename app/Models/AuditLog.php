<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class AuditLog extends Model
{
    protected $fillable = ['user_id', 'user_name', 'action', 'module', 'details', 'ip', 'country', 'region', 'is_read'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public static function unreadCount(): int
    {
        return static::where('is_read', false)->count();
    }

    public static function markAllRead(): int
    {
        return static::where('is_read', false)->update(['is_read' => true]);
    }

    public static function record(string $action, ?string $module = null, ?string $details = null): void
    {
        $user = auth()->user();
        $ip = request()?->ip();

        [$country, $region] = in_array($action, ['Logged in', 'Logged out'], true)
            ? static::geoForIp($ip)
            : [null, null];

        static::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'Daniel Mwinuka',
            'action' => $action,
            'module' => $module,
            'details' => $details,
            'ip' => $ip,
            'country' => $country,
            'region' => $region,
        ]);
    }

    /**
     * Best-effort country/region lookup for an IP. Results are cached per IP
     * so repeat logins are instant and lookups never block authentication.
     */
    private static function geoForIp(?string $ip): array
    {
        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            return [null, null];
        }

        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return [null, null];
        }

        $cache = json_decode((string) Setting::get('audit.geo_cache', '{}'), true) ?? [];
        if (isset($cache[$ip])) {
            return [$cache[$ip]['country'] ?? null, $cache[$ip]['region'] ?? null];
        }

        foreach (['https://ipapi.co/'.$ip.'/json/', 'https://ipwho.is/'.$ip] as $url) {
            try {
                $response = Http::timeout(3)->acceptJson()->get($url);

                if (! $response->ok()) {
                    continue;
                }

                $d = $response->json();

                $isIpapi = str_contains($url, 'ipapi.co');
                if (($isIpapi && ($d['error'] ?? false) === true) || (! $isIpapi && ($d['success'] ?? true) === false)) {
                    continue;
                }

                $country = $isIpapi ? ($d['country_name'] ?? null) : ($d['country'] ?? null);
                $region = $isIpapi ? ($d['region'] ?? $d['city'] ?? null) : ($d['region'] ?? $d['city'] ?? null);

                if ($country || $region) {
                    $cache[$ip] = ['country' => $country, 'region' => $region];
                    Setting::put('audit.geo_cache', json_encode($cache));

                    return [$country, $region];
                }
            } catch (\Throwable $e) {
                // Best-effort geolocation only; never break authentication.
            }
        }

        return [null, null];
    }
}
