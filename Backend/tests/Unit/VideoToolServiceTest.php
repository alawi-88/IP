<?php

namespace Tests\Unit;

use App\Models\Mentor;
use App\Models\MentorVideoTool;
use App\Models\MentorSession;
use App\Models\Participant;
use App\Models\Competition;
use App\Models\MentorAvailability;
use App\Services\VideoToolIntegrationService;
use App\Services\SessionSchedulingService;
use App\Services\VideoTools\ZoomService;
use App\Services\VideoTools\TeamsService;
use App\Services\VideoTools\GoogleMeetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class VideoToolServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Mentor $mentor;
    protected Participant $participant;
    protected Competition $competition;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mentor = Mentor::factory()->create();
        $this->participant = Participant::factory()->create();
        $this->competition = Competition::factory()->create();
    }

    /** @test */
    public function video_tool_integration_service_can_get_authorization_url()
    {
        $service = new VideoToolIntegrationService();
        
        $url = $service->getAuthorizationUrl('zoom', $this->mentor->id);
        
        $this->assertStringContainsString('zoom.us/oauth/authorize', $url);
        $this->assertStringContainsString('client_id', $url);
        $this->assertStringContainsString('state', $url);
    }

    /** @test */
    public function video_tool_integration_service_can_get_teams_authorization_url()
    {
        $service = new VideoToolIntegrationService();
        
        $url = $service->getAuthorizationUrl('teams', $this->mentor->id);
        
        $this->assertStringContainsString('login.microsoftonline.com', $url);
        $this->assertStringContainsString('client_id', $url);
        $this->assertStringContainsString('state', $url);
    }

    /** @test */
    public function video_tool_integration_service_can_get_google_meet_authorization_url()
    {
        $service = new VideoToolIntegrationService();
        
        $url = $service->getAuthorizationUrl('google_meet', $this->mentor->id);
        
        $this->assertStringContainsString('accounts.google.com', $url);
        $this->assertStringContainsString('client_id', $url);
        $this->assertStringContainsString('state', $url);
    }

    /** @test */
    public function video_tool_integration_service_throws_exception_for_invalid_tool()
    {
        $service = new VideoToolIntegrationService();
        
        $this->expectException(\InvalidArgumentException::class);
        $service->getAuthorizationUrl('invalid_tool', $this->mentor->id);
    }

    /** @test */
    public function mentor_video_tool_model_can_set_as_default()
    {
        // Create two video tools
        $zoomTool = MentorVideoTool::factory()->create([
            'mentor_id' => $this->mentor->id,
            'tool_type' => 'zoom',
            'is_default' => true,
        ]);

        $teamsTool = MentorVideoTool::factory()->create([
            'mentor_id' => $this->mentor->id,
            'tool_type' => 'teams',
            'is_default' => false,
        ]);

        // Set teams as default
        $teamsTool->setAsDefault();

        // Verify teams is now default and zoom is not
        $this->assertTrue($teamsTool->fresh()->is_default);
        $this->assertFalse($zoomTool->fresh()->is_default);
    }

    /** @test */
    public function mentor_video_tool_model_can_check_token_expiry()
    {
        $videoTool = MentorVideoTool::factory()->create([
            'mentor_id' => $this->mentor->id,
            'tool_type' => 'zoom',
            'token_expires_at' => now()->addHour(),
        ]);

        $this->assertFalse($videoTool->isTokenExpired());

        $videoTool->update(['token_expires_at' => now()->subHour()]);
        $this->assertTrue($videoTool->isTokenExpired());
    }

    /** @test */
    public function mentor_video_tool_model_can_check_validity()
    {
        $videoTool = MentorVideoTool::factory()->create([
            'mentor_id' => $this->mentor->id,
            'tool_type' => 'zoom',
            'is_active' => true,
            'access_token' => 'test_token',
            'token_expires_at' => now()->addHour(),
        ]);

        $this->assertTrue($videoTool->isValid());

        $videoTool->update(['is_active' => false]);
        $this->assertFalse($videoTool->isValid());
    }

    /** @test */
    public function mentor_session_model_can_check_status()
    {
        $session = MentorSession::factory()->create([
            'mentor_id' => $this->mentor->id,
            'participant_id' => $this->participant->id,
            'competition_id' => $this->competition->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addHour(),
        ]);

        $this->assertTrue($session->isUpcoming());
        $this->assertFalse($session->isInProgress());
        $this->assertFalse($session->isCompleted());
        $this->assertFalse($session->isCancelled());

        $session->update(['status' => 'in_progress']);
        $this->assertTrue($session->isInProgress());

        $session->update(['status' => 'completed']);
        $this->assertTrue($session->isCompleted());

        $session->update(['status' => 'cancelled']);
        $this->assertTrue($session->isCancelled());
    }

    /** @test */
    public function mentor_session_model_can_format_duration()
    {
        $session = MentorSession::factory()->create([
            'mentor_id' => $this->mentor->id,
            'participant_id' => $this->participant->id,
            'competition_id' => $this->competition->id,
            'duration_minutes' => 90,
        ]);

        $this->assertEquals('1h 30m', $session->duration_formatted);

        $session->update(['duration_minutes' => 60]);
        $this->assertEquals('1h', $session->duration_formatted);

        $session->update(['duration_minutes' => 30]);
        $this->assertEquals('30m', $session->duration_formatted);
    }

    /** @test */
    public function session_scheduling_service_can_get_available_slots()
    {
        // Create mentor availability
        MentorAvailability::factory()->create([
            'mentor_id' => $this->mentor->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_recurring' => false,
            'is_active' => true,
        ]);

        $service = new SessionSchedulingService(
            Mockery::mock(VideoToolIntegrationService::class)
        );

        $slots = $service->getAvailableSlots(
            $this->mentor->id,
            now()->addDay()->format('Y-m-d'),
            60
        );

        $this->assertIsArray($slots);
        $this->assertNotEmpty($slots);
        
        // Check that slots are properly formatted
        foreach ($slots as $slot) {
            $this->assertArrayHasKey('start_time', $slot);
            $this->assertArrayHasKey('end_time', $slot);
            $this->assertArrayHasKey('duration_minutes', $slot);
            $this->assertEquals(60, $slot['duration_minutes']);
        }
    }

    /** @test */
    public function session_scheduling_service_validates_mentor_availability()
    {
        $service = new SessionSchedulingService(
            Mockery::mock(VideoToolIntegrationService::class)
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Mentor is not available at the requested time');

        $service->scheduleSession([
            'mentor_id' => $this->mentor->id,
            'participant_id' => $this->participant->id,
            'competition_id' => $this->competition->id,
            'title' => 'Test Session',
            'scheduled_at' => now()->addHour(),
            'duration_minutes' => 60,
        ]);
    }

    /** @test */
    public function zoom_service_is_properly_configured()
    {
        $service = new ZoomService();
        
        // Mock config values
        config(['services.zoom.client_id' => 'test_client_id']);
        config(['services.zoom.client_secret' => 'test_client_secret']);
        config(['services.zoom.redirect_uri' => 'http://test.com/callback']);
        
        $this->assertTrue($service->isConfigured());
        $this->assertEquals('zoom', $service->getToolType());
    }

    /** @test */
    public function teams_service_is_properly_configured()
    {
        $service = new TeamsService();
        
        // Mock config values
        config(['services.teams.client_id' => 'test_client_id']);
        config(['services.teams.client_secret' => 'test_client_secret']);
        config(['services.teams.redirect_uri' => 'http://test.com/callback']);
        
        $this->assertTrue($service->isConfigured());
        $this->assertEquals('teams', $service->getToolType());
    }

    /** @test */
    public function google_meet_service_is_properly_configured()
    {
        $service = new GoogleMeetService();
        
        // Mock config values
        config(['services.google.client_id' => 'test_client_id']);
        config(['services.google.client_secret' => 'test_client_secret']);
        config(['services.google.redirect_uri' => 'http://test.com/callback']);
        
        $this->assertTrue($service->isConfigured());
        $this->assertEquals('google_meet', $service->getToolType());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
