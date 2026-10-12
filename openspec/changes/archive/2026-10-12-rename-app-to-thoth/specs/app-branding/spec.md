# Spec Delta

## Purpose

Defines the name the application presents to its user, so that the page title, the wordmark and outgoing mail all identify the application consistently.

## ADDED Requirements

### Requirement: Application name

The application SHALL present itself under the name "Thoth". When no name is configured in the environment, the application SHALL still present itself as "Thoth".

#### Scenario: Name is configured

- **WHEN** the application runs with its standard environment configuration
- **THEN** the name it presents is "Thoth"

#### Scenario: Name is not configured

- **WHEN** the application runs with no application name set in the environment
- **THEN** the name it presents is "Thoth"

### Requirement: Page title

The browser page title SHALL be the application name on pages without their own title, and "<page title> - Thoth" on pages that have one.

#### Scenario: Page with its own title

- **WHEN** the user opens a page titled "Cards"
- **THEN** the browser title is "Cards - Thoth"

#### Scenario: Page without its own title

- **WHEN** the user opens a page that sets no title
- **THEN** the browser title is "Thoth"

### Requirement: Wordmark and compact mark

The layouts SHALL show the application name as the wordmark. The compact mark SHALL show the initial of the name and expose the full name as its accessible label.

#### Scenario: Full wordmark

- **WHEN** a layout shows the full wordmark
- **THEN** it reads "Thoth" as a single word in the accent colour

#### Scenario: Compact mark

- **WHEN** a layout shows the compact mark
- **THEN** it shows "T" and its accessible label is "Thoth"

### Requirement: Mail sender name

Mail sent by the application SHALL use the application name as the sender name unless a different sender name is configured.

#### Scenario: Default sender name

- **WHEN** the application sends mail and no separate sender name is configured
- **THEN** the sender name is "Thoth"
