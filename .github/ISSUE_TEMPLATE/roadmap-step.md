---
name: Roadmap step
about: One step from ROADMAP.md (maintainers open these; comment to take one)
labels: roadmap
---

**Step:** <!-- e.g. W3 Animated Headline -->
**Milestone:** <!-- e.g. Milestone 6 - Widget pack -->
**Depends on:** <!-- step ids, or "nothing" -->

### What it covers
<!-- Elementor Pro module and widgets, with the Pro paths to lift -->

### Done means
- [ ] Lift commit (manifest + `tools/lift.py`, nothing else)
- [ ] Trims in separate commits, each with a reason
- [ ] Registered in `src/Plugin.php`, coverage list updated
- [ ] Endpoints table in the pull request ("none" if none)
- [ ] `vendor/bin/phpunit` and Python tests green
- [ ] Test template in `tests/fixtures/templates/`
- [ ] README table updated

See CONTRIBUTING.md for details.
