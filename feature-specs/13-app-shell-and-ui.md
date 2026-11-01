# Spec: App Shell and Shared UI

**Ticket:** IF-2

> **MVP scope:** All requirements except dark mode. Test plan: Pest only (skip Vitest).

## 1. Purpose
Give every page the same layout, navigation, feedback (toasts), and reusable building blocks so later features are fast and consistent.

## 2. User Stories
- As a user, I get a consistent sidebar/topbar layout that works on mobile and desktop.
- As a user, I see clear success and error messages after actions.
- As a developer, I reuse shared components instead of rebuilding tables, money inputs, and badges.

## 3. Functional Requirements
- FR-1: Layouts: `app-layout` (sidebar + topbar), `auth-layout`, `public-layout` (no sidebar, for the public invoice page)
- FR-2: Sidebar items per `../ai-context/ui-context.md`; items hidden when the role cannot access them
- FR-3: Topbar: company name (and switcher when 2+ companies), user menu (profile, API tokens, logout)
- FR-4: `HandleInertiaRequests` shares: `auth.user`, `auth.company`, `auth.role`, `flash.success|error`
- FR-5: Flash messages appear as toasts and auto-dismiss after 5 seconds
- FR-6: Shared components: `Money`, `MoneyInput`, `DataTable` (with pagination), `EmptyState`, `StatCard`, `StatusBadge`, `ConfirmDialog`, `PageHeader`
- FR-7: `lib/money.ts`: `formatMoney(minor, currency)` and `parseMoneyToMinor(input)`
- FR-8: `usePermissions` hook reading `can` flags from props
- FR-9: Clients pages refactored to use the shared components
- FR-10: Page titles set with Inertia `<Head>`

## 4. Business Rules
- No business rules in these components; they display data and emit events
- Money is never handled as floating point: parsing uses string/integer logic
- Shared props contain only what the UI needs (no secrets, no other tenants' data)
- Components are typed (TypeScript strict) and accessible (labels, focus, keyboard)

## 5. Data Model
None.

## 6. Permissions
Navigation visibility follows the role matrix; the server still enforces access.

## 7. UI / UX
Follow `../ai-context/ui-context.md`: tokens, spacing, status badge colors, empty states, loading skeletons. Sidebar becomes a sheet on small screens; tables scroll horizontally inside their container.

## 8. Edge Cases
- Inputs like `2,000.50`, `2000.5`, `.5`, `0`, empty, `abc` for `parseMoneyToMinor`
- Very large amounts and zero-decimal currencies
- Flash message shown twice after redirect/back navigation
- Long company names truncated in the topbar
- User with no company (fallback page)

## 9. Acceptance Criteria
- [ ] Layouts render on 375px and 1440px widths
- [ ] Toasts show for success and error flashes
- [ ] `formatMoney` and `parseMoneyToMinor` handle tricky inputs (`2,000.50`, `.5`, empty, invalid)
- [ ] Clients pages use shared components
- [ ] `npm run types` and `npm run build` pass

## 10. Test Plan
Pest: shared props contain the expected keys via `assertInertia`. Money helpers: verify by hand with the tricky inputs above.

## 11. Out of Scope
Dark mode (stretch), internationalization, custom theming UI.
