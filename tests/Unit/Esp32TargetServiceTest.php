<?php

namespace Tests\Unit;

use App\Services\Esp32TargetService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class Esp32TargetServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'esp32.type' => 'esp32',
            'esp32.host' => '192.168.4.1',
            'esp32.port' => 80,
            'esp32.scheme' => 'http',
            'esp32.allowed_hosts' => ['192.168.4.1'],
            'esp32.capture_interface' => 'lo',
            'esp32.health_path' => '/health',
            'esp32.metrics_path' => '/metrics',
        ]);
    }

    public function test_readiness_accepts_valid_health_and_metrics(): void
    {
        Http::fake([
            'http://192.168.4.1/health' => Http::response('OK', 200),
            'http://192.168.4.1/metrics' => Http::response($this->metrics(), 200),
        ]);

        $result = app(Esp32TargetService::class)->readiness();

        $this->assertTrue($result['ready']);
        $this->assertSame(220548, $result['metrics']['free_heap_bytes']);
        Http::assertSentCount(2);
    }

    public function test_public_or_non_allowlisted_target_is_rejected_before_http(): void
    {
        Http::fake();
        config([
            'esp32.host' => '8.8.8.8',
            'esp32.allowed_hosts' => ['8.8.8.8'],
            'esp32.capture_filter' => 'host 8.8.8.8 and tcp port 80',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TARGET_NOT_ALLOWED');

        try {
            app(Esp32TargetService::class)->readiness(false);
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_missing_metric_field_is_rejected(): void
    {
        Http::fake([
            'http://192.168.4.1/metrics' => Http::response(
                array_diff_key($this->metrics(), ['reset_reason' => true]),
                200,
            ),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('field reset_reason tidak tersedia');

        app(Esp32TargetService::class)->metrics();
    }

    public function test_missing_capture_interface_has_clear_error(): void
    {
        config(['esp32.capture_interface' => 'definitely-missing-interface']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CAPTURE_INTERFACE_NOT_FOUND');

        app(Esp32TargetService::class)->assertCaptureInterface();
    }

    public function test_capture_filter_cannot_be_broader_than_configured_target(): void
    {
        config(['esp32.capture_filter' => 'tcp port 80']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TARGET_NOT_ALLOWED');

        app(Esp32TargetService::class)->assertAllowedTarget();
    }

    private function metrics(): array
    {
        return [
            'uptime_seconds' => 907,
            'free_heap_bytes' => 220548,
            'min_free_heap_bytes' => 215588,
            'request_count' => 5,
            'reset_reason' => 'POWERON',
        ];
    }
}
