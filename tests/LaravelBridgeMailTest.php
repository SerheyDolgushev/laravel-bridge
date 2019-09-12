<?php
declare(strict_types=1);

namespace Platformsh\FlexBridge\Tests;

use PHPUnit\Framework\TestCase;
use function Platformsh\LaravelBridge\mapPlatformShEnvironment;

class LaravelBridgeMailTest extends TestCase
{

    protected $config;

    public function setUp()
    {
        parent::setUp();

        $this->host = 'smtp.platform.sh';
    }

    public function test_not_on_platformsh_does_nothing() : void
    {
        mapPlatformShEnvironment();

        $this->assertFalse(getenv('MAIL_HOST'));
    }

    public function test_mail_gets_mapped() : void
    {
        putenv('PLATFORM_APPLICATION_NAME=test');
        putenv('PLATFORM_ENVIRONMENT=test');

        putenv(sprintf('SMTP_HOST=%s', $this->host));

        mapPlatformShEnvironment();

        $this->assertEquals('smtp', getenv('MAIL_DRIVER'));
        $this->assertEquals($this->host, getenv('MAIL_HOST'));
        $this->assertEquals('25', getenv('MAIL_PORT'));
    }
}
