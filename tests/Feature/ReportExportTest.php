<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    private function csv($response): string
    {
        return file_get_contents($response->baseResponse->getFile()->getPathname());
    }

    public function test_csv_export_downloads_registrations(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        Registration::factory()->create(['event_id' => $event->id, 'name' => 'Plain Person', 'email' => 'plain@example.com']);

        $response = $this->actingAs($this->orgAdmin($org))
            ->get('/reports/export?type=registrations&format=csv')
            ->assertOk()
            ->assertDownload();

        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
        $content = $this->csv($response);
        $this->assertStringContainsString('Reference', $content);
        $this->assertStringContainsString('plain@example.com', $content);
    }

    public function test_formula_injection_in_name_is_neutralised_with_leading_quote(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        Registration::factory()->create(['event_id' => $event->id, 'name' => '=HYPERLINK("x")']);

        $content = $this->csv(
            $this->actingAs($this->orgAdmin($org))->get('/reports/export?type=registrations&format=csv')->assertOk()
        );

        $this->assertStringContainsString("'=HYPERLINK", $content);
        $this->assertDoesNotMatchRegularExpression('/(^|,)"?=HYPERLINK/m', $content);
    }

    public function test_export_requires_valid_format(): void
    {
        $org = Organization::factory()->create();

        $this->actingAs($this->orgAdmin($org))
            ->getJson('/reports/export?type=registrations&format=docx')
            ->assertStatus(422)->assertJsonValidationErrors(['format']);
    }

    public function test_export_requires_permission(): void
    {
        $org = Organization::factory()->create();
        $this->seedRoles();

        $this->actingAs($this->plainUser($org))
            ->getJson('/reports/export?type=registrations&format=csv')
            ->assertForbidden();
    }
}
