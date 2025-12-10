<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Process the request
        $response = $next($request);

        $duration = (microtime(true) - $startTime) * 1000; // milliseconds

        // Only log authenticated requests
        if ($request->user()) {
            try {
                AuditLog::create([
                    'user_id' => $request->user()->id,
                    'action' => $request->route()?->getName() ?? $request->path(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'request_data' => $this->filterSensitiveData($request->all()),
                    'response_status' => $response->status(),
                    'duration_ms' => round($duration, 2),
                ]);
            } catch (\Exception $e) {
                // Don't let audit logging break the request
                \Log::error('Audit log failed: ' . $e->getMessage());
            }
        }

        return $response;
    }

    /**
     * Filter sensitive data from request
     */
    private function filterSensitiveData(array $data): array
    {
        $sensitive = ['password', 'password_confirmation', 'token', 'secret', 'api_key'];

        foreach ($sensitive as $key) {
            if (isset($data[$key])) {
                $data[$key] = '***REDACTED***';
            }
        }

        // Limit data size to prevent huge audit logs
        $json = json_encode($data);
        if (strlen($json) > 10000) {
            return ['message' => 'Request data too large for audit log'];
        }

        return $data;
    }
}
