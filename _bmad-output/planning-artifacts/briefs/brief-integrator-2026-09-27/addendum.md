---
title: "Addendum: integrator product brief"
status: final
created: 2026-09-27
updated: 2026-09-27
---

# Addendum: integrator *(working title)*

Supporting depth that belongs to this brief's story but doesn't fit its 1-2 page shape — for whoever builds the PRD, architecture, or an investor pitch next.

## Form Factor

Web platform is the primary surface. Mobile/field access matters — integrators need to reach the catalogue, proposals, and orders while on-site or between jobs — but on-site room-measurement tooling (e.g. AR-based measurement) is explicitly not required for v1. This should inform the architecture/PRD's platform and responsive-design decisions, not gate the brief's core pitch.

## Competitive & Regional Research (sourced)

**Global/US comparable-platform landscape** — grounding for "What Makes This Different":
- **ADI Global Distribution** — largest catalog (80,000+ SKUs) for security/pro-AV/low-voltage, 190+ locations; strong on distribution scale, weak on native project-design tooling (partners with D-Tools, WeQuote, ConnectWise CPQ instead of building it natively).
- **D-Tools (System Integrator/Cloud)** — the incumbent design-to-procurement tool (estimating, proposals, CAD/system design, procurement, PM); integrates with ADI for live pricing — proof that stitching design + distribution together via integration is the existing norm, not a novel idea.
- **Snap One/Control4 dealer portal** — vertically integrated own-brand distribution with a 5-tier "Partner Rewards" loyalty program (progressive discounts, free shipping, ~8% quarterly rebates at top tier, priority support) — the closest real-world precedent for this brief's loyalty-tier design. Locks integrators into Snap One's own ecosystem rather than an open multi-brand catalog.
- **iPoint Solutions** — proposal/business-management suite with configurable margins and the ability to hide cost lines from client-facing proposals — the closest existing analog to this platform's "share proposal with margin included" feature.
- **WeQuote** — cloud quoting tool pulling live prices from multiple distributors, explicitly positioned against the "quoting/inventory/procurement live in different places" fragmentation this brief also targets.
- Sources: [D-Tools/ADI integration](https://www.d-tools.com/resource-center/news-events/adi-integrates-with-d-tools-business-management-software-solutions), [ADI software integrations](https://www.adiglobaldistribution.us/services/software-integrations), [Snap One Partner Rewards](https://www.cepro.com/news/snap-one-merges-rewards-programs-from-control4-distribution-partners/96204/), [iPoint proposals](https://ipointsolutions.net/product/proposals/), [WeQuote coverage](https://www.commercialintegrator.com/insights/wequote-and-its-u-s-growth-story-maximizing-connection-at-cedia-expo/149280).

**Nigeria/West Africa regional context**:
- **B2B e-commerce maturity**: FMCG-focused platforms (TradeDepot, Omnibiz, Alerzo) have proven the distributor-digitization model at scale (Tracxn lists 106 B2B e-commerce startups in Nigeria, 150 in West Africa broadly) — but no equivalent exists yet for industrial equipment, electronics, security/AV, or electrical wholesale. That specific vertical is open, unaddressed space.
- **Trade-body fragmentation**: no single cross-discipline "systems integrator" association exists. Adjacent bodies are siloed by discipline — Nigeria Computer Society (NCS, general IT), NEMSA/LECAN (mandatory electrical-installation licensing), COREN/NIEEE (professional engineering registration). AV and security integration specifically have no dedicated national association (unlike the US's CEDIA/AVIXA/SIA). The market runs on ~250+ unaffiliated integrator companies plus informal contractor networks — relevant for go-to-market, since there's no single body to partner with for distribution or credibility.
- **Payment/trade-credit infrastructure**: Nigeria is West Africa's largest B2B BNPL market (~$1.4-1.75B projected 2025-26, 20%+ CAGR through 2030), but that credit is embedded in platform-fintech products (TradeDepot, Sabi, Moniepoint), not standalone trade-credit infrastructure. Paystack/Flutterwave remain payment-collection rails, not credit underwriters. Relevant if a future trade-credit feature is considered (explicitly out of scope for v1 — see Scope).
- **Logistics/customs**: documentation inconsistency is the #1 clearance-delay driver at Nigerian ports; mandatory SON conformity assessment applies to electronics/machinery; FX access constraints from CBN controls compound import friction. Freight-tech is workable but fragile (Kobo360 had a major 2025 financial crisis).
- **Counterfeit risk**: reported at ~40% of products in some categories, with electrical/component categories flagged as safety-critical fake risks — a real argument for provenance/verified-sourcing as a differentiator, beyond what's stated in the main brief.
- Sources: [Guardian NG](https://guardian.ng/business-services/b2b-e-commerce-consolidating-nigerias-digital-economy-blueprint-alerzo-ceo/), [Tracxn Nigeria](https://tracxn.com/d/explore/b2b-e-commerce-startups-in-nigeria/), [NCS](https://www.ncs.org.ng/), [NEMSA](https://nemsa.gov.ng/), [ensun.io](https://ensun.io/search/system-integrator/nigeria), [GlobeNewswire BNPL report](https://www.globenewswire.com/news-release/2026/04/22/3279189/), [Wigmore Trading customs guide](https://wigmoretrading.com/customs-clearance-in-nigeria-a-complete-guide-to-smooth-import-and-export-operations/), [trade.gov Nigeria market challenges](https://trade.gov/country-commercial-guides/nigeria-market-challenges), [Kobo360](https://www.kobo360.com/), [Sahara Reporters on counterfeits](https://saharareporters.com/2026/05/14/southwest-nigeria-epicentre-africas-largest-counterfeit-economy-report).

## Parked Roadmap: B2C / Hire-an-Integrator

Beyond the platform's core integrator-facing scope, there's a future direction worth preserving: end clients (currently not platform users at all — they only ever see the proposal an integrator shares with them) could eventually get their own accounts, with an option to discover and hire a vetted integrator directly through the platform when a project requires professional installation. This would turn integrator into a two-sided B2C surface on top of its B2B core, but only makes sense once there's a real base of trusted, verified integrators on the platform to point clients toward — it depends on the core model succeeding first, not the other way around. Explicitly out of scope for v1 (see Scope in brief.md).

## Open Question for the PRD: Loyalty Tier Mechanics

Decided in principle: loyalty discounts are based on **trailing-window purchase spend** (not lifetime cumulative), so an integrator's tier can move up or down with recent buying activity — modeled loosely on Snap One's 5-tier Partner Rewards program (see research above).

Not yet decided, and needs to be pinned down at the PRD stage:
- The length of the trailing window (e.g. trailing 90 days, quarterly, trailing 12 months)
- The number of tiers and the discount spread between them
- Whether tier benefits include anything beyond price (e.g. shipping, support priority — as Snap One's program does) or are purely a discount percentage
