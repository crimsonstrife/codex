# Codex

Codex is a collaborative documentation and planning app built with Laravel 12. It is designed as a Confluence-style knowledge base with nested pages, workspace-level organization, diagram support, and Forge-aware integrations.

The current codebase already includes workspace management, rich page editing, revision history, comments, notifications, exports, search, diagrams, and an admin panel.

## Feature Highlights

### Workspaces

- Public or private workspaces with owner/member access rules
- Workspace branding with name, description, color, and icon
- Optional workspace home page
- Workspace member management with `member`, `editor`, and `admin` roles
- Email or link-based workspace invitations
- Pinned pages and recent activity on the workspace hub
- Workspace analytics dashboard
- Workspace link graph for internal page relationships
- Offline-friendly workspace ZIP export
- Optional Forge project mapping via `forge_project_id` and `forge_project_key`

### Pages

- Nested page trees powered by `kalnoy/nestedset`
- Markdown and rich-text page authoring
- Draft, published, and archived page states
- Categories, tags, reading-time estimate, and sub-pages
- Page templates, including seeded system templates plus workspace templates
- Revision history with change summaries
- Revision diff and restore flows
- Page duplication, move, and cross-workspace transfer
- Soft deletes
- Edit locking with heartbeat + take-over flow
- Print view and Markdown export with front matter
- Attachments and inline editor image uploads

### Collaboration

- Starred pages dashboard
- Recently viewed pages
- Page comments with one level of threaded replies
- Resolve/unresolve comment workflow
- Page watching with in-app notifications
- Optional email notifications for watched page updates
- `@mentions` in rich-text content and comments
- Notification center with mark-all-read support

### Linking, Search, and Discovery

- Wiki-style links with `[[Page Title]]` syntax
- Wiki-link autocomplete in the rich-text editor
- Stored backlink relationships for "what links here" views
- Broken-link detection for unresolved wiki links
- Rich-text callout blocks
- Markdown callout/admonition blocks using `:::type ... :::`
- Global search across pages and diagrams
- Search filters for workspace, status, tag, author, date range, and title-only scope
- Laravel Scout search with the `database` driver by default and an easy upgrade path to Meilisearch, Algolia, or Typesense

### Diagrams

- Native Mermaid-based diagram editor and renderer
- Native Mermaid mind map and flowchart modes
- Optional draw.io / diagrams.net visual editor support
- Diagram publishing, tagging, and categorization

### Platform Features

- Registration, login, password reset, and email verification
- Two-factor authentication
- Profile photos and profile management
- Browser session management
- Jetstream teams and personal API tokens
- Filament admin panel at `/admin` for authorized users
- App-level bearer tokens for machine-to-machine integrations
- Forge SSO via Socialite

## Editor Conventions

Markdown pages support wiki links and callouts. Example:

```md
[[Deployment Checklist]]

:::warning
Rotate the shared secret before production.
:::
```

Rich-text pages expose the same collaboration features through TinyMCE toolbar actions for wiki links, mentions, callouts, tables, images, and formatting.

## Tech Stack

- PHP 8.3
- Laravel 12
- Laravel Jetstream + Fortify + Sanctum
- Livewire 3
- Filament 4
- Bootstrap 5 + Vite
- TinyMCE for rich-text editing
- Mermaid for native diagrams
- draw.io / diagrams.net embedding for visual diagrams
- Laravel Scout for search
- Spatie packages for activity logs, markdown, media library, permissions, settings, slugs, and tags

## Local Development

### Prerequisites

- PHP 8.3+
- Composer
- Node.js + npm
- A supported database

The default `.env.example` uses SQLite, which is the easiest way to get started locally.

### Quick Start

1. Copy the environment file:

   ```bash
   cp .env.example .env
   ```

2. If you are using the default SQLite configuration, create the database file first:

   ```bash
   touch database/database.sqlite
   ```

3. Install dependencies:

   ```bash
   composer install
   npm install
   ```

4. Generate the app key, run migrations, seed roles/templates, and expose public storage:

   ```bash
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   ```

5. Start the local development stack:

   ```bash
   composer run dev
   ```

`composer run dev` starts the Laravel server, the queue listener, log tailing, and Vite together. The queue worker matters because page update emails and invitation emails are queued.

### Useful Commands

```bash
composer run setup      # install deps, copy env if needed, generate key, migrate, build assets
composer run dev        # app server + queue worker + logs + Vite
composer test           # run the PHPUnit suite
php artisan migrate --seed
php artisan storage:link
php artisan app-token:create "Forge"
```

### Seeded Local User

When you run `php artisan db:seed` in a local environment, the app creates a default user:

- Email: `test@example.com`
- Password: `password`

The seeders also create permissions, roles, and system page templates.

## Integration Notes

### Forge SSO and Forge Project Linking

Forge login is optional. To enable it, configure the Forge values in your environment:

- `FORGE_ENABLED`
- `FORGE_URL`
- `FORGE_CLIENT_ID`
- `FORGE_CLIENT_SECRET`
- `FORGE_REDIRECT_URI`

If you want Codex to fetch Forge projects for workspace mapping, also add:

- `FORGE_M2M_CLIENT_ID`
- `FORGE_M2M_CLIENT_SECRET`

### Search Backends

The app ships with Scout's `database` driver by default, so search works without extra infrastructure. If you want a dedicated search engine, set `SCOUT_DRIVER` to `meilisearch`, `algolia`, or `typesense` and provide the matching credentials.

### Diagrams

Native Mermaid diagrams work in-browser with no external service. draw.io support uses the configurable `DRAWIO_URL` value and expects the target diagrams.net instance to be reachable from the browser.

### Mail and Notifications

Workspace invitations and watched-page update emails use Laravel's mail and queue systems. The default `.env.example` logs mail locally, but real environments should set a production mailer and run a queue worker.

## Integration API

Codex exposes machine-to-machine endpoints protected by app-level bearer tokens:

- `GET /api/v1/workspaces`
- `GET /api/v1/pages/search`

Generate an app token with:

```bash
php artisan app-token:create "Forge"
```

The UI also uses internal JSON endpoints for wiki-link autocomplete, mentions, and notifications.

## Testing

The repository includes PHPUnit feature coverage for authentication, profile management, teams, browser sessions, API tokens, and Forge-related API behavior.
