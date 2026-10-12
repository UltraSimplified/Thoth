# Thoth: Specification

## 1. Overview

A single-user Laravel application for logging a daily tarot card draw from the Crowley Thoth deck. Each day records the card drawn (plus any jumpers and clarifiers), an optional question, a one-line note of an event to observe, how the day played out, and a link to that day's 750words.com journal entry. The main purpose is long-term pattern analysis: suit, element and astrological counts, card frequency, position on the Tree of Life, and recurring themes across weeks, months, seasons and years.

The app runs as a web app (Laravel + Vue 3 via Inertia) and is packaged for Android with NativePHP Mobile.

### Goals

- Log a daily reading in under 30 seconds.
- Show the card image and its place on the Tree of Life, and link out to the matching reading on thothreadings.com.
- Link every day to its 750words.com stats page.
- Bring in the existing 2026 reading history from the start.
- Provide rich, trustworthy statistics over time.

---

## 2. Tech Stack

| Concern  | Choice                                                                                                            |
| -------- | ----------------------------------------------------------------------------------------------------------------- |
| Backend  | Laravel (current stable), PHP 8.3+                                                                                |
| Frontend | Vue 3 (Composition API, `<script setup>`) with Inertia.js and TypeScript, from Laravel's official Vue starter kit |
| Styling  | Tailwind CSS                                                                                                      |
| Charts   | Apache ECharts via `vue-echarts` (handles the bar, radar and heat map charts the statistics need)                 |
| Database | SQLite on both web and device                                                                                     |
| Auth     | Single user. Registration disabled after the first user is created                                                |
| Mobile   | NativePHP Mobile (Android target)                                                                                 |
| Testing  | Pest                                                                                                              |

**Timezone and day boundary:** Europe/London, stored in config (`APP_TIMEZONE`). All "day" logic uses the local date, not UTC. The day ends at midnight: a reading logged at 00:01 belongs to the new day. The date is fixed when the reading form opens and shown on the form, so a form opened at 23:58 and saved at 00:02 keeps the earlier date.

**Reversals:** not recorded. All cards are read upright, following the Thoth convention.

---

## 3. Core Concepts

- **Day**: one calendar date (local time). Holds the event note, the outcome note, the resonance answer, the themes and the 750words link. A day can exist without a card (a note-only day).
- **Reading**: the main card drawn on a day, with an optional question and interpretation notes. One per day.
- **Jumper**: a card that jumps out of the deck while shuffling, before the main card is picked. A reading can have any number of jumpers, kept in the order they appeared. Jumpers are recorded separately from clarifiers because they come before the draw rather than qualifying it.
- **Clarifier**: an extra card drawn on the same day to clarify the main card. A reading can have any number of clarifiers, kept in the order drawn.
- **Card**: one of the 78 cards of the Thoth deck, seeded once with its full attributions (section 3.1).
- **Theme**: a free-form tag describing the broad theme of a day, added in hindsight (section 4.7). For example, a day about money can be tagged "money".
- **Birth cards**: your personal significators calculated from your date of birth, set once in settings (section 4.4).

### 3.1 Card attributions

Every card is seeded with:

- Thoth name and title (e.g. Seven of Disks, "Failure"), arcana, suit, rank and number
- Element, and for court cards the sub-element (the Knight of Cups is Fire of Water)
- **Astrology:**
    - Majors: Hebrew letter and its sign, planet or element (Art is Samekh, Sagittarius)
    - Pips Two to Ten: planet, sign and decan (the Ten of Disks is Mercury in Virgo, third decan)
    - Knights, Queens and Princes: the span of the zodiac they rule
    - Aces and Princesses: the quadrant of the heavens they rule (no decan)
- Tree of Life position (section 4.3)
- thothreadings.com URL and image path

---

## 4. Features

Features are listed in the order they should be built. Each one relies only on those above it, so the app is usable, and testable against real data, at every step.

### 4.1 Appearance and app shell

Built first because every later screen uses its colour tokens and layout.

- Layout shell: bottom tab bar (Today, Calendar, Stats, Cards) on phone and a sidebar on desktop, plus the settings page that later features add to.
- Three themes: **Light**, **Dark** and **Goblin**.
- Goblin mode is dark mode in shades of green: deep forest-green background, slightly lighter green panels, soft green text and a bright leaf-green accent, matching the Goblin theme in the Good on Paper admin. Its icon is a leaf.
- The theme chooser offers **System**, Light, Dark and Goblin. System follows the phone (or browser), switching between Light and Dark with the Android system theme.
- All colours are semantic tokens (`--surface`, `--ink`, `--line`, `--accent` and suit colours) defined per theme, so components never reference raw colours.
- Suit colours are adjusted per theme so they stay distinguishable and meet contrast requirements against each background.

### 4.2 Card pages and images

Built straight after the shell because it needs only the seeded deck, and it proves the attribution data is correct before anything depends on it.

- A card index of all 78 cards, grouped by suit.
- Each card has a detail page with its image, title, attributions and a link to its reading on thothreadings.com.
- **My notes:** your own summary of what the card means to you. There is one note per card, attached to the card itself rather than to any day.
    - It appears as a textarea about three lines deep, directly above the thothreadings link, **wherever the card is shown**: the card page, the Today screen and every day page where it is the main card.
    - Editing it in any of those places updates the one note, so the change shows on every day the card was or will be drawn.
    - Jumpers and clarifiers show their card's note too, collapsed to one line until tapped.
    - It saves automatically as you type and can be edited at any time; the day lock in 4.8 doesn't apply to it.
    - Notes are included in search, export and sync.
- Once readings exist, the page also lists every date the card appeared (as main card, jumper or clarifier) with that day's question, event note and journal link.
- Card images as described in section 5.
- v1 links out to thothreadings.com only. Excerpts are an optional later feature behind a setting, off by default (see section 8).

### 4.3 Tree of Life

Built next so the card pages can show it, and so it's ready for the Today screen.

Every card is shown in its place on the Qabalistic Tree of Life, drawn as an SVG with the ten sephiroth (Daath shown dashed, never highlighted) and the 22 paths.

**Placement rules**

- **Major Arcana → paths 11–32**, by Hebrew letter. The path is highlighted, with its number and letter labelled.
- **Aces to Tens → sephiroth 1–10** (Ace = Kether through Ten = Malkuth).
- **Court cards → sephiroth:** Knight = Chokmah, Queen = Binah, Prince = Tiphareth, Princess = Malkuth.
- **Suit → Qabalistic world**, shown as a label: Wands = Atziluth, Cups = Briah, Swords = Yetzirah, Disks = Assiah.

**Path attributions (Thoth)**

| Path | Letter | Card           | Connects          |
| ---- | ------ | -------------- | ----------------- |
| 11   | Aleph  | The Fool       | Kether–Chokmah    |
| 12   | Beth   | The Magus      | Kether–Binah      |
| 13   | Gimel  | The Priestess  | Kether–Tiphareth  |
| 14   | Daleth | The Empress    | Chokmah–Binah     |
| 15   | Heh    | The Star       | Chokmah–Tiphareth |
| 16   | Vav    | The Hierophant | Chokmah–Chesed    |
| 17   | Zain   | The Lovers     | Binah–Tiphareth   |
| 18   | Cheth  | The Chariot    | Binah–Geburah     |
| 19   | Teth   | Lust           | Chesed–Geburah    |
| 20   | Yod    | The Hermit     | Chesed–Tiphareth  |
| 21   | Kaph   | Fortune        | Chesed–Netzach    |
| 22   | Lamed  | Adjustment     | Geburah–Tiphareth |
| 23   | Mem    | The Hanged Man | Geburah–Hod       |
| 24   | Nun    | Death          | Tiphareth–Netzach |
| 25   | Samekh | Art            | Tiphareth–Yesod   |
| 26   | Ayin   | The Devil      | Tiphareth–Hod     |
| 27   | Peh    | The Tower      | Netzach–Hod       |
| 28   | Tzaddi | The Emperor    | Netzach–Yesod     |
| 29   | Qoph   | The Moon       | Netzach–Malkuth   |
| 30   | Resh   | The Sun        | Hod–Yesod         |
| 31   | Shin   | The Aeon       | Hod–Malkuth       |
| 32   | Tav    | The Universe   | Yesod–Malkuth     |

Crowley's exchange of The Emperor (Tzaddi) and The Star (Heh) is the default. A setting switches to the Golden Dawn placement (Emperor on path 15, Star on path 28) for comparison.

**Where it appears**

- **Card page:** a full-size tree. Tapping a sephira or path shows its name, number, letter and every card that sits there (for example, Binah holds the four Threes and the four Queens).
- **Today screen and day pages:** a compact tree beside the card with the main card's position lit. Jumpers and clarifiers are shown as fainter highlights in two distinct styles.
- **Text key:** beneath the tree on the Today screen, day pages and card page, one line per highlighted element. Variable elements come first (the day's cards, or "This card" on a card page), then a "Fixed" block with the six birth positions. Each line gives the card, its position, and the Hebrew name of the sephira or path in italics. Hovering a point or path on the tree lights its line in the key.
- **Statistics:** a tree heat map (see 6.2).
- **Birth cards:** the positions of all your birth cards are permanently outlined on every tree (section 4.4).

**Implementation**

- Placement data lives in the deck seeder JSON: each card has a `tree` entry of either `{ "type": "path", "number": 15, "letter": "Heh", "from": "chokmah", "to": "tiphareth" }` or `{ "type": "sephira", "sephira": "binah" }`.
- `Sephira` is a PHP backed enum with number, name, pillar and triad, exported to TypeScript.
- A single Vue `TreeOfLife.vue` component renders the SVG from a fixed coordinate map. It takes props for highlighted nodes and paths, faint highlights for jumpers and clarifiers, outlined birth positions, an optional heat value per node and path, and a size.
- Every node and path has an accessible label, and the heat map has a table alternative.
- The text key is `TreeSummary.vue`. `TreeOfLife.vue` emits the key of the point or path under the pointer or keyboard focus (for example `binah` or `path-15`), and the key lights each matching line.

### 4.4 Birth cards

A small addition to settings, the card pages and the tree, built while those are fresh.
Set once in settings, each with its role. For a birth date of 28 October 1970:

| Role         | Card                                      | Basis                                                        | Tree position       |
| ------------ | ----------------------------------------- | ------------------------------------------------------------ | ------------------- |
| Birth Atu    | The Magus (I), Fortune (X), The Sun (XIX) | 28 + 10 + 1970 = 2008 → 10 → 1, in the 19–10–1 constellation | Paths 12, 21 and 30 |
| Decan card   | Five of Cups (Disappointment)             | Sun at about 4–5° Scorpio: first decan, Mars in Scorpio      | Geburah             |
| Sun-sign Atu | Death (XIII)                              | Atu of Scorpio                                               | Path 24             |
| Court card   | Prince of Cups                            | Rules 21° Libra to 20° Scorpio                               | Tiphareth           |

- A small badge marks a birth card wherever it appears: card pages now, and Today, calendar and lists as they are built. Hovering or tapping the badge names its role.
- All six Tree positions are outlined on every tree graphic.
- Statistics include a "birth cards drawn" count for the period, broken down by role.

### 4.5 Journal link

A small helper, built before the Today screen that first uses it.

- Every day (Today, day pages, calendar and lists) shows a 750words link built from the date: `https://750words.com/account/stats/YYYY-MM-DD`.
- First-run setup reminds you to confirm your 750words account timezone is set to London, so 750words' days line up with the app's.

### 4.6 Today screen: logging a reading

The core daily flow, built on the card picker, the tree and the journal link.

**Morning, before a card is logged:** the date and a "Log today's card" form:

- Question (optional, asked before drawing)
- "Add jumper" for any card that jumps out while shuffling, using the same picker as the main card
- Card picker: tabs for Majors, Wands, Cups, Swords and Disks showing card thumbnails, with a search box above them that matches names, titles and numbers ("aeon", "5 wands", "prudence")
- Once a card is chosen, a link to its thothreadings reading appears under it
- "Add clarifier" to pick further cards, each through the same picker
- Event to observe (single line, optional, max 255 chars)
- Interpretation notes (optional)

**After saving:** the card image, name, title, suit and element, the Tree of Life with the card's position lit, jumpers above the main card and clarifiers beneath it as thumbnails, each of which opens its card full screen when tapped so the detail can be read (on phones the main card fills most of the width), your notes on the card (4.2), the thothreadings button and the 750words button, the question and the event note.

**No draw today:** the event note and journal link can be filled in without a card.

Each day also has its own page (`/days/{date}`) showing the same content, used by the calendar and lists later.

### 4.7 Evening section: outcomes, resonance and themes

Built once logging works, since it completes the same day.

- On Today and on every day's page:
    - How it played out (single line, optional)
    - Did it resonate? Yes / No (optional)
    - Themes (multi-select tags with create-on-the-fly)
- The outcome note, resonance answer and themes can be filled in or changed on any day, at any time. They are deliberately left off the morning form so they describe what happened rather than what was expected.
- A "Notes" page lists every event note with its card, outcome and resonance.

### 4.8 Editing and locking

Built after both halves of the day exist, so the rules can cover every field.

- The morning fields (the reading, its jumpers, clarifiers and event note) can be edited for 7 days. At midnight at the end of the seventh day after its date, they become read-only.
- The evening fields (outcome, resonance and themes, section 4.7) have no lock and can be edited on any day.
- Locked morning fields show a small lock icon. There is no unlock in the UI; corrections to locked days are done through the importer's review screen or directly in the database.

### 4.9 Import of existing readings

Built once the data model, jumpers, clarifiers and locking are settled, so imported days behave exactly like logged ones. Importing here also gives the calendar and statistics real data to be built against.

A paste-in importer for the format of the existing reading list:

```
* FEBRUARY (4 Cups, 10 Disks, ...) = DISKS
   * XVI — The Tower / Knight of Disks — 05/02
   * EIGHT of WANDS — Swiftness 08/02
   * SIX of WANDS — Victory — 3 Jun
      * ATU XX - The Aeon
```

**Parsing rules:**

- Month header lines are recognised and their hand tallies ignored (the app recalculates them).
- Dates in `dd/mm`, `dd/m`, `d Mon` and `d Month` forms; the year is chosen on the import screen (default 2026).
- Cards matched by suit and number, accepting digits, words and Roman numerals, "ATU" prefixes and common misspellings ("Aoen", "Heirophant").
- Indented cards beneath a day become that day's clarifiers. Two cards on one line separated by "/" become a main card and a clarifier. On the review screen any extra card can be switched between clarifier and jumper.
- Text in brackets or after the title that isn't the title (e.g. "Air unchallenged", "0 = 2") goes into the interpretation notes.

**Review screen:** every parsed day is listed before anything is saved, with problems highlighted:

- Title doesn't match the card (e.g. Seven of Disks "Success", Atu XVI "The Universe")
- Atu number and name disagree
- Card with no date
- Date with no card (saved as a note-only day if confirmed)
- Unrecognised line

Each flagged line can be corrected or confirmed. Imported days arrive already locked where they are older than 7 days.

### 4.10 Calendar and history

Built after the import so it can be designed and tested against seven months of real days.

- **Month grid:** each day shows a small card thumbnail (on phones as well as wider screens) with a top border tinted by suit, dots for jumpers and clarifiers, and a marker for note-only days. Empty future days show a faded card-back. Tapping a day opens it, or opens the reading form with that date filled in if the day is empty.
- **List:** reverse-chronological days with card, question and event note, with filters for date range, suit, arcana, element, planet, sign, theme and resonance, and free-text search across questions, notes and interpretations.
- A switch at the top toggles between grid and list, and the app remembers the last one used.

### 4.11 Export

Built last in the core log, once every kind of data it covers exists.

- CSV and JSON export of days, readings, jumpers, clarifiers, themes and card notes.
- JSON import for restore from a backup.

---

## 5. Card Images

- 78 images stored under `storage/app/public/cards/` (or `resources/images/cards/` for bundling into the mobile app) and named by slug, e.g. `the-aeon.webp`.
- Provide thumbnail (about 200px wide) and full (about 800px wide) sizes, in WebP.
- An artisan command `cards:import-images {dir}` crops and resizes your scans and names them by slug.
- **Source:** the Thoth deck artwork (Lady Frieda Harris) is under copyright. Use scans of your own physical deck for personal use and do not publish or redistribute the app or its images. A card-back placeholder is used when an image is missing.

---

## 6. Statistics and Analysis

All statistics share a period selector (week, month, season, year, all time, custom) and are filterable by date range.

By default, statistics count the **main card** only. A switch includes clarifiers, and charts show which mode they're in. Jumpers never enter the main statistics; they have their own analysis (6.2, item 15).

The framework is built first: periods (6.1), comparison with the previous period (6.3) and expected-vs-observed testing (6.4). The analyses in 6.2 are then listed in build order.

### 6.1 Period definitions

| Period | Definition                                                                                                                                                                                                                                                                                                  |
| ------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Week   | ISO week (Monday–Sunday)                                                                                                                                                                                                                                                                                    |
| Month  | Calendar month                                                                                                                                                                                                                                                                                              |
| Season | Selectable in settings, Northern Hemisphere. **Meteorological** (default): Winter Dec–Feb, Spring Mar–May, Summer Jun–Aug, Autumn Sep–Nov. **Astronomical**: solstices and equinoxes. **Celtic fire festivals**: Samhain (1 Nov), Imbolc (1 Feb), Beltane (1 May) and Lughnasadh (1 Aug) each open a season |
| Year   | Calendar year                                                                                                                                                                                                                                                                                               |

Period bucketing is done in PHP with Carbon, so results are identical on web and device.

### 6.2 Required analyses

**Deck structure** (the simplest, and the ones that prove the expected-count weighting works):

1. **Suit counts per month**: stacked bar chart and table of Wands / Cups / Swords / Disks / Major Arcana per month, as counts and percentages, with the month's dominant suit called out.
2. **Major vs. minor vs. court**: proportions against the deck (22/78 major, 16/78 court).
3. **Element distribution**: Fire / Water / Air / Earth, with court sub-elements available as a second layer.
4. **Rank/number distribution**: Aces through Tens plus the court ranks.
5. **Card frequency**: ranked list of all cards by appearances, including cards never drawn, with "last drawn" and "days since last drawn".

**Time patterns:** 6. **Repeat draws**: cards drawn twice or more within a rolling window (for example 30 days). 7. **Streaks and gaps**: current and longest run of days with a card drawn. Note-only days count as gaps. 8. **Weekday distribution**: suits and elements by day of the week.

**Attributions** (rely on the full astrological and Tree data): 9. **Astrology**: counts by planet, by zodiac sign and by decan, combining Majors, pips and courts according to their attributions. 10. **Tree of Life distribution**: a heat map of how often each sephira and path was lit, plus breakdowns by pillar (Mercy: Chokmah, Chesed, Netzach; Severity: Binah, Geburah, Hod; Middle: Kether, Tiphareth, Yesod, Malkuth) and by triad (Supernal, Ethical, Astral, Malkuth). 11. **Birth cards**: how often each of your birth cards was drawn in the period, grouped by role (birth Atu, decan card, Sun-sign Atu, court card), against the expected rate for that many cards.

**Your own notes** (depend on evening entries, which the imported history doesn't have, so these become useful as new days build up): 12. **Theme analysis**: top themes per week, month, season and year, with a theme-by-period heat map. Theme rename and merge tools are built just before this, so drifting tags ("change", "transition") can be tidied first. 13. **Resonance**: share of days marked "resonated", overall and broken down by suit, element, planet and Tree position. 14. **Event correlation view**: for a chosen card, suit, planet or theme, the event notes and outcomes from the days it appeared.

**Separate:** 15. **Jumpers**: how often cards jump, which cards jump most, their suits and elements, and how often a jumper shares a suit, element or Tree position with that day's main card. Kept separate from all other analyses.

### 6.3 Comparison with the previous period

Every analysis can show the selected period beside the previous one (this month against last month, this week against last week), with the change marked on each chart and table.

### 6.4 Statistical honesty

- The dashboard shows **expected vs. observed** counts. Expected counts are weighted by deck composition, not by a uniform split: 22 Majors against 14 cards per suit, 8 cards on each of Chokmah, Binah, Tiphareth and Malkuth against 4 on the other sephiroth, and planet and sign weights derived from the seeded attributions.
- A chi-square goodness-of-fit indicator is shown with a plain-English verdict: too few draws to judge (fewer than 5 expected per category), consistent with chance, or unusual (p < 0.05) with a reminder that many charts are being viewed at once.

### 6.5 Implementation notes

- Each analysis is its own class in `app/Stats/`, taking a period and returning a DTO for the Vue chart components.
- At this data size (one day per row, a few thousand rows over many years) the analyses load the data once and aggregate in PHP, which keeps every query portable between web and device.

---

## 7. Routes and Pages (Inertia)

| Route                      | Page                                                                                                                                |
| -------------------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| `/`                        | Today                                                                                                                               |
| `/days/{date}`             | Day page (reading, jumpers, clarifiers, notes, evening section)                                                                     |
| `/days/{date}/edit`        | Edit a day (until locked)                                                                                                           |
| `/calendar/{year}/{month}` | Calendar (grid or list)                                                                                                             |
| `/cards`                   | Card index (78 cards)                                                                                                               |
| `/cards/{card:slug}`       | Card page with appearance history                                                                                                   |
| `/stats`                   | Statistics dashboard                                                                                                                |
| `/notes`                   | Event notes list                                                                                                                    |
| `/themes`                  | Manage themes (rename, merge)                                                                                                       |
| `/import`                  | Paste-in importer and review screen                                                                                                 |
| `/settings`                | Theme, birth cards, season definition (meteorological, astronomical, Celtic), Emperor/Star placement, excerpt toggle, export/import |

---

## 8. External Dependencies and Constraints

### thothreadings.com

- Treated as a third-party content source. v1 is **link-out only**, using the URL stored per card.
- The URLs are populated once from the seeder file, checked by hand, and kept healthy with `cards:verify-links`, which reports any that have stopped resolving.
- If the excerpt feature is enabled later: fetch server-side with a descriptive User-Agent, respect `robots.txt`, fetch each card at most once, store only a short excerpt, and always attribute and link to the source. Check the site's terms before enabling it.

### 750words.com

- Link only. There is no API integration and no scraping.
- URL pattern: `https://750words.com/account/stats/YYYY-MM-DD`, built from the day's local date.
- Your 750words account timezone must be set to London for the dates to match (reminded at first run).

---

## 9. Mobile (NativePHP, Android)

- NativePHP Mobile bundles PHP and the Laravel app into an Android shell, with SQLite on the device and the compiled Vue assets bundled in.
- **The web app holds the master copy.** The phone keeps a full local copy so it works offline, and syncs with the web app whenever it is online.
- **Sync:** an authenticated API (Sanctum token entered once in settings). The phone pushes days and card notes changed since its last sync, then pulls everything changed on the web. Records use ULIDs and soft deletes so either side can create and delete safely. If both sides changed the same day, the web version wins and the phone's version is kept in a conflict log you can review.
- **Locking and sync:** the 7-day morning lock is enforced on the web, so an offline edit to a day that has since locked is rejected at sync and listed in the conflict log.
- JSON export/import (4.11) remains for backups.
- **Native features to use** (confirm availability in the current NativePHP release):
    - Local morning reminder ("Draw your card") and evening reminder ("How did it play out?")
    - Opening 750words and thothreadings links in the system browser
    - Optional biometric lock
    - Following the Android system theme
- **Layout:** the mobile-first shell from 4.1. Charts must be touch-friendly and readable at 360px width.

---

## 10. Non-Functional Requirements

- Single user. Registration is disabled once the account exists.
- Data is private. No analytics or third-party trackers.
- HTTPS for the web deployment.
- Nightly database backup for the web deployment, plus on-demand export.
- Accessibility: card images have alt text with the card name, charts have a table alternative, text meets contrast requirements in all three themes, and touch targets are at least 44px.

---

## 11. Delivery Phases

Each phase follows the build order above.

**Phase 1: Core log (web)**

1. Project setup: Vue starter kit, single-user auth, SQLite
2. Deck seeder with full attributions, Tree positions and thothreadings URLs, plus `cards:verify-links`
3. Appearance and app shell (4.1)
4. Card pages and images, including `cards:import-images` (4.2)
5. Tree of Life component (4.3)
6. Birth cards (4.4)
7. Journal link and first-run timezone reminder (4.5)
8. Today screen with card picker, jumpers and clarifiers (4.6)
9. Evening section and Notes page (4.7)
10. Editing and the 7-day lock (4.8)
11. Importer with review screen, bringing in the 2026 history (4.9)
12. Calendar grid and list (4.10)
13. Export and restore (4.11)

**Phase 2: Statistics**

1. Period engine, previous-period comparison, and expected-vs-observed with chi-square (6.1, 6.3, 6.4)
2. Deck structure analyses (6.2, items 1–5)
3. Time patterns (items 6–8)
4. Attribution analyses, including the Tree heat map (items 9–11)
5. Theme rename and merge tools, then theme, resonance and event correlation analyses (items 12–14)
6. Jumper analysis (item 15)

**Phase 3: Mobile**

1. NativePHP Android build of the existing app
2. Sync API and conflict log (the phone depends on it, since the web holds the master copy)
3. Morning and evening notifications, system-browser links, System theme on Android

**Phase 4: Refinements**

- Optional excerpts from thothreadings.com, after checking the site's terms

---

## 12. Future Ideas

- Multi-card spreads (three-card, Celtic Cross) with named positions.
- Moon phase and astrological transit overlay on the day view.
- "Journaled?" tracking per day.
- Year-in-review summary page.
