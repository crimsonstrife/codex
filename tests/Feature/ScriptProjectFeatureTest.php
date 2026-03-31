<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PageTemplate;
use App\Models\ScriptEntity;
use App\Models\ScriptProject;
use App\Models\ScriptRevision;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScriptProjectFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_script_project_creates_a_default_draft_and_shows_it_in_the_workspace(): void
    {
        $owner = $this->createUser();
        $workspace = $this->createWorkspace($owner);

        $response = $this->actingAs($owner)->post(route('workspaces.scripts.store', $workspace), [
            'title' => 'Pilot',
            'logline' => 'An exhausted fixer takes one last job.',
            'synopsis' => 'A pilot script with a binder-friendly workspace.',
            'status' => 'draft',
        ]);

        $script = ScriptProject::query()->firstOrFail();

        $response->assertRedirect(route('workspaces.scripts.edit', [$workspace, $script]));

        $this->assertSame('screenplay', $script->type);
        $this->assertCount(2, $script->document['blocks']);
        $this->assertDatabaseHas('script_revisions', [
            'script_project_id' => $script->id,
            'revision_number' => 1,
            'title' => 'Pilot',
        ]);

        $this->actingAs($owner)
            ->get(route('workspaces.show', $workspace))
            ->assertOk()
            ->assertSee('Pilot')
            ->assertSee('Scripts');
    }

    public function test_updating_a_script_persists_structured_blocks_and_creates_a_revision(): void
    {
        $owner = $this->createUser();
        $workspace = $this->createWorkspace($owner);
        $script = $this->createScript($workspace, $owner);

        $character = $script->entities()->create([
            'created_by' => $owner->id,
            'type' => ScriptEntity::TYPE_CHARACTER,
            'name' => 'Mara',
            'display_name' => 'MARA',
        ]);

        $location = $script->entities()->create([
            'created_by' => $owner->id,
            'type' => ScriptEntity::TYPE_LOCATION,
            'name' => 'Safe House',
            'display_name' => 'SAFE HOUSE',
        ]);

        $document = [
            'version' => 1,
            'blocks' => [
                [
                    'id' => 'scene-1',
                    'type' => 'scene_heading',
                    'text' => 'BATHROOM',
                    'location_entity_id' => $location->id,
                    'meta' => ['prefix' => 'INT.', 'time_of_day' => 'NIGHT'],
                ],
                [
                    'id' => 'action-1',
                    'type' => 'action',
                    'text' => 'Water drips from the ceiling.',
                ],
                [
                    'id' => 'cue-1',
                    'type' => 'character_cue',
                    'text' => 'MARA',
                    'character_entity_id' => $character->id,
                    'modifiers' => 'V.O.',
                ],
                [
                    'id' => 'paren-1',
                    'type' => 'parenthetical',
                    'text' => 'quietly',
                ],
                [
                    'id' => 'dialogue-1',
                    'type' => 'dialogue',
                    'text' => 'No one gets out clean.',
                ],
                [
                    'id' => 'transition-1',
                    'type' => 'transition',
                    'text' => 'CUT TO:',
                ],
            ],
        ];

        $response = $this->actingAs($owner)->put(route('workspaces.scripts.update', [$workspace, $script]), [
            'title' => 'Pilot',
            'logline' => 'An exhausted fixer takes one last job.',
            'synopsis' => 'Updated synopsis',
            'status' => 'draft',
            'document' => json_encode($document, JSON_THROW_ON_ERROR),
            'change_summary' => 'Scene polish',
        ]);

        $response->assertRedirect(route('workspaces.scripts.show', [$workspace, $script]));

        $script->refresh();

        $this->assertCount(6, $script->document['blocks']);
        $this->assertSame('character_cue', $script->document['blocks'][2]['type']);
        $this->assertSame($character->id, $script->document['blocks'][2]['character_entity_id']);
        $this->assertDatabaseHas('script_revisions', [
            'script_project_id' => $script->id,
            'revision_number' => 2,
            'change_summary' => 'Scene polish',
        ]);
    }

    public function test_entity_renames_flow_through_rendered_and_printed_script_output(): void
    {
        $owner = $this->createUser();
        $workspace = $this->createWorkspace($owner);
        $script = $this->createScript($workspace, $owner);

        $character = $script->entities()->create([
            'created_by' => $owner->id,
            'type' => ScriptEntity::TYPE_CHARACTER,
            'name' => 'Mara',
            'display_name' => 'MARA',
        ]);

        $location = $script->entities()->create([
            'created_by' => $owner->id,
            'type' => ScriptEntity::TYPE_LOCATION,
            'name' => 'Safe House',
            'display_name' => 'SAFE HOUSE',
        ]);

        $script->update([
            'document' => [
                'version' => 1,
                'blocks' => [
                    [
                        'id' => 'scene-1',
                        'type' => 'scene_heading',
                        'text' => 'KITCHEN',
                        'location_entity_id' => $location->id,
                        'modifiers' => null,
                        'position' => 1,
                        'character_entity_id' => null,
                        'meta' => ['prefix' => 'INT.', 'time_of_day' => 'DAY'],
                    ],
                    [
                        'id' => 'cue-1',
                        'type' => 'character_cue',
                        'text' => 'MARA',
                        'character_entity_id' => $character->id,
                        'modifiers' => 'O.S.',
                        'position' => 2,
                        'location_entity_id' => null,
                        'meta' => [],
                    ],
                    [
                        'id' => 'dialogue-1',
                        'type' => 'dialogue',
                        'text' => 'Keep moving.',
                        'position' => 3,
                        'character_entity_id' => null,
                        'location_entity_id' => null,
                        'modifiers' => null,
                        'meta' => [],
                    ],
                ],
            ],
        ]);

        $this->actingAs($owner)->patch(route('workspaces.scripts.entities.update', [$workspace, $script, $character]), [
            'name' => 'Captain Mara',
            'display_name' => 'CAPTAIN MARA',
            'aliases' => '',
            'notes' => '',
            'hierarchy_text' => '',
        ])->assertRedirect();

        $this->actingAs($owner)->patch(route('workspaces.scripts.entities.update', [$workspace, $script, $location]), [
            'name' => 'Harbor Safe House',
            'display_name' => 'HARBOR SAFE HOUSE',
            'aliases' => '',
            'notes' => '',
            'hierarchy_text' => '',
        ])->assertRedirect();

        $this->actingAs($owner)
            ->get(route('workspaces.scripts.show', [$workspace, $script]))
            ->assertOk()
            ->assertSee('CAPTAIN MARA')
            ->assertSee('INT. KITCHEN, HARBOR SAFE HOUSE - DAY')
            ->assertSee('CAPTAIN MARA (O.S.)');

        $this->actingAs($owner)
            ->get(route('workspaces.scripts.print', [$workspace, $script]))
            ->assertOk()
            ->assertSee('CAPTAIN MARA')
            ->assertSee('HARBOR SAFE HOUSE');
    }

    public function test_binder_pages_can_be_created_attached_reordered_and_rendered_on_page_cards(): void
    {
        $owner = $this->createUser();
        $workspace = $this->createWorkspace($owner);
        $script = $this->createScript($workspace, $owner);

        $outlinePage = Page::create([
            'title' => 'Existing Outline',
            'workspace_id' => $workspace->id,
            'author_id' => $owner->id,
            'content_type' => 'markdown',
            'status' => 'draft',
        ]);

        $template = PageTemplate::create([
            'workspace_id' => $workspace->id,
            'created_by' => $owner->id,
            'name' => 'Scene Card',
            'content' => 'Card body',
            'content_type' => 'markdown',
        ]);

        $this->actingAs($owner)->post(route('workspaces.scripts.binder-pages.store', [$workspace, $script]), [
            'page_id' => $outlinePage->id,
            'role' => 'outline',
        ])->assertRedirect();

        $this->actingAs($owner)->post(route('workspaces.scripts.binder-pages.create', [$workspace, $script]), [
            'title' => 'Cold Open Card',
            'template_id' => $template->id,
            'role' => 'scene_card',
        ])->assertRedirect();

        $script->refresh()->load('binderLinks.page');
        $this->assertCount(2, $script->binderLinks);

        $newLink = $script->binderLinks->last();
        $newPage = $newLink->page;

        $this->actingAs($owner)->patch(route('workspaces.scripts.binder-pages.update', [$workspace, $script, $newLink]), [
            'role' => 'research',
            'position' => 1,
        ])->assertRedirect();

        $newLink->refresh();
        $this->assertSame(1, $newLink->position);
        $this->assertSame('research', $newLink->role);

        $this->actingAs($owner)
            ->get(route('workspaces.pages.show', [$workspace, $newPage]))
            ->assertOk()
            ->assertSee($script->title)
            ->assertSee('Research');
    }

    public function test_script_permissions_follow_workspace_membership_rules(): void
    {
        $owner = $this->createUser();
        $member = $this->createUser();
        $editor = $this->createUser();
        $outsider = $this->createUser();

        $workspace = $this->createWorkspace($owner, false);
        $workspace->members()->attach($member, ['role' => 'member']);
        $workspace->members()->attach($editor, ['role' => 'editor']);

        $script = $this->createScript($workspace, $owner);

        $this->actingAs($outsider)
            ->get(route('workspaces.scripts.show', [$workspace, $script]))
            ->assertForbidden();

        $this->actingAs($member)
            ->get(route('workspaces.scripts.show', [$workspace, $script]))
            ->assertOk();

        $this->actingAs($member)
            ->get(route('workspaces.scripts.edit', [$workspace, $script]))
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('workspaces.scripts.edit', [$workspace, $script]))
            ->assertOk();
    }

    public function test_script_print_and_fountain_export_render_screenplay_elements(): void
    {
        $owner = $this->createUser();
        $workspace = $this->createWorkspace($owner);
        $script = $this->createScript($workspace, $owner);

        $character = $script->entities()->create([
            'created_by' => $owner->id,
            'type' => ScriptEntity::TYPE_CHARACTER,
            'name' => 'Mara',
            'display_name' => 'MARA',
        ]);

        $location = $script->entities()->create([
            'created_by' => $owner->id,
            'type' => ScriptEntity::TYPE_LOCATION,
            'name' => 'Safe House',
            'display_name' => 'SAFE HOUSE',
        ]);

        $script->update([
            'document' => [
                'version' => 1,
                'blocks' => [
                    [
                        'id' => 'scene-1',
                        'type' => 'scene_heading',
                        'text' => 'BATHROOM',
                        'location_entity_id' => $location->id,
                        'position' => 1,
                        'character_entity_id' => null,
                        'modifiers' => null,
                        'meta' => ['prefix' => 'INT.', 'time_of_day' => 'NIGHT'],
                    ],
                    [
                        'id' => 'cue-1',
                        'type' => 'character_cue',
                        'text' => 'MARA',
                        'character_entity_id' => $character->id,
                        'modifiers' => 'V.O.',
                        'position' => 2,
                        'location_entity_id' => null,
                        'meta' => [],
                    ],
                    [
                        'id' => 'dialogue-1',
                        'type' => 'dialogue',
                        'text' => 'The job is already bad.',
                        'position' => 3,
                        'character_entity_id' => null,
                        'location_entity_id' => null,
                        'modifiers' => null,
                        'meta' => [],
                    ],
                    [
                        'id' => 'transition-1',
                        'type' => 'transition',
                        'text' => 'CUT TO:',
                        'position' => 4,
                        'character_entity_id' => null,
                        'location_entity_id' => null,
                        'modifiers' => null,
                        'meta' => [],
                    ],
                ],
            ],
        ]);

        $this->actingAs($owner)
            ->get(route('workspaces.scripts.print', [$workspace, $script]))
            ->assertOk()
            ->assertSee('INT. BATHROOM, SAFE HOUSE - NIGHT')
            ->assertSee('MARA (V.O.)')
            ->assertSee('The job is already bad.')
            ->assertSee('CUT TO:');

        $this->actingAs($owner)
            ->get(route('workspaces.scripts.export.fountain', [$workspace, $script]))
            ->assertOk()
            ->assertSeeText('Title: Pilot')
            ->assertSeeText('INT. BATHROOM, SAFE HOUSE - NIGHT')
            ->assertSeeText('MARA (V.O.)')
            ->assertSee('> CUT TO:', false);
    }

    protected function createUser(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $user->forceFill([
            'current_team_id' => $team?->id,
        ])->save();

        return $user->fresh();
    }

    protected function createWorkspace(User $owner, bool $public = true): Workspace
    {
        return Workspace::create([
            'name' => 'Writers Room',
            'owner_id' => $owner->id,
            'is_public' => $public,
        ]);
    }

    protected function createScript(Workspace $workspace, User $author): ScriptProject
    {
        $script = ScriptProject::create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
            'title' => 'Pilot',
            'status' => 'draft',
            'document' => ScriptProject::defaultDocument(),
        ]);

        ScriptRevision::create([
            'script_project_id' => $script->id,
            'user_id' => $author->id,
            'title' => $script->title,
            'status' => $script->status,
            'logline' => null,
            'synopsis' => null,
            'document' => $script->document,
            'revision_number' => 1,
            'change_summary' => 'Initial draft',
        ]);

        return $script;
    }
}
