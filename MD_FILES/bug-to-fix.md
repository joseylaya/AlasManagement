# Implement SPA-Like Navigation and iOS-Style Skeleton Loading

Improve the ALAS Business Manager so navigation feels fast and smooth like a React single-page application.

The application currently performs a full browser refresh when users open pages or click navigation links. Replace this behavior with SPA-like navigation where the main application layout remains mounted and only the page content changes.

## Main Objective

Create a seamless application experience with:

* No full-page refresh during normal internal navigation
* Persistent sidebar, top navigation, notification area, and user profile
* Dynamic replacement of the main content area
* Smooth page transitions
* iOS-inspired skeleton loaders while content is loading
* Preserved browser Back and Forward navigation
* Preserved URL updates for every page
* Direct URLs that still work after refreshing the browser
* Role-based menus and permissions that remain enforced

Do not rebuild the project in React.

Use the existing Laravel, Livewire, Blade, Alpine.js, and Tailwind CSS architecture wherever possible.

If the application uses Livewire 3, use `wire:navigate` and persistent layout components to provide SPA-like navigation.

---

# 1. SPA-Like Navigation

Update all internal application links to use SPA-style navigation.

For Livewire 3, internal links should use:

```blade
<a href="{{ route('orders.index') }}" wire:navigate>
    Orders
</a>
```

Apply SPA navigation to:

* Sidebar menu links
* Mobile navigation links
* Breadcrumb links
* Dashboard cards
* Table and card actions
* View Details links
* Back buttons
* Pagination links where supported
* Notification links
* Quick-action buttons
* Role-specific navigation items

Do not apply SPA navigation to:

* External websites
* File downloads
* Export actions
* Logout unless safely supported
* Links that intentionally open a new browser tab

The following parts of the interface must remain visible and should not reload during navigation:

* Desktop sidebar
* Mobile navigation
* Top navigation bar
* User profile menu
* Notification icon
* Main application shell

Only the page content inside the main content container should change.

Example structure:

```blade
<div class="min-h-screen bg-gray-50">
    <x-sidebar />

    <div class="main-layout">
        <x-top-navigation />

        <main id="page-content">
            {{ $slot }}
        </main>
    </div>
</div>
```

Use persistent components where appropriate:

```blade
@persist('sidebar')
    <x-sidebar />
@endpersist

@persist('top-navigation')
    <x-top-navigation />
@endpersist
```

Avoid duplicating persistent elements across pages.

---

# 2. Page Loading State

Whenever the user navigates to another internal page, immediately show a skeleton loader inside the main content area.

Do not show:

* A blank white page
* A blocking full-screen spinner
* A browser refresh flash
* A small spinner with no indication of the incoming layout
* A loader that hides the sidebar and navigation

The skeleton should appear only in the content container while the application shell remains visible.

The loader should feel similar to native iOS loading placeholders:

* Soft neutral gray placeholders
* Rounded corners
* Gentle shimmer animation
* Smooth fade-in and fade-out
* No aggressive flashing
* No unnecessary bright colors
* Layout-aware placeholder shapes
* Respect reduced-motion accessibility settings

The skeleton should approximately match the structure of the page being loaded.

---

# 3. Skeleton Loader Components

Create reusable skeleton components rather than writing separate loader markup on every page.

Recommended components:

```text
components/
    skeleton/
        page.blade.php
        dashboard.blade.php
        table.blade.php
        mobile-card-list.blade.php
        form.blade.php
        details.blade.php
        finance-list.blade.php
```

Example base skeleton element:

```blade
<div
    {{ $attributes->merge([
        'class' => 'animate-skeleton rounded-lg bg-gray-200'
    ]) }}
></div>
```

Example shimmer animation:

```css
@keyframes skeleton-shimmer {
    0% {
        background-position: 200% 0;
    }

    100% {
        background-position: -200% 0;
    }
}

.animate-skeleton {
    background: linear-gradient(
        90deg,
        rgb(229 231 235) 25%,
        rgb(243 244 246) 50%,
        rgb(229 231 235) 75%
    );
    background-size: 200% 100%;
    animation: skeleton-shimmer 1.4s ease-in-out infinite;
}

@media (prefers-reduced-motion: reduce) {
    .animate-skeleton {
        animation: none;
    }
}
```

Keep the animation subtle and professional.

---

# 4. Page-Specific Skeleton Layouts

## Dashboard Skeleton

Display placeholders for:

* Page title
* Summary cards
* Statistics
* Recent orders
* Low-stock section
* Finance overview
* Recent activity

The skeleton must adapt based on the logged-in role:

### Owner

Include placeholders for:

* Complete business overview
* Revenue and expenses
* Orders
* Inventory
* Cash position
* Staff activity
* Reports

### Manager

Include placeholders for:

* Orders requiring approval
* Pending fulfillment
* Low-stock products
* Expenses
* Cash position
* Staff activity
* Notifications requiring action

### Staff

Include placeholders for:

* Create Order action
* Recent orders
* Assigned notifications
* Order statuses
* Basic read-only finance summary

Do not expose owner-only skeleton sections to Manager or Staff users.

## Desktop Table Skeleton

Display:

* Header placeholders
* Five to eight placeholder rows
* Status badge placeholders
* Action button placeholders
* Pagination placeholder

## Mobile List Skeleton

Below 768px, do not display a compressed desktop table skeleton.

Display stacked card skeletons containing:

* Primary identifier
* Main description
* Amount or status
* Metadata rows
* Badge placeholders
* Primary action placeholder

This should match the mobile card presentation required by the application design.

## Form Skeleton

Display:

* Page title
* Section headings
* Input placeholders
* Select placeholders
* Summary card
* Sticky bottom action area

Use a single-column layout on mobile.

## Details Skeleton

Display:

* Record title
* Status badges
* Summary information
* Timeline
* Item list
* Available actions

Only display action placeholders that the current role is authorized to use.

---

# 5. Livewire Navigation Loading Behavior

Create a global navigation loading state.

Listen for Livewire navigation events:

```javascript
document.addEventListener('livewire:navigating', () => {
    document.documentElement.classList.add('is-navigating');
});

document.addEventListener('livewire:navigated', () => {
    document.documentElement.classList.remove('is-navigating');
});
```

Show the skeleton immediately when navigation starts.

Hide it only after the new page content has been mounted.

Example behavior:

```text
User clicks Orders
        ↓
URL changes
        ↓
Main content begins transition
        ↓
Orders skeleton appears
        ↓
Orders data and components load
        ↓
Skeleton fades out
        ↓
Orders content fades in
```

Prevent content from jumping during the transition.

Use a minimum skeleton display duration only when necessary to prevent flickering, but do not intentionally slow down fast pages.

---

# 6. Livewire Component Loading States

For actions that update only part of the current page, use scoped `wire:loading` states.

Examples include:

* Searching
* Filtering
* Pagination
* Approving an order
* Rejecting an order
* Updating fulfillment status
* Loading order details
* Fetching financial records
* Updating inventory
* Saving a form

Example:

```blade
<div wire:loading wire:target="search,filters">
    <x-skeleton.mobile-card-list />
</div>

<div wire:loading.remove wire:target="search,filters">
    <!-- Actual content -->
</div>
```

Do not replace the entire page when only one section is loading.

For example:

* Filtering orders should replace only the order list.
* Loading finance transactions should replace only the finance list.
* Updating a dashboard widget should replace only that widget.
* Approving an order should disable only the affected controls and update the relevant order card.

---

# 7. Transition Behavior

Use subtle transitions when changing content.

Recommended behavior:

```css
.page-content-enter {
    opacity: 0;
    transform: translateY(4px);
}

.page-content-enter-active {
    opacity: 1;
    transform: translateY(0);
    transition:
        opacity 180ms ease,
        transform 180ms ease;
}
```

Do not use:

* Large sliding animations
* Bouncing effects
* Long transitions
* Page-flip effects
* Animations that delay interaction
* Animations on every small element

The application should feel responsive, stable, and professional.

---

# 8. Scroll Behavior

When navigating to a different page:

* Scroll the main content to the top
* Keep the sidebar in its current state
* Keep desktop sidebar scroll position when appropriate
* Close mobile navigation drawers
* Close open dropdowns and temporary overlays
* Preserve filter state only when intentionally supported
* Restore browser history correctly when using Back or Forward

For pagination and filters, avoid scrolling the entire page when only a list is updated.

---

# 9. Mobile Requirements

For screens below 768px:

* Keep the mobile navigation persistent
* Replace large tables with stacked cards or compact rows
* Avoid horizontal scrolling
* Show skeleton cards instead of table rows
* Keep touch targets at least 44px high
* Use full-width search and filter controls
* Keep primary actions easy to reach
* Use sticky bottom actions for long forms
* Close the navigation drawer after selecting a page
* Maintain safe spacing around fixed bottom navigation

The loading experience must work correctly on:

* Small mobile screens
* Large mobile screens
* Tablets
* Desktop screens

---

# 10. Role-Based Navigation and Security

SPA navigation must not weaken role-based access control.

The interface and backend permissions must continue to follow the same rules.

## Owner

The Owner may access all modules and actions.

## Manager

The Manager may access operational, inventory, order approval, expense, cash, and permitted financial functions, but cannot access protected Owner controls.

## Staff

The Staff may create orders, view orders, view activity logs, view products and inventory availability, and open permitted Finance pages in read-only mode.

Staff cannot:

* Approve or reject orders
* Modify protected financial transactions
* Adjust inventory
* Change product prices
* Manage users or roles
* Access Owner-only information
* Perform protected administrative actions

Do not rely only on hiding menu items.

Every page and action must still be protected using:

* Laravel middleware
* Policies
* Gates
* Permission checks
* Livewire action authorization
* Server-side validation

Unauthorized pages must return a proper permission-denied state even when accessed directly through the URL.

---

# 11. Accessibility

Ensure the loading behavior is accessible.

Add:

```html
aria-busy="true"
```

to the content container while loading.

Use appropriate screen-reader text:

```html
<span class="sr-only">Loading page content</span>
```

Additional requirements:

* Do not rely only on animation to communicate loading
* Respect `prefers-reduced-motion`
* Maintain accessible contrast
* Do not trap keyboard focus
* Preserve logical focus after navigation
* Keep buttons disabled while their action is processing
* Prevent duplicate submissions

---

# 12. Performance Requirements

Avoid introducing unnecessary JavaScript or heavy dependencies.

Prioritize:

* Existing Livewire capabilities
* Blade components
* Alpine.js only where necessary
* Reusable Tailwind classes
* Lazy loading of expensive components
* Pagination for large datasets
* Debounced search
* Efficient database queries
* Avoiding duplicate API or database requests
* Avoiding repeated mounting of persistent navigation components

Do not migrate the entire project to React solely to achieve SPA behavior.

---

# 13. Error Handling

If page navigation fails:

* Remove the skeleton loader
* Keep the application shell visible
* Show a clear error state inside the main content area
* Provide a Retry action
* Preserve the current URL where possible
* Log the technical error
* Do not leave the interface permanently loading

Example message:

```text
We could not load this page.

Please check your connection and try again.

[Try Again]
```

If the user loses authorization during navigation, show the permission-denied state instead of a generic loading error.

---

# 14. Acceptance Criteria

The implementation is complete when:

1. Internal navigation no longer causes a visible full-page browser refresh.
2. The sidebar, top navigation, notifications, and user profile remain mounted.
3. Only the main content container changes between pages.
4. URLs update correctly.
5. Browser Back and Forward buttons work.
6. Direct page URLs still work after browser refresh.
7. An iOS-style skeleton appears during navigation.
8. Skeleton layouts match the type of page being loaded.
9. Mobile pages use card skeletons instead of compressed tables.
10. Search, filters, and pagination use localized loading states.
11. Loading states do not expose unauthorized information or actions.
12. Owner, Manager, and Staff permissions remain enforced on the backend.
13. Navigation works on desktop, tablet, and mobile.
14. Failed navigation displays a recoverable error state.
15. The existing modern white theme, rounded cards, soft shadows, typography, spacing, and overall design identity are preserved.
16. The implementation does not require rebuilding the project in React.
17. There are no duplicate clicks, duplicate submissions, or permanently stuck loaders.
18. Existing forms, modals, validation, notifications, and role workflows continue to work.

Before completing the implementation, test all major pages under the Owner, Manager, and Staff accounts.
