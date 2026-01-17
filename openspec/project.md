# Project Context

## Purpose
Build a multi-jurisdiction tax assistant platform (Spain, USA, Colombia) that centralizes financial data, maps transactions to tax-relevant categories, and produces summaries, checklists, and exports for tax preparation without providing formal tax advice.

## Tech Stack
- Backend: Laravel 12, PHP 8.4.17, Laravel Fortify v1, Livewire v4, Flux UI Free v2
- Frontend: Blade + Livewire, Tailwind CSS v4, Vite 7, Axios
- Testing/Quality: Pest v4, PHPUnit v12, Laravel Pint
- Tooling/Local: Laravel Boost (MCP), Laravel Herd
- Data: MySQL 8 for deployment (per project plan)

## Project Conventions

### Code Style
- Format PHP with `vendor/bin/pint` (use Pint conventions)
- Use PHP 8 constructor property promotion; no empty public constructors
- Always use curly braces for control structures
- Explicit return types and parameter type hints everywhere
- Prefer PHPDoc blocks over inline comments; add array shapes when useful
- Enum keys in TitleCase
- Tailwind v4 CSS-first config (`@import "tailwindcss";` and `@theme`), no deprecated utilities

### Architecture Patterns
- Laravel 12 file structure: middleware configured in `bootstrap/app.php`
- Prefer Eloquent models and relationships; avoid `DB::` when possible
- Use Form Request classes for validation; include custom error messages
- Use queued jobs (`ShouldQueue`) for time-consuming work
- Livewire components for UI; state lives on server and UI reflects it
- Reuse existing components (Flux UI first, then Blade)

### Testing Strategy
- All tests written in Pest
- Write/update tests for changes; cover happy, failure, and edge cases
- Run minimal affected tests with `php artisan test --compact`
- Use factories for model setup

### Git Workflow
- Use a branch per proposed change (align with OpenSpec change IDs when possible)
- Open a PR for each change branch
- Keep commits small and focused; avoid amending unless requested

## Domain Context
- Single-user focused MVP for cross-border tax prep across Spain, USA, Colombia
- Key outputs: IRPF summaries (Spain), Form 5472/pro-forma 1120 and Schedule E summaries (US), Colombia rental summaries
- System is an organization/calculation helper, not tax advice

## Important Constraints
- Do not change dependencies without approval
- Stick to existing directory structure; no new base folders without approval
- Use Laravel Herd for local site access (no manual web server setup)
- Search docs via Boost `search-docs` before Laravel ecosystem changes

## External Dependencies
- YNAB API/CSV (planned)
- Mercury API/CSV (planned)
- Banco Santander (CSV; potential PSD2/open-banking later)
- Bancolombia (CSV; potential API later)
- OCR pipeline for documents (planned)
- Algolia for search (planned), with DB FTS fallback
