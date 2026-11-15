- source_spec: `_bmad-output/implementation-artifacts/spec-1-1-scaffold-the-project-with-its-rules-enforced.md`
  summary: Make the staging FrozenClock settable across requests (persist the frozen instant, with a command or endpoint to set and advance it), alongside the one-tick sweep command of TC-2.
  evidence: FrozenClock is an in-memory singleton per PHP process, so in staging a set instant is lost on the next request; review finding 35 of Story 1.1.

- source_spec: `_bmad-output/implementation-artifacts/spec-1-7-the-integrator-app-installs-on-a-phone.md`
  summary: Serve the app shell compressed in production and set a bundle-size budget so the cold start on the test phone approaches the NFR-6 targets.
  evidence: The emulated baseline in docs/operations/performance-baseline.md is 13.6 s to the first usable screen, with 330 KB served uncompressed (about 104 KB gzipped); nothing yet enforces compression or a budget.
