- source_spec: `_bmad-output/implementation-artifacts/spec-1-1-scaffold-the-project-with-its-rules-enforced.md`
  summary: Make the staging FrozenClock settable across requests (persist the frozen instant, with a command or endpoint to set and advance it), alongside the one-tick sweep command of TC-2.
  evidence: FrozenClock is an in-memory singleton per PHP process, so in staging a set instant is lost on the next request; review finding 35 of Story 1.1.
