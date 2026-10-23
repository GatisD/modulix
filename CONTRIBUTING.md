# Contributing to Modulix

Thank you for helping. Modulix replaces Elementor Pro by lifting Pro's own GPL code instead of rewriting it, so sites
built with Pro keep working: same widget names, same settings, same markup. Every contribution follows that rule.

## Pick a step

1. Open [ROADMAP.md](ROADMAP.md) and pick a step whose dependencies are done. Steps labelled `good first issue` are
   widgets without server endpoints.
2. Comment on the step's issue so nobody else starts the same one. One step is one pull request.
3. A step labelled `needs-maintainer` touches endpoints, uploads, outgoing requests, capabilities or money. You can
   still work on it; a maintainer reviews those parts line by line before merging.

## Set up

- PHP 8.1 or newer, Composer, Python 3
- A WordPress test site with Elementor (free) 3.34 or newer
- For lifting: the Elementor Pro release Modulix is built from (4.0.4, see [docs/LIFTING.md](docs/LIFTING.md)). If you
  do not have it, say so on the issue: a maintainer pushes the mechanical lift commit to a branch and you continue
  from there.

```
composer install
vendor/bin/phpunit
python3 -m unittest discover -s tests/tools
tools/build.sh        # build/modulix-<version>.zip
```

## Done means

A step is ready to merge when all of these are true. Put the checklist in the pull request description.

1. **Lift commit.** The Pro files are listed in `docs/lift-manifest.txt` and copied with
   `tools/lift.py <pro-root> docs/lift-manifest.txt . 4.0.4`. That commit contains nothing else, so reviewers can
   compare it with the release.
2. **Trims in separate commits.** Each commit removes one thing and says why: licence checks, Elementor Connect,
   telemetry, upsells, or code the module does not need. Do not reformat lifted files; small diffs are what make the
   lift reviewable.
3. **Registered.** The module is added to `src/Plugin.php` and to `src/Compat/ProModulesManager.php` (its Pro
   name), and `src/Coverage.php` names every widget, dynamic tag and document type the step adds. The tests fail
   until all three agree.
4. **Endpoints listed.** The pull request has a table of every AJAX action and REST route the lifted code registers:
   its permission check, and which request fields reach a database query, the file system or an outgoing request.
   Write "none" if there are none.
5. **Tests pass.** `vendor/bin/phpunit` and the Python tests are green. `tests/LiftedReferencesTest.php` fails while a
   lifted file still refers to a class Modulix does not have, or to an `ElementorPro` name. Logic that Modulix adds
   itself (anything in `src/`) is written test first, as small functions that can be tested without WordPress.
6. **Test template.** Export an Elementor template that uses the new widgets with their main settings to
   `tests/fixtures/templates/<step>.json`. Maintainers use it to compare Modulix with Elementor Pro on a test site:
   markup, layout at desktop and mobile width, interactive states, console errors and the PHP log.
7. **README.** The table "What works today" lists the new features.

## Rules

- No requests to Elementor's servers, no telemetry, no licence code.
- Keep the copyright line and the two marker lines that `tools/lift.py` adds to every lifted file.
- Comments describe what the code does. Keep them factual.
- No secrets, site names or server addresses in code, tests or fixtures.
- AI-assisted pull requests are welcome when a person has read the diff, run the tests and answers review questions.
- Commit messages are one plain sentence: what changed and why.

## Security

Do not open a public issue for a security problem. See [SECURITY.md](SECURITY.md).

## Licence

Contributions are licensed under GPL-3.0-or-later, the same as Modulix.
