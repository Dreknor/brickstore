<?php

namespace Tests\Unit;

use App\Models\Store;
use App\Models\User;
use App\Services\NextcloudService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NextcloudServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->store = Store::factory()
            ->for($user)
            ->withNextcloudCredentials()
            ->create([
                'nextcloud_invoice_path' => '/Rechnungen/{year}/{month}',
            ]);
    }

    #[Test]
    public function can_instantiate_nextcloud_service(): void
    {
        $service = new NextcloudService($this->store);

        $this->assertInstanceOf(NextcloudService::class, $service);
    }

    #[Test]
    public function can_generate_upload_path_with_placeholders(): void
    {
        $service = new NextcloudService($this->store);

        $path = $service->generateUploadPath('/Rechnungen/{year}/{month}');

        $currentYear = now()->year;
        $currentMonth = now()->format('m');

        $this->assertStringContainsString((string) $currentYear, $path);
        $this->assertStringContainsString($currentMonth, $path);
        $this->assertStringNotContainsString('{year}', $path);
        $this->assertStringNotContainsString('{month}', $path);
    }

    #[Test]
    public function can_generate_path_with_store_name(): void
    {
        $service = new NextcloudService($this->store);

        $path = $service->generateUploadPath('/Rechnungen/{store_name}');

        $this->assertStringContainsString(
            str_slug($this->store->name),
            $path
        );
    }

    #[Test]
    public function has_credentials_returns_true_when_configured(): void
    {
        $service = new NextcloudService($this->store);

        $this->assertTrue($service->hasCredentials());
    }

    #[Test]
    public function has_credentials_returns_false_when_not_configured(): void
    {
        $store = Store::factory()
            ->for(User::factory())
            ->create([
                'nextcloud_url' => null,
                'nextcloud_username' => null,
                'nextcloud_password' => null,
            ]);

        $service = new NextcloudService($store);

        $this->assertFalse($service->hasCredentials());
    }

    #[Test]
    public function path_generation_handles_multiple_placeholders(): void
    {
        $service = new NextcloudService($this->store);

        $path = $service->generateUploadPath('/Files/{year}-{month}-{day}/{store_name}');

        $this->assertStringNotContainsString('{year}', $path);
        $this->assertStringNotContainsString('{month}', $path);
        $this->assertStringNotContainsString('{day}', $path);
        $this->assertStringNotContainsString('{store_name}', $path);

        // Should contain numeric values
        $this->assertRegExp('/\d{4}/', $path); // year
        $this->assertRegExp('/\d{2}/', $path); // month/day
    }
}

