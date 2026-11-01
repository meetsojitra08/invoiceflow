# UI Context

> Design and UX rules for InvoiceFlow. Stack: React 19 + TypeScript + Tailwind CSS 4 + shadcn/ui, rendered through Inertia. Keep it clean, fast, and business-like.

## Design Principles
1. **Clarity over decoration:** this is a financial tool; numbers must be easy to read
2. **Few clicks:** creating an invoice should take under 2 minutes
3. **Consistency:** same components for all lists, forms, and states
4. **Responsive:** works at 375px (mobile) to 1440px (desktop)
5. **Accessible:** keyboard navigable, labeled inputs, sufficient contrast (WCAG AA)

## Visual Tokens (Tailwind)

| Token | Value |
|-------|-------|
| Primary | `indigo-600` (hover `indigo-700`) |
| Success | `green-600` |
| Warning | `amber-500` |
| Danger | `red-600` |
| Neutral text | `gray-900` / `gray-600` / `gray-400` |
| Background | `gray-50`, cards `white` with `border-gray-200` |
| Font | Inter (system fallback), base 14 to 16px |
| Radius | `rounded-lg` cards, `rounded-md` inputs/buttons |
| Spacing | 4px scale; page padding `p-6`, card padding `p-4` or `p-6` |

## App Layout
- **Sidebar (left, collapsible on mobile):** Dashboard, Invoices, Clients, Products, Payments, Reports, Team, Settings
- **Top bar:** company name/switcher, search, user menu (profile, API tokens, logout)
- **Content:** page title, primary action button (top right), breadcrumbs for nested pages
- **Flash messages:** toast top right, auto-dismiss after 5s

## Pages

| Page | Route | Key elements |
|------|-------|--------------|
| Login / Register | `/login`, `/register` | Company name field on register |
| Dashboard | `/dashboard` | KPI cards, revenue chart, recent invoices, overdue list |
| Invoices list | `/invoices` | Table, status filter tabs, search, date range, pagination |
| Invoice create/edit | `/invoices/create` | React invoice builder (see below) |
| Invoice detail | `/invoices/{id}` | Summary, items, payments, timeline, action buttons |
| Clients list/detail | `/clients` | Table, detail with invoice history and balance |
| Products | `/products` | Table with price and tax |
| Payments | `/payments` | Table, filters |
| Reports | `/reports` | Revenue by month, outstanding, top clients, export button |
| Team | `/team` | Members table, invite modal, role select |
| Settings | `/settings` | Company profile, invoice prefix, reminders, currency |
| API Tokens | `/profile/tokens` | Create token (show once), revoke |
| Public invoice | `/i/{token}` (signed) | Clean invoice view, Pay now button, Download PDF |

## Reusable Components
Base primitives come from **shadcn/ui** in `resources/js/components/ui` (button, input, select, textarea, label, card, dialog, badge, table, dropdown-menu, tabs, skeleton, toast/sonner, alert-dialog, popover, calendar). Add them with the shadcn CLI as needed; do not rewrite them.

App-level components in `resources/js/components/shared`: `MoneyInput`, `Money` (display), `DataTable` (with pagination), `EmptyState`, `StatCard`, `StatusBadge`, `ConfirmDialog`, `PageHeader`, `DateRangePicker`, `ClientCombobox`, `ProductCombobox`.

## Status Badges

| Status | Color |
|--------|-------|
| Draft | gray |
| Sent | blue |
| Partially paid | amber |
| Paid | green |
| Overdue | red |
| Void | gray, line-through number |

## Invoice Builder (React)
- Header: client select (searchable), issue date, due date, invoice number (read-only)
- Items table: description, qty, unit price, tax %, discount, line total; add/remove rows; pick product to autofill
- Totals panel (sticky on desktop): subtotal, discount, tax, **total**
- Totals recalculate live: debounced (300ms) `POST /invoices/calculate`; the server runs `InvoiceCalculator`; the UI renders the returned values and shows a subtle loading state
- Item rows use stable IDs as React keys; `MoneyInput` converts typed amounts to minor units
- Buttons: **Save draft**, **Save and send**, Cancel
- Disabled/read-only when status is not draft

## Forms
- Labels above inputs; required marked with `*`
- Inline validation errors in red under the field
- Primary button right-aligned; destructive actions use confirm dialog
- Prevent double submit (`disabled={processing}` from Inertia's `useForm`)

## Tables
- Sticky header, zebra optional, row hover
- Numbers right-aligned, monospace/tabular figures (`tabular-nums`)
- Row actions in a kebab dropdown; bulk actions out of scope
- Always an empty state: icon, one-line message, primary action

## Money and Dates Display
- Money: formatted with currency symbol and locale (`₹1,23,456.00` for INR), from minor units via `MoneyFormatter`
- Dates: `d M Y` (e.g. `01 Nov 2026`); relative text for due dates ("due in 3 days", "5 days overdue")
- Time zone from company settings

## States
- **Loading:** `Skeleton` for deferred props; spinner/disabled state on buttons while `processing`
- **Empty:** helpful message with action
- **Error:** friendly text, retry; never show stack traces
- **Success:** toast

## Charts
- Recharts (`BarChart`, `PieChart`); one revenue-by-month bar chart and one status donut; wrap in `ResponsiveContainer`
- Colors from tokens; always show data labels or tooltips

## PDF Invoice Layout (DomPDF)
- A4, company logo/name top left, "INVOICE" and number top right
- Bill-to block, issue/due dates
- Items table, totals box right-aligned, notes and payment instructions footer
- Simple CSS only (DomPDF has limited flexbox/grid support): use tables and floats
- Status stamp (PAID / VOID) as faint watermark text

## Emails
- Plain, branded header, one clear call-to-action button ("View invoice")
- Include invoice number, total, due date; PDF attached
- Use Laravel Markdown Mailables

## AI UI
- "Create with AI" button on invoice page opens a modal with a text prompt
- Result displayed as an editable draft in the builder, labeled "AI draft: review before saving"
- Show loading state and a clear failure message with manual fallback

## Accessibility Checklist
- [ ] All inputs have labels
- [ ] Focus visible on interactive elements
- [ ] Color is never the only status indicator (badges include text)
- [ ] Modals trap focus and close on Esc
- [ ] Tables have proper headers; images have alt text

## Frontend Implementation Notes
- Tailwind CSS 4 is configured CSS-first in `resources/css/app.css`; define the color tokens above as CSS variables there so shadcn/ui components pick them up
- Icons: `lucide-react` (already used by the starter kit)
- Layouts in `resources/js/layouts`: `app-layout` (sidebar + topbar), `auth-layout`, `public-layout` (invoice pay page, no sidebar)
- Page titles via Inertia `<Head title="...">`
- Navigation via Inertia `<Link>`; preserve scroll and filters with `preserveState`/`preserveScroll` where useful
- Responsive: sidebar collapses to a sheet on mobile; tables scroll horizontally inside their container
- Dark mode: optional stretch; use token variables so it is easy to add later
- Keep components small; if a page file exceeds about 200 lines, extract components
