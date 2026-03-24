<?php

namespace Database\Seeders;

use App\Models\PageTemplate;
use Illuminate\Database\Seeder;

class PageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name'         => 'Game Design Document',
                'description'  => 'Comprehensive GDD template covering core loop, mechanics, progression, and more.',
                'content_type' => 'richtext',
                'category'     => 'game-design',
                'is_system'    => true,
                'content'      => <<<'HTML'
<h2>Overview</h2>
<p><em>A brief summary of the game — its concept, genre, and what makes it unique.</em></p>
<h3>Elevator Pitch</h3>
<p></p>
<h3>Genre &amp; Platform</h3>
<p></p>
<h3>Target Audience</h3>
<p></p>
<h3>Tone &amp; Mood</h3>
<p></p>

<h2>Core Gameplay Loop</h2>
<p><em>Describe the fundamental cycle of actions a player will repeat throughout the game.</em></p>
<p></p>

<h2>Mechanics</h2>
<h3>Player Controls</h3>
<p></p>
<h3>Core Systems</h3>
<p></p>
<h3>Feel &amp; Feedback</h3>
<p></p>

<h2>Progression</h2>
<h3>Player Progression</h3>
<p></p>
<h3>Level / World Progression</h3>
<p></p>
<h3>Difficulty Curve</h3>
<p></p>

<h2>Game World &amp; Setting</h2>
<p></p>

<h2>Characters</h2>
<h3>Player Character</h3>
<p></p>
<h3>Enemies</h3>
<p></p>
<h3>NPCs</h3>
<p></p>

<h2>Economy &amp; Resources</h2>
<p></p>

<h2>UI &amp; UX</h2>
<h3>HUD</h3>
<p></p>
<h3>Menus &amp; Screens</h3>
<p></p>

<h2>Audio Direction</h2>
<h3>Music</h3>
<p></p>
<h3>Sound Effects</h3>
<p></p>

<h2>Technical Requirements</h2>
<p></p>

<h2>Milestones</h2>
<p></p>
HTML,
            ],

            [
                'name'         => 'Art Bible',
                'description'  => 'Visual direction document covering style, color, character, and environment art guidelines.',
                'content_type' => 'richtext',
                'category'     => 'art',
                'is_system'    => true,
                'content'      => <<<'HTML'
<h2>Visual Identity</h2>
<p><em>Define the overall look and feel of the game — the emotional impression art should create.</em></p>
<h3>Style Reference</h3>
<p></p>
<h3>Mood &amp; Tone</h3>
<p></p>
<h3>Inspirations &amp; References</h3>
<p></p>

<h2>Color Palette</h2>
<p><em>Define the primary and secondary color palettes. Include hex codes where possible.</em></p>
<h3>Primary Palette</h3>
<p></p>
<h3>Secondary / Accent Palette</h3>
<p></p>
<h3>Environment-Specific Palettes</h3>
<p></p>

<h2>Typography</h2>
<h3>Fonts &amp; Lettering</h3>
<p></p>
<h3>UI Text Standards</h3>
<p></p>

<h2>Character Art Guidelines</h2>
<h3>Player Character</h3>
<p></p>
<h3>Enemy Design</h3>
<p></p>
<h3>NPC Design</h3>
<p></p>
<h3>Silhouette &amp; Readability</h3>
<p></p>

<h2>Environment Art</h2>
<h3>Tilesets &amp; Props</h3>
<p></p>
<h3>Backgrounds &amp; Parallax</h3>
<p></p>
<h3>Lighting &amp; Atmosphere</h3>
<p></p>

<h2>UI Art</h2>
<h3>HUD Elements</h3>
<p></p>
<h3>Menus &amp; Screens</h3>
<p></p>
<h3>Icons &amp; Buttons</h3>
<p></p>

<h2>Animation Guidelines</h2>
<h3>Frame Rates &amp; Style</h3>
<p></p>
<h3>Key Animation States</h3>
<p></p>

<h2>Technical Specifications</h2>
<h3>Sprite Sizes &amp; Formats</h3>
<p></p>
<h3>File Naming Conventions</h3>
<p></p>
<h3>Export Settings</h3>
<p></p>
HTML,
            ],

            [
                'name'         => 'Story Bible',
                'description'  => 'Narrative document covering world building, characters, themes, and story structure.',
                'content_type' => 'richtext',
                'category'     => 'story',
                'is_system'    => true,
                'content'      => <<<'HTML'
<h2>World Overview</h2>
<p><em>A high-level description of the game world — its setting, history, and defining characteristics.</em></p>
<h3>Setting</h3>
<p></p>
<h3>History &amp; Lore</h3>
<p></p>
<h3>Rules of the World</h3>
<p></p>
<h3>Tone &amp; Atmosphere</h3>
<p></p>

<h2>Themes</h2>
<p><em>The core ideas and messages explored through the narrative.</em></p>
<p></p>

<h2>Characters</h2>
<h3>Protagonist</h3>
<p><strong>Name:</strong><br><strong>Background:</strong><br><strong>Motivation:</strong><br><strong>Arc:</strong></p>
<h3>Antagonist</h3>
<p><strong>Name:</strong><br><strong>Background:</strong><br><strong>Motivation:</strong><br><strong>Arc:</strong></p>
<h3>Supporting Characters</h3>
<p></p>

<h2>Story Structure</h2>
<h3>Act 1 — Setup</h3>
<p></p>
<h3>Act 2 — Confrontation</h3>
<p></p>
<h3>Act 3 — Resolution</h3>
<p></p>
<h3>Key Story Beats</h3>
<p></p>

<h2>Dialogue Guidelines</h2>
<h3>Character Voice</h3>
<p></p>
<h3>Tone &amp; Style</h3>
<p></p>
<h3>Localization Notes</h3>
<p></p>

<h2>Factions &amp; Organizations</h2>
<p></p>

<h2>Notable Locations</h2>
<p></p>

<h2>Mythology &amp; In-World Lore</h2>
<p></p>
HTML,
            ],

            [
                'name'         => 'Level Design Document',
                'description'  => 'Template for documenting individual level layout, encounters, narrative moments, and art notes.',
                'content_type' => 'richtext',
                'category'     => 'level-design',
                'is_system'    => true,
                'content'      => <<<'HTML'
<h2>Level Overview</h2>
<p><strong>Level Name:</strong><br><strong>World / Zone:</strong><br><strong>Estimated Play Time:</strong></p>
<h3>Setting &amp; Environment</h3>
<p></p>
<h3>Design Goals</h3>
<p><em>What should the player learn, feel, or accomplish in this level?</em></p>
<p></p>

<h2>Layout &amp; Flow</h2>
<h3>Critical Path</h3>
<p></p>
<h3>Optional Areas &amp; Exploration</h3>
<p></p>
<h3>Pacing Notes</h3>
<p></p>

<h2>Gameplay Elements</h2>
<h3>Enemies &amp; Encounters</h3>
<p></p>
<h3>Puzzles &amp; Challenges</h3>
<p></p>
<h3>Hazards &amp; Obstacles</h3>
<p></p>
<h3>Collectibles &amp; Secrets</h3>
<p></p>

<h2>Checkpoints &amp; Progression</h2>
<p></p>

<h2>Narrative Moments</h2>
<h3>Cutscenes / Scripted Events</h3>
<p></p>
<h3>Environmental Storytelling</h3>
<p></p>

<h2>Art &amp; Visual Notes</h2>
<p></p>

<h2>Audio Cues</h2>
<h3>Music</h3>
<p></p>
<h3>Ambient &amp; SFX</h3>
<p></p>
HTML,
            ],

            [
                'name'         => 'Mechanics Reference',
                'description'  => 'Template for documenting a single game mechanic — how it works, edge cases, and balance notes.',
                'content_type' => 'richtext',
                'category'     => 'reference',
                'is_system'    => true,
                'content'      => <<<'HTML'
<h2>Mechanic Overview</h2>
<p><strong>Mechanic Name:</strong><br><strong>Category:</strong><br><strong>Status:</strong> Draft / In Development / Implemented / Needs Playtest</p>
<h3>Purpose</h3>
<p><em>What role does this mechanic play in the game? What player need does it address?</em></p>
<p></p>

<h2>How It Works</h2>
<h3>Player Input</h3>
<p></p>
<h3>System Response</h3>
<p></p>
<h3>Feedback &amp; Feel</h3>
<p></p>

<h2>Parameters &amp; Values</h2>
<p><em>List any tuneable values (e.g. speed, cooldown, damage range).</em></p>
<p></p>

<h2>Edge Cases</h2>
<p></p>

<h2>Balance Notes</h2>
<p></p>

<h2>Integration</h2>
<h3>Related Mechanics</h3>
<p></p>
<h3>Affected Systems</h3>
<p></p>

<h2>Playtesting Notes</h2>
<p></p>
HTML,
            ],
        ];

        foreach ($templates as $data) {
            PageTemplate::firstOrCreate(
                ['name' => $data['name'], 'is_system' => true],
                $data
            );
        }
    }
}
