# theming Specification

## Purpose

Defines the colour themes the application offers and the visual guarantees each theme makes, so that accent colours and icon legibility stay consistent as the interface grows.

## Requirements

### Requirement: Light theme accent

The Light theme SHALL use the Dark theme's background colour, navy `#14121c`, as its accent colour, with white as the colour of content placed on the accent.

#### Scenario: Accent elements in Light

- **WHEN** the Light theme is shown
- **THEN** the wordmark, the selected theme-switcher button, the active navigation marker and the primary button are navy `#14121c`

#### Scenario: Content on the accent in Light

- **WHEN** the Light theme is shown and an icon or label sits on an accent-coloured background
- **THEN** that icon or label is white

### Requirement: System mode accent

System mode SHALL follow the operating system's colour scheme and use the accent of the theme it resolves to.

#### Scenario: Operating system is light

- **WHEN** System mode is selected and the operating system prefers a light colour scheme
- **THEN** the Light theme is shown with the navy accent

#### Scenario: Operating system is dark

- **WHEN** System mode is selected and the operating system prefers a dark colour scheme
- **THEN** the Dark theme is shown with its gold accent `#d8b25c`

### Requirement: Dark and Goblin accents

The Dark theme SHALL keep its gold accent `#d8b25c` and the Goblin theme SHALL keep its green accent `#34d36f`.

#### Scenario: Dark accent

- **WHEN** the Dark theme is shown
- **THEN** accent elements are gold `#d8b25c`

#### Scenario: Goblin accent

- **WHEN** the Goblin theme is shown
- **THEN** accent elements are green `#34d36f`

### Requirement: Inactive icon contrast in dark themes

In the Dark and Goblin themes, inactive navigation icons and unselected theme-switcher icons SHALL have a contrast ratio of at least 9:1 against the surface behind them.

#### Scenario: Navigation icon in Dark

- **WHEN** the Dark theme is shown and a navigation item is not the current page
- **THEN** its icon has a contrast ratio of at least 9:1 against the navigation surface

#### Scenario: Navigation icon in Goblin

- **WHEN** the Goblin theme is shown and a navigation item is not the current page
- **THEN** its icon has a contrast ratio of at least 9:1 against the navigation surface

#### Scenario: Unselected theme-switcher icon

- **WHEN** the Dark or Goblin theme is shown
- **THEN** each unselected theme-switcher icon has a contrast ratio of at least 9:1 against the switcher background

### Requirement: Icon brightening is limited to inactive icons in dark themes

The brighter icon colour SHALL NOT change text labels, the Light theme, or the colours of hovered, active and selected items.

#### Scenario: Label beside an inactive icon

- **WHEN** the Dark or Goblin theme is shown and a navigation item is not the current page
- **THEN** its text label keeps the theme's muted colour

#### Scenario: Light theme icons

- **WHEN** the Light theme is shown
- **THEN** inactive icons keep the Light theme's muted colour `#5e5873`

#### Scenario: Active and hovered items

- **WHEN** a navigation item is the current page or is hovered, or a theme-switcher button is selected
- **THEN** its icon has the same colour as before this change, matching its label or selected state
