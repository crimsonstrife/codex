<?php

namespace Tests\Unit;

use App\Support\HealthCheckResultPresenter;
use Tests\TestCase;

class HealthCheckResultPresenterTest extends TestCase
{
    public function test_it_formats_security_advisories_for_display(): void
    {
        $presented = HealthCheckResultPresenter::presentResult([
            'name' => 'security-advisories',
            'label' => 'Security Advisories',
            'status' => 'failed',
            'shortSummary' => 'Attention required',
            'notificationMessage' => 'Security advisories found for `laravel/framework`',
            'meta' => [
                'laravel/framework' => [
                    [
                        'advisoryId' => 'PKSA-123',
                        'packageName' => 'laravel/framework',
                        'title' => 'Remote code execution in routing pipeline',
                        'link' => 'https://github.com/advisories/GHSA-test',
                        'cve' => 'CVE-2026-1234',
                        'affectedVersions' => '<11.4.1',
                        'reportedAt' => '2026-03-20T14:30:00+00:00',
                        'sources' => [
                            [
                                'name' => 'GitHub Advisory Database',
                                'url' => 'https://github.com/advisories/GHSA-test',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame([], $presented['metaRows']);
        $this->assertNotNull($presented['securityAdvisories']);
        $this->assertSame(1, $presented['securityAdvisories']['packageCount']);
        $this->assertSame(1, $presented['securityAdvisories']['advisoryCount']);
        $this->assertSame('laravel/framework', $presented['securityAdvisories']['packages'][0]['packageName']);
        $this->assertSame('Remote code execution in routing pipeline', $presented['securityAdvisories']['packages'][0]['advisories'][0]['title']);
        $this->assertSame('CVE-2026-1234', $presented['securityAdvisories']['packages'][0]['advisories'][0]['cve']);
        $this->assertSame('<11.4.1', $presented['securityAdvisories']['packages'][0]['advisories'][0]['affectedVersions']);
        $this->assertSame('Mar 20, 2026', $presented['securityAdvisories']['packages'][0]['advisories'][0]['reportedAt']);
        $this->assertSame('GitHub Advisory Database', $presented['securityAdvisories']['packages'][0]['advisories'][0]['sources'][0]['label']);
    }

    public function test_it_pretty_prints_nested_non_advisory_meta(): void
    {
        $presented = HealthCheckResultPresenter::presentResult([
            'name' => 'optimization',
            'label' => 'Optimization',
            'status' => 'warning',
            'shortSummary' => 'Some optimizations are missing',
            'notificationMessage' => null,
            'meta' => [
                'config_cache' => true,
                'links' => ['https://example.com/docs'],
                'missing' => [
                    'routes' => false,
                    'events' => true,
                ],
            ],
        ]);

        $this->assertNull($presented['securityAdvisories']);
        $this->assertCount(3, $presented['metaRows']);
        $this->assertSame('Yes', $presented['metaRows'][0]['value']);
        $this->assertTrue($presented['metaRows'][1]['isUrl']);
        $this->assertTrue($presented['metaRows'][2]['isBlock']);
        $this->assertStringContainsString('"routes": false', $presented['metaRows'][2]['value']);
    }
}
