<?php

namespace Tests\Feature;

use App\Models\Mentor;
use App\Models\MentorVideoTool;
use App\Models\MentorSession;
use App\Models\Participant;
use App\Models\Competition;
use App\Services\VideoToolIntegrationService;
use App\Services\SessionSchedulingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoToolIntegrationTest extends TestCase
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
    public function mentor_can_get_authorization_url_for_zoom()
    {
        $response = $this->actingAs($this->mentor, 'mentors')
            ->postJson('/api/mentor/video-tools/authorize', [
                'tool_type' => 'zoom'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'authorization_url',
                'message'
            ]);
    }

    /** @test */
    public function mentor_can_get_authorization_url_for_teams()
    {
        $response = $this->actingAs($this->mentor, 'mentors')
            ->postJson('/api/mentor/video-tools/authorize', [
                'tool_type' => 'teams'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'authorization_url',
                'message'
            ]);
    }

    /** @test */
    public function mentor_can_get_authorization_url_for_google_meet()
    {
        $response = $this->actingAs($this->mentor, 'mentors')
            ->postJson('/api/mentor/video-tools/authorize', [
                'tool_type' => 'google_meet'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'authorization_url',
                'message'
            ]);
    }

    /** @test */
    public function mentor_cannot_get_authorization_url_for_invalid_tool()
    {
        $response = $this->actingAs($this->mentor, 'mentors')
            ->postJson('/api/mentor/video-tools/authorize', [
                'tool_type' => 'invalid_tool'
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function mentor_can_get_video_tool_integrations()
    {
        // Create a video tool integration
        MentorVideoTool::factory()->create([
            'mentor_id' => $this->mentor->id,
            'tool_type' => 'zoom',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->mentor, 'mentors')
            ->getJson('/api/mentor/video-tools/integrations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'tool_type',
                        'tool_display_name',
                        'account_email',
                        'is_default',
                        'is_active',
                        'is_valid',
                        'last_sync_at',
                    ]
                ]
            ]);
    }

    /** @test */
    public function mentor_can_set_default_video_tool()
    {
        // Create video tool integrations
        $zoomTool = MentorVideoTool::factory()->create([
            'mentor_id' => $this->mentor->id,
            'tool_type' => 'zoom',
            'is_active' => true,
            'is_default' => false,
        ]);

        $teamsTool = MentorVideoTool::factory()->create([
            'mentor_id' => $this->mentor->id,
            'tool_type' => 'teams',
            'is_active' => true,
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->mentor, 'mentors')
            ->postJson('/api/mentor/video-tools/set-default', [
                'tool_type' => 'zoom'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'tool_type',
                    'tool_display_name',
                ]
            ]);

        // Verify that zoom is now default and teams is not
        $this->assertTrue($zoomTool->fresh()->is_default);
        $this->assertFalse($teamsTool->fresh()->is_default);
    }

    /** @test */
    public function mentor_can_disconnect_video_tool()
    {
        $videoTool = MentorVideoTool::factory()->create([
            'mentor_id' => $this->mentor->id,
            'tool_type' => 'zoom',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->mentor, 'mentors')
            ->postJson('/api/mentor/video-tools/disconnect', [
                'tool_type' => 'zoom'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message'
            ]);

        $this->assertDatabaseMissing('mentor_video_tools', [
            'id' => $videoTool->id,
        ]);
    }

    /** @test */
    public function mentor_can_schedule_session()
    {
        $sessionData = [
            'participant_id' => $this->participant->id,
            'competition_id' => $this->competition->id,
            'title' => 'Test Session',
            'description' => 'Test session description',
            'scheduled_at' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
        ];

        $response = $this->actingAs($this->mentor, 'mentors')
            ->postJson('/api/mentor/sessions', $sessionData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'scheduled_at',
                    'duration_minutes',
                    'status',
                    'mentor',
                    'participant',
                    'competition',
                ]
            ]);

        $this->assertDatabaseHas('mentor_sessions', [
            'mentor_id' => $this->mentor->id,
            'participant_id' => $this->participant->id,
            'competition_id' => $this->competition->id,
            'title' => 'Test Session',
        ]);
    }

    /** @test */
    public function mentor_can_get_available_slots()
    {
        $response = $this->actingAs($this->mentor, 'mentors')
            ->getJson('/api/mentor/sessions/available-slots', [
                'date' => now()->addDays(1)->format('Y-m-d'),
                'duration_minutes' => 60,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'start_time',
                        'end_time',
                        'duration_minutes',
                    ]
                ]
            ]);
    }

    /** @test */
    public function mentor_can_get_sessions()
    {
        // Create a session
        MentorSession::factory()->create([
            'mentor_id' => $this->mentor->id,
            'participant_id' => $this->participant->id,
            'competition_id' => $this->competition->id,
        ]);

        $response = $this->actingAs($this->mentor, 'mentors')
            ->getJson('/api/mentor/sessions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'scheduled_at',
                        'status',
                        'mentor',
                        'participant',
                        'competition',
                    ]
                ],
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ]
            ]);
    }

    /** @test */
    public function mentor_can_update_session()
    {
        $session = MentorSession::factory()->create([
            'mentor_id' => $this->mentor->id,
            'participant_id' => $this->participant->id,
            'competition_id' => $this->competition->id,
            'title' => 'Original Title',
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->mentor, 'mentors')
            ->putJson("/api/mentor/sessions/{$session->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'
            ]);

        $this->assertDatabaseHas('mentor_sessions', [
            'id' => $session->id,
            'title' => 'Updated Title',
            'description' => 'Updated description',
        ]);
    }

    /** @test */
    public function mentor_can_cancel_session()
    {
        $session = MentorSession::factory()->create([
            'mentor_id' => $this->mentor->id,
            'participant_id' => $this->participant->id,
            'competition_id' => $this->competition->id,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->mentor, 'mentors')
            ->postJson("/api/mentor/sessions/{$session->id}/cancel", [
                'reason' => 'Emergency'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'
            ]);

        $this->assertDatabaseHas('mentor_sessions', [
            'id' => $session->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function mentor_cannot_access_other_mentor_sessions()
    {
        $otherMentor = Mentor::factory()->create();
        $session = MentorSession::factory()->create([
            'mentor_id' => $otherMentor->id,
            'participant_id' => $this->participant->id,
            'competition_id' => $this->competition->id,
        ]);

        $response = $this->actingAs($this->mentor, 'mentors')
            ->getJson("/api/mentor/sessions/{$session->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function mentor_cannot_update_other_mentor_sessions()
    {
        $otherMentor = Mentor::factory()->create();
        $session = MentorSession::factory()->create([
            'mentor_id' => $otherMentor->id,
            'participant_id' => $this->participant->id,
            'competition_id' => $this->competition->id,
        ]);

        $response = $this->actingAs($this->mentor, 'mentors')
            ->putJson("/api/mentor/sessions/{$session->id}", [
                'title' => 'Unauthorized Update'
            ]);

        $response->assertStatus(404);
    }
}
