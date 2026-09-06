<?php

namespace Tests\Feature;

use App\Models\MessageTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTemplatesEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_a_seeded_template_persists_across_page_loads(): void
    {
        $role = Role::firstOrCreate(['name' => 'Super Administrator']);
        $user = User::factory()->create(['name' => 'Big Leader', 'role_id' => $role->id]);
        $this->actingAs($user);

        MessageTemplate::seedDefaults();
        $template = MessageTemplate::where('key', 'task_assignment')->first();
        $this->assertNotNull($template);

        $edited = 'Karibu {name}, kazi yako mpya {task} imebadilishwa. Tokeni: EDITED123';

        $this->put(route('messaging.templates.update', $template->id), [
            'name'    => 'Activity — Assignment (edited)',
            'message' => $edited,
        ])->assertRedirect();

        $this->get(route('messaging.templates'))->assertOk()->assertSee('EDITED123');

        $template->refresh();
        $this->assertSame('Activity — Assignment (edited)', $template->name);
        $this->assertSame($edited, $template->message);
    }

    public function test_seed_defaults_does_not_overwrite_admin_edits(): void
    {
        $template = MessageTemplate::create([
            'key'       => 'card_invite',
            'name'      => 'My Custom Invite',
            'message'   => 'My custom invite message {name}',
            'created_by' => 'David Ngungila',
        ]);

        MessageTemplate::seedDefaults();

        $template->refresh();
        $this->assertSame('My Custom Invite', $template->name);
        $this->assertSame('My custom invite message {name}', $template->message);
    }

    public function test_seed_defaults_creates_missing_rows(): void
    {
        $this->assertCount(0, MessageTemplate::all());

        MessageTemplate::seedDefaults();

        $this->assertNotNull(MessageTemplate::where('key', 'task_assignment')->first());
        $this->assertNotNull(MessageTemplate::where('key', 'pledge_received')->first());
    }
}