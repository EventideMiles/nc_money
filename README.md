# Money app for Nextcloud

Financial management app for Nextcloud. It is loosely based on the concepts of [GnuCash](http://www.gnucash.org).

Basic functionality is working but it is still in a very early stage of development. Use at your own risk.

I will happily accept (working) pull requests for bug fixes, improvements and new (useful) features.

## Keeping this fork updated

This fork runs on a Nextcloud AIO instance, so staying ahead of platform changes matters more than usual. The CI here watches for it:

- **Dependabot** opens grouped update PRs weekly (npm, composer) and monthly (GitHub Actions). Review and merge them like any other PR — their CI has to be green first.
- The **compatibility canary** (weekly scheduled run) runs the integration tests against the latest Nextcloud stable line and, when one exists, the newest beta/RC. A red canary run means a new Nextcloud release broke something — come fix this app before upgrading AIO. The weekly scan starts once this branch is the default/master branch (scheduled workflows only run from there); manual dispatch works anytime.
- **phpstan** scans `lib/` with Nextcloud's deprecation rules. A failure there means Nextcloud marked an API this app uses as deprecated — fix it before it gets removed. Failures are pinned to the Nextcloud version in `composer.json` (`nextcloud/ocp`), so bumping that pin after an upgrade is what reveals the new deprecations.
- `npm outdated` output is attached to each canary run's summary as a warning-only report on aging frontend dependencies.

To actually see any of this, enable GitHub notifications for Actions and Dependabot in this fork's watch settings (Watch → Custom → Actions, plus Dependabot alerts if you want them).

## User manual

Please see the [wiki](https://github.com/powerpaul17/nc_money/wiki) (coming soon).
