<?php

namespace Tests\Unit;

use App\Services\UrlGuard;
use Tests\TestCase;

class UrlGuardTest extends TestCase
{
    public function test_allows_public_http_and_https_urls(): void
    {
        $guard = new UrlGuard();

        $this->assertTrue($guard->isAllowed('https://8.8.8.8/info'));
        $this->assertTrue($guard->isAllowed('http://1.1.1.1/about'));
    }

    public function test_blocks_private_and_loopback_ips_and_non_http_schemes(): void
    {
        $guard = new UrlGuard();

        $this->assertFalse($guard->isAllowed('http://127.0.0.1'));
        $this->assertFalse($guard->isAllowed('http://127.0.0.1:8080/admin'));
        $this->assertFalse($guard->isAllowed('http://localhost/secret'));
        $this->assertFalse($guard->isAllowed('http://10.0.0.1/internal'));
        $this->assertFalse($guard->isAllowed('http://172.16.0.5/api'));
        $this->assertFalse($guard->isAllowed('http://192.168.1.1/router'));
        $this->assertFalse($guard->isAllowed('http://169.254.169.254/latest/meta-data/'));
        $this->assertFalse($guard->isAllowed('file:///etc/passwd'));
        $this->assertFalse($guard->isAllowed('ftp://example.com/file'));
    }
}
