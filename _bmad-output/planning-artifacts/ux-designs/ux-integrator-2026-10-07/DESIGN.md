---
name: integrator
description: A trade ledger for integrators who work from a phone on a ladder and from a desktop at the office. Warm paper, serif headings, hairline rules, flat and dense. Light mode only. integrator is a working title.
status: final
updated: 2026-10-09
colors:
  surface-paper: '#FAF6EC'
  surface-sheet: '#FFFFFF'
  surface-aged: '#F0E9D6'
  ink: '#1A1812'
  ink-secondary: '#4A4434'
  ledger-green: '#153D2E'
  brass: '#6B4E0C'
  caution: '#6E4400'
  caution-tint: '#F6EACB'
  good: '#1B5E3A'
  error: '#8A1C14'
  error-tint: '#F6E4DE'
  control-edge: '#7A7258'
  hairline: '#D9CFB5'
  on-green: '#FFFFFF'
  on-green-soft: '#C9DCCF'
  scrim: '#1A1812'
  focus-ring: '#153D2E'
  focus-ring-on-green: '#FFFFFF'
typography:
  title-console:
    fontFamily: '"Palatino Linotype", Palatino, "Book Antiqua", "Noto Serif", "Times New Roman", serif'
    fontSize: 26px
    fontWeight: '700'
    lineHeight: '1.15'
  title-screen:
    fontFamily: '"Palatino Linotype", Palatino, "Book Antiqua", "Noto Serif", "Times New Roman", serif'
    fontSize: 21px
    fontWeight: '700'
    lineHeight: '1.2'
  heading:
    fontFamily: '"Palatino Linotype", Palatino, "Book Antiqua", "Noto Serif", "Times New Roman", serif'
    fontSize: 19px
    fontWeight: '700'
    lineHeight: '1.2'
  heading-small:
    fontFamily: '"Palatino Linotype", Palatino, "Book Antiqua", "Noto Serif", "Times New Roman", serif'
    fontSize: 16px
    fontWeight: '700'
    lineHeight: '1.2'
  eyebrow:
    fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'
    fontSize: 12px
    fontWeight: '700'
    lineHeight: '1.2'
    letterSpacing: 0.14em
    note: 'Capitals, brass. The only place capitals are used.'
  body:
    fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'
    fontSize: 15px
    fontWeight: '400'
    lineHeight: '1.45'
  body-strong:
    fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'
    fontSize: 15px
    fontWeight: '600'
    lineHeight: '1.45'
  small:
    fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'
    fontSize: 13px
    fontWeight: '400'
    lineHeight: '1.4'
  label:
    fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'
    fontSize: 12px
    fontWeight: '600'
    lineHeight: '1.2'
    note: 'The smallest size anywhere. Nothing is set below 12px.'
  button:
    fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'
    fontSize: 15px
    fontWeight: '600'
    lineHeight: '1.2'
  button-console:
    fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'
    fontSize: 14px
    fontWeight: '600'
    lineHeight: '1.2'
  input:
    fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.3'
    note: '16px so a phone never zooms in on its own.'
  amount-total:
    fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'
    fontSize: 24px
    fontWeight: '700'
    lineHeight: '1.1'
    note: 'Tabular figures.'
  amount-line:
    fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'
    fontSize: 15px
    fontWeight: '700'
    lineHeight: '1.3'
    note: 'Tabular figures, right-aligned in a column.'
  table-console:
    fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'
    fontSize: 13px
    fontWeight: '400'
    lineHeight: '1.4'
  code:
    fontFamily: 'ui-monospace, "Cascadia Mono", Consolas, "Liberation Mono", Menlo, monospace'
    fontSize: 13px
    fontWeight: '600'
    lineHeight: '1.3'
    note: 'References, codes and serial numbers, so they can be read aloud and copied.'
rounded:
  sm: 2px
  sheet: 14px
  full: 9999px
spacing:
  '1': 4px
  '2': 8px
  '3': 12px
  '4': 16px
  '5': 24px
  '6': 32px
  '7': 48px
  screen-margin: 16px
  target-min: 44px
  target-phone-button: 52px
  target-console: 40px
  target-console-small: 34px
  row-phone-min: 56px
  checkbox-row: 48px
  field-height: 48px
  tab-bar-height: 68px
  console-rail: 224px
  console-built-for: 1280px
  console-reflow-below: 1024px
  content-max: 1100px
components:
  button-primary:
    background: '{colors.ledger-green}'
    foreground: '{colors.on-green}'
    border: '1px solid {colors.ledger-green}'
    radius: '{rounded.sm}'
    typography: '{typography.button}'
    height: '{spacing.target-phone-button}'
  button-secondary:
    background: '{colors.surface-sheet}'
    foreground: '{colors.ledger-green}'
    border: '1px solid {colors.ledger-green}'
    radius: '{rounded.sm}'
    typography: '{typography.button}'
  button-danger:
    background: '{colors.error}'
    foreground: '{colors.on-green}'
    border: '1px solid {colors.error}'
    radius: '{rounded.sm}'
  button-danger-outline:
    background: '{colors.surface-sheet}'
    foreground: '{colors.error}'
    border: '1px solid {colors.error}'
    radius: '{rounded.sm}'
  button-disabled:
    background: '{colors.surface-aged}'
    foreground: '{colors.ink-secondary}'
    border: '1px dashed {colors.control-edge}'
    radius: '{rounded.sm}'
  status-label:
    background: '{colors.surface-sheet}'
    foreground: '{colors.ink}'
    border: '1px solid {colors.control-edge}'
    radius: '{rounded.sm}'
    typography: '{typography.label}'
  status-label-needs:
    background: '{colors.caution-tint}'
    foreground: '{colors.caution}'
    border: '1px solid {colors.brass}'
    radius: '{rounded.sm}'
    typography: '{typography.label}'
  status-label-failed:
    background: '{colors.error-tint}'
    foreground: '{colors.error}'
    border: '1px solid {colors.error}'
    radius: '{rounded.sm}'
    typography: '{typography.label}'
  status-label-good:
    background: '{colors.surface-sheet}'
    foreground: '{colors.good}'
    border: '1px solid {colors.good}'
    radius: '{rounded.sm}'
    typography: '{typography.label}'
  banner:
    background: '{colors.surface-aged}'
    foreground: '{colors.ink}'
    border: '4px solid {colors.ledger-green} (left rule)'
    typography: '{typography.small}'
  banner-needs:
    background: '{colors.caution-tint}'
    foreground: '{colors.ink}'
    border: '4px solid {colors.brass} (left rule)'
  banner-failure:
    background: '{colors.error-tint}'
    foreground: '{colors.ink}'
    border: '4px solid {colors.error} (left rule)'
  form-field:
    background: '{colors.surface-sheet}'
    foreground: '{colors.ink}'
    border: '1px solid {colors.control-edge}'
    radius: '{rounded.sm}'
    typography: '{typography.input}'
    height: '{spacing.field-height}'
  code-entry:
    background: '{colors.surface-sheet}'
    border: '1px solid {colors.control-edge}'
    radius: '{rounded.sm}'
    typography: '{typography.amount-total}'
    height: '{spacing.target-phone-button}'
  choice:
    background: '{colors.surface-sheet}'
    border: '1px solid {colors.control-edge}'
    radius: '{rounded.sm}'
    note: 'Selected: 2px {colors.ledger-green} border. Checkbox 26px. Radio 24px. Switch with the word On or Off beside it.'
  quantity-stepper:
    background: '{colors.surface-sheet}'
    border: '1px solid {colors.control-edge}'
    radius: '{rounded.sm}'
    typography: '{typography.amount-line}'
    height: 46px
  row-and-table:
    background: '{colors.surface-sheet}'
    border: '1px solid {colors.hairline} (row rules)'
    typography: '{typography.table-console}'
    height: '{spacing.row-phone-min}'
    note: 'Phone row at least 56px. Console table header on {colors.surface-aged} with a 2px {colors.ink} top rule.'
  sheet-and-dialog:
    background: '{colors.surface-paper}'
    border: '3px solid {colors.ledger-green} (top)'
    radius: '{rounded.sheet}'
    note: 'Scrim is {colors.scrim} at 62%. The console dialog is centred and square-cornered.'
  tabs-and-navigation:
    background: '{colors.surface-sheet}'
    foreground: '{colors.ink-secondary}'
    border: '2px solid {colors.ink} (tab bar top rule)'
    height: '{spacing.tab-bar-height}'
    note: 'Active tab: 4px {colors.ledger-green} bar and {colors.ledger-green} text. Top bar and console top bar are {colors.ledger-green} with {colors.on-green} text. Console rail is {colors.surface-aged}, 224px.'
  timeline-and-steps:
    foreground: '{colors.ink}'
    border: '2px solid {colors.hairline}'
    note: 'Done: {colors.ledger-green} tick in a ring. Now: numbered ring. Step pips 4px tall, {colors.ledger-green} when done, {colors.hairline} otherwise.'
  clock:
    foreground: '{colors.ink}'
    typography: '{typography.amount-line}'
    note: 'Time left in bold, exact end time beside it. Turns {colors.caution} on {colors.caution-tint} in the last hours.'
  meter:
    background: '{colors.surface-aged}'
    border: '1px solid {colors.control-edge}'
    height: 12px
    note: 'Fill {colors.ledger-green}. Fill {colors.brass} when late. Always with its figure in words.'
  amount:
    foreground: '{colors.ink}'
    typography: '{typography.amount-total}'
    border: '3px double {colors.ink} (above and below a grand total)'
    note: 'Naira sign, commas, two decimals, tabular figures. VAT included stated in {typography.eyebrow}.'
  card:
    background: '{colors.surface-paper}'
    border: '1px solid {colors.control-edge}'
    radius: '{rounded.sm}'
    note: 'Queue cards and tiles add a 3px double {colors.ink} top rule. A delivery card is {colors.surface-sheet}.'
  check-list:
    foreground: '{colors.ink}'
    border: '1px solid {colors.hairline} (row rules)'
    typography: '{typography.small}'
    note: 'Tick in {colors.good}. Verdict in words at the right: Clear or Needs action.'
  copy-and-share:
    background: '{colors.surface-sheet}'
    foreground: '{colors.ledger-green}'
    border: '1px solid {colors.control-edge}'
    radius: '{rounded.sm}'
    height: '{spacing.target-min}'
    typography: '{typography.code}'
  upload:
    background: '{colors.surface-sheet}'
    border: '1px solid {colors.control-edge}'
    radius: '{rounded.sm}'
    note: 'File row with a thumbnail box, name, size and percentage, a Remove button and a {colors.ledger-green} meter.'
  preview-panel:
    background: '{colors.surface-aged}'
    border: '2px solid {colors.ink} (top rule)'
    typography: '{typography.small}'
  two-person-approval:
    background: '{colors.caution-tint}'
    border: '4px solid {colors.brass} (left rule)'
    note: 'A needs banner plus a {components.button-disabled} Approve for the person who asked.'
  guide-step:
    background: '{colors.surface-paper}'
    foreground: '{colors.ink}'
    typography: '{typography.heading}'
    note: 'One step to a screen, body text one size up, and the warning box is {colors.caution-tint} with a {colors.brass} rule.'
---

## Brand & Style

integrator is a trade counter with a ledger on it. It is built by an integrator for integrators in Nigeria, and the people who use it are practitioners: a technician on a ladder with one bar of signal, an owner at a desk with a client waiting, a finance officer matching a bank statement line by line. The look follows the object they already trust. A private-bank ledger on warm paper: serif headings, hairline rules, double rules under totals, figures that line up, nothing decorative.

Direction A, called **Ledger**, was chosen from four rendered directions and taken whole, with no changes to its palette, shape, density or type. The directions set aside were Site Kit (black and hazard yellow, heavy and roomy), Soft (lilac and indigo, large radii) and Spec Sheet (monospace figures and bracketed codes). Five colour themes were also shown and none was picked, because the Ledger direction came with its own palette.

The posture is calm and exact. Money, time and verification are the product, so the interface looks like a document that can be checked: one filled button per screen, plain words on every status, a colour only where a person has to act or something has gone wrong. It is flat. There are no shadows, no gradients, no rounded pill shapes, no icons standing alone. It works in light mode only in version 1, and in English only.

Reference: [mockups/system/direction-ledger.html](mockups/system/direction-ledger.html) shows the chosen direction on the Project screen as an Owner, a Technician with no prices and offline, the Order payment screen on a phone, and the staff Receipts queue.

## Colors

A short list of colours does all the work. Everything sits on paper, and nothing is pure grey.

- **Paper ({colors.surface-paper}, `#FAF6EC`)** is the ground of every screen. Slightly warm, so a phone in sunlight does not glare.
- **Sheet ({colors.surface-sheet}, `#FFFFFF`)** is for what is written on: fields, tables, delivery cards, bottom sheets' contents and the document-like panels.
- **Aged ({colors.surface-aged}, `#F0E9D6`)** is the second paper: notes, panels, table header bands, the console rail and the fill of a button that cannot be pressed.
- **Ink ({colors.ink}, `#1A1812`) and ink secondary ({colors.ink-secondary}, `#4A4434`)** carry all text. Secondary is for helper lines, labels and captions, never for the one thing a person must read.
- **Ledger green ({colors.ledger-green}, `#153D2E`)** is the brand and the one action colour: headings, links, the top bar, the filled primary button and the focus ring. It is never used to mean "good".
- **Brass ({colors.brass}, `#6B4E0C`)** is the accent for labels and rules: the small capital label above a heading, and the left rule on a banner that needs a person. **Caution ({colors.caution}, `#6E4400`) on caution tint ({colors.caution-tint}, `#F6EACB`)** is the pair for anything that needs a person or a clock: a price hold running out, a receipt to send, a request waiting.
- **Good ({colors.good}, `#1B5E3A`)** appears only for a passed check ("Clear", "Matches") and in stock.
- **Error ({colors.error}, `#8A1C14`) on error tint ({colors.error-tint}, `#F6E4DE`)** is for something that went wrong or is refused, and for the one red button, the one that cannot be undone.
- **Control edge ({colors.control-edge}, `#7A7258`)** is the border of every control. **Hairline ({colors.hairline}, `#D9CFB5`)** is a decorative row rule and carries no meaning.
- **On green ({colors.on-green}, `#FFFFFF`) and on green soft ({colors.on-green-soft}, `#C9DCCF`)** are text on the green bars and on the filled green button.

**In-progress is neutral.** Approved, Processing, In transit and every other state that is simply under way is a plain outlined label with words and no colour. Colour is reserved for good (green), needs a person or a clock (brass and caution), and error (red). A status never relies on colour: the words carry the meaning. In `EXPERIENCE.md`, *brass* is short for the needs-a-person pair and *red* for the error pair.

**Focus.** A 3px {colors.focus-ring} outline with a 2px gap on everything that can be reached. On the green bars and on a filled green button the ring is {colors.focus-ring-on-green}, because green on green is 1.00 to 1.

Contrast was worked out from these tokens with the WCAG 2.2 relative-luminance formula. Every text pair below passes 4.5 to 1, and the weakest is 6.37.

| Pair, as used | Where | Ratio |
|---|---|---|
| {colors.ink} on {colors.surface-paper} | Body text | 16.44 to 1 |
| {colors.ink-secondary} on {colors.surface-sheet} | Helper text | 9.69 to 1 |
| {colors.ink-secondary} on {colors.surface-aged} | Notes, panels | 8.00 to 1 |
| {colors.ledger-green} on {colors.surface-paper} | Links, headings | 11.19 to 1 |
| {colors.on-green} on {colors.ledger-green} | Top bar, filled button | 12.08 to 1 |
| {colors.on-green-soft} on {colors.ledger-green} | Small text on a green bar | 8.41 to 1 |
| {colors.caution} on {colors.caution-tint} | Needs-a-person labels and banners | 7.05 to 1 |
| {colors.brass} on {colors.caution-tint} | Brass label on a tint | 6.45 to 1 |
| {colors.brass} on {colors.surface-aged} | Eyebrow label on aged | 6.37 to 1 |
| {colors.error} on {colors.error-tint} | Errors, refused | 7.56 to 1 |
| {colors.good} on {colors.surface-sheet} | "Clear", "Matches" | 7.75 to 1 |
| {colors.on-green} on {colors.error} | The red button | 9.30 to 1 |
| {colors.control-edge} on {colors.surface-paper} | Field and button borders (needs 3 to 1) | 4.45 to 1 |
| {colors.focus-ring} on {colors.surface-paper} | Focus ring (needs 3 to 1) | 11.19 to 1 |
| {colors.focus-ring-on-green} on {colors.ledger-green} | Focus on a green bar | 12.08 to 1 |

Colour evidence, with the pairs drawn as swatches: [mockups/system/accessibility-step21.html](mockups/system/accessibility-step21.html).

Avoid: tints for anything merely in progress, a second brand colour, pure black, grey that is not warm, and green for anything but a passed check or stock.

## Typography

Two voices and one for codes. Headings are serif, set in the **system serif stack**: Palatino Linotype, Palatino, Book Antiqua, Noto Serif, Times New Roman. No font file is bundled, so on Android the heading is the device's own serif. The body and every amount are the **system sans**. References, codes and serial numbers are the **system monospace**, so they can be read aloud and copied. System fonts only keeps the first screen fast on slow data.

| Role | Face and size | Used for |
|---|---|---|
| {typography.title-console} | Serif 26px | The title of a console page |
| {typography.title-screen} | Serif 21px | The title of a phone screen |
| {typography.heading} | Serif 19px | A card or sheet title |
| {typography.heading-small} | Serif 16px | A section title with a rule under it |
| {typography.eyebrow} | Sans 12px, capitals, spaced | The small label above a heading, in {colors.brass}. The only capitals. |
| {typography.body} | Sans 15px | Everything else |
| {typography.small} | Sans 13px | Helper lines, captions, meta |
| {typography.label} | Sans 12px | Status labels. The smallest size anywhere. |
| {typography.button} | Sans 15px, 600 | Phone buttons. Console buttons are {typography.button-console}, 14px. |
| {typography.input} | Sans 16px | Fields |
| {typography.amount-total} | Sans 24px, 700, tabular | A grand total |
| {typography.amount-line} | Sans 15px, 700, tabular | An amount in a row |
| {typography.table-console} | Sans 13px | Console tables |
| {typography.code} | Monospace 13px | INT-0042, CN-0012, vouch codes, serial numbers |

Rules. Sentence case everywhere. Every amount uses tabular figures and lines up on the right in a list. Nothing is set below 12px. Text scales with the system's larger-text setting, and the layout is checked at 200% with nothing lost. Console 13px is a density choice for a desktop and sits above the 12px floor.

## Layout & Spacing

The scale is 4, 8, 12, 16, 24, 32 and 48px, plus named sizes for the things that must not drift: a 16px screen margin, 44px targets (52px for a phone's main button), 56px phone rows and 48px fields.

**Phone, up to 599px.** One column. A 16px margin. A bottom tab bar of five. Sheets rise from the bottom. The layout works down to 320px with no sideways scrolling. A sticky bar holds the one main action and the total.

**Tablet, 600 to 1023px.** The phone layout centred in a column up to 720px, with the tab bar kept. From 900px a list and the chosen item sit side by side.

**Laptop and desktop, 1024px and up.** The tab bar becomes a left rail. Content stops at {spacing.content-max} (1100px) and is centred. What each screen does at this size is in `EXPERIENCE.md`, under Responsive & Platform.

**Staff console.** Built for a desktop at {spacing.console-built-for} (1280px). A top bar, a {spacing.console-rail} left rail grouped by kind of work, then the page. Tables are 13px with 40px rows and controls (34px for a small one in a row). Below {spacing.console-reflow-below} (1024px), including when a person zooms in, the rail becomes a menu button, side-by-side panes stack and a wide table scrolls inside its own box.

**Rhythm.** Hairline rules between rows, a 2px ink rule under a section title, and a double rule above and below a grand total. Related things sit 4 to 8px apart, groups 16 to 24px. Density is compact: a screen should show what a person needs without scrolling for the main figure.

Layout references: [mockups/system/responsive-platform-step22.html](mockups/system/responsive-platform-step22.html) (the three sizes and four laptop-width frames) and the per-flow sheets in `mockups/app/` and `mockups/console/`.

## Elevation & Depth

None. There are no shadows, no blurs and no gradients. Depth is tone and rule: paper under sheet, aged panels under paper, and a hairline or a 2px ink rule where one thing ends and another begins. A sticky bar is marked by a 2px ink rule on top. The only layer is the scrim behind a sheet or dialog, {colors.scrim} at 62%, and the sheet that sits on it has a 3px {colors.ledger-green} top rule.

## Shapes

Corners are square with a 2px softening ({rounded.sm}) on every control, card, label and field. A bottom sheet rounds its top two corners to {rounded.sheet} (14px) so it reads as a surface rising from the edge. {rounded.full} is only for a radio button and a progress dot. No pill shapes, no round avatars, no circles for surfaces. Borders are real borders, not shadows, so a control is still visible when the system forces its own colours.

Icons are 2px line icons at 18 to 20px with round ends, and are always paired with words. The only icon-only controls are the cart and Back, and both have a name. The wordmark is **integrator** in bold italic serif, provisional like the name. Photos appear on a product page and in a guide step, and nowhere in a list row. A business's own logo appears only on its own proposals and documents.

## Components

Visual specs. Behaviour is in `EXPERIENCE.md`, under Component Patterns, with the same names. The tokens above are in the frontmatter; this is what to build.

- **Button** — Primary is {colors.ledger-green} filled with {colors.on-green} text, {spacing.target-phone-button} high on a phone and {spacing.target-console} in the console. Secondary is {colors.surface-sheet} with a {colors.ledger-green} outline and text. Danger is {colors.error} filled with white text, used only in a dialog about something that cannot be undone; the console's Decline and Put on hold buttons are the outline variant. A button that cannot be pressed stays on the screen as {colors.surface-aged} with a dashed {colors.control-edge} border and {colors.ink-secondary} text, with its reason written beside it.
- **Status label** — A small outlined box with the word inside, {typography.label}. Neutral for in progress. {colors.caution} on {colors.caution-tint} with a brass outline for needs a person or a clock. {colors.error} on {colors.error-tint} for failed. {colors.good} outline for a passed check.
- **Banner** — A 4px left rule and a tint: aged with green for information, caution tint with brass for needs a person, error tint with red for a failure. The action sits inside the banner. Account-wide banners stack at the very top of the screen.
- **Form field** — The label sits above, always visible, 13px semibold. The box is {colors.surface-sheet} with a {colors.control-edge} border, {spacing.field-height} high, {typography.input} text. An error is a 2px {colors.error} outline and a sentence under the field.
- **Code entry** — Six boxes of 52px height with large figures, over one real input so paste and auto-fill work. The resend countdown is words under the boxes.
- **Choice** — A radio card or a checkbox row is one target. Selected is a 2px {colors.ledger-green} border. A checkbox is 26px, a radio 24px. A switch is a 34 by 18px track with the word On or Off beside it.
- **Quantity stepper** — A 46px-high box with minus and plus each {spacing.target-min} wide on each side of the number, which can be typed into.
- **Row and table** — A phone row is at least {spacing.row-phone-min}, the whole row a target, amounts on the right. A console table has a fixed header on {colors.surface-aged} under a 2px ink rule, {typography.table-console} text, a selected row outlined in green with a 4px green left bar, and a late row on {colors.error-tint} with a 4px red left bar and the reason in words.
- **Sheet and dialog** — The phone sheet rises over a {colors.scrim} scrim, {colors.surface-paper} with a 3px green top rule and 14px top corners. The console dialog is centred, square, on the same scrim. The title is a question, the body lists what will happen.
- **Tabs and navigation** — The phone tab bar is five labelled tabs on {colors.surface-sheet} under a 2px ink rule, {spacing.tab-bar-height} high, the current one marked by a 4px green bar and green text. A segmented control splits a list (Open, Done, Problems). The top bar is {colors.ledger-green} with the wordmark and the cart. The console rail is {colors.surface-aged}, grouped, with counts that turn caution when something is late.
- **Timeline and steps** — A vertical list with a ring for each stage: a green tick when done, a numbered ring for now, and the date beside each. Step pips are 4px bars, green when done.
- **Clock** — The time left in bold with the exact end time beside it, and a meter under it when a target is involved.
- **Meter** — A 12px bar on {colors.surface-aged} with a {colors.control-edge} border and a {colors.ledger-green} fill ({colors.brass} when late). It always has its figure in words.
- **Amount** — The naira sign, commas, two decimals and tabular figures, in {typography.amount-line}. A grand total uses {typography.amount-total} between two 3px double ink rules, with "VAT included" in the eyebrow style under its label.
- **Card** — A {colors.surface-paper} box with a 1px control edge. A queue card or a tile adds a 3px double ink rule on top. A delivery card is {colors.surface-sheet}. A card needing action is {colors.caution-tint}.
- **Check list** — Rows of a small tick in {colors.good}, a sentence with its detail under it, and the verdict in words at the right.
- **Copy and share** — A 44px outlined button beside the thing to copy, in {typography.code} for a code. It says Copied for two seconds.
- **Upload** — A row with a thumbnail box, the file name, the size and the percentage, a Remove button, and a green meter under it while sending.
- **Preview panel** — A {colors.surface-aged} panel with label and value rows under a 2px ink rule, placed beside the Save button.
- **Two-person approval** — A needs banner naming who asked and who already agreed, and an Approve button that is {components.button-disabled} for the person who asked, with the reason beside it.
- **Guide step** — One step to a screen. Large type, one size above body, one photo, big Back and Next buttons, and a warning box in {colors.caution-tint} with a brass rule.

Looks as drawn: [mockups/system/patterns-step20.html](mockups/system/patterns-step20.html) shows sixteen of these, from Button to Two-person approval, without Meter, Amount, Card, Check list and Guide step. Those five are drawn in [the Finance console sheet](mockups/console/flow-uj6-step13-console-finance.html) (Meter, Card, Check list), [the money rules](mockups/system/product-rules-step23.html) (Amount) and [the Technician sheet](mockups/app/flow-uj5-step11-technician.html) (Guide step).

## Do's and Don'ts

| Do | Don't |
|---|---|
| One filled button per screen, in {colors.ledger-green} | A second filled button, or a coloured button for emphasis |
| A plain outlined label for anything in progress | Colour a status that is only in progress |
| Brass or caution for a person to act or a clock, red for wrong | Use red for a warning, or green for "good" outside a passed check |
| Words on every status, with colour only to reinforce | Rely on colour, or an icon alone, to carry meaning |
| Tabular figures, right-aligned, two decimals, naira only | Dollars to a business, shortened amounts (₦1.2m), or rounding for the screen |
| A double rule under a grand total and "VAT included" beside it | A shadow, a card lift or a gradient to show importance |
| A dashed, visible, disabled button with its reason beside it | Hide a button a person can use later, or disable it with no reason |
| Hide a row or a button a role can never use | Grey it out or show a padlock |
| The system serif for headings and the system sans for the rest | Bundle a font, or set body text in the serif |
| Sentence case, with the small brass capital label as the one exception | Title Case, all-caps headings, exclamation marks, emoji |
| Real borders on every control | Rely on a shadow or a fill to show where a control is |
| Light mode only, English only | A dark theme or a second language in version 1 |
