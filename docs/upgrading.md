# Upgrading and rollback

Back up the WordPress database and `wp-content` outside the public web root before changing either plugin. Keep the
Elementor Pro zip and licence details available until the switch has been accepted.

## Switch from Elementor Pro

1. Update Elementor (free) to a supported release and resolve any pending database updates.
2. Install the Modulix release zip and activate it while Elementor Pro remains active. Modulix runs only its
   readiness report in this state.
3. Run Tools -> Modulix or `wp modulix check`.
4. Review every missing widget, dynamic tag, document type and feature. Do not continue if a live document depends
   on a missing item. Review the [compatibility table](compatibility.md) for the active theme and plugins.
5. Record a baseline for the public pages and editor workflows that use Pro features. Include desktop and mobile
   pages, forms, account and checkout flows, popups and Theme Builder templates when the site uses them.
6. Deactivate Elementor Pro without uninstalling it. Modulix changes from check mode to full mode automatically.
7. Regenerate Elementor CSS and data, clear all page and object caches, and repeat the baseline checks.
8. Keep Elementor Pro installed but inactive until the rollback window has ended.

Modulix does not convert Elementor documents. Supported widgets and settings continue to use their existing stored
names. The readiness report is the gate for names that Modulix does not support.

## Upgrade from Modulix 0.7.0 or a 0.7.0 pre-release

1. Create the backup and baseline described above.
2. Replace the plugin with the new release zip. WordPress normally keeps the existing Elementor documents and
   Modulix settings.
3. Regenerate Elementor CSS and data and clear caches.
4. Run `wp modulix check` after every upgrade, whether or not Elementor Pro is installed. The command uses the bundled
   coverage catalog and is available in both check mode and full mode. Resolve any missing item the site still needs.
5. Treat the saved baseline as an additional check, then test forms, uploads, popups, Theme Builder conditions, Loop
   content and WooCommerce flows that the site uses.

The 1.0.0 release adds modules and assets but does not rename the existing 0.7 stored widget, tag or document names.

## Upgrade from Modulix 0.6.x or earlier

Versions through 0.6.x cannot be active beside Elementor Pro. Use this sequence:

1. Back up the database and `wp-content`, then deactivate the old Modulix version.
2. Install the new release zip.
3. Activate Elementor Pro, then activate the new Modulix version. The new version enters check mode.
4. Run the readiness report and resolve all required missing items.
5. Deactivate Elementor Pro. Modulix then enters full mode.
6. Regenerate Elementor CSS and data, clear caches and repeat the saved baseline checks.

Do not uninstall the old plugin before the backup. If WordPress cannot replace the directory during upload, remove
only the inactive plugin directory after confirming the backup, then upload the new zip.

## Roll back to Elementor Pro

1. Put the site in a maintenance window if forms, orders or account changes could occur during the switch.
2. Deactivate Modulix.
3. Activate the Elementor Pro release used before the switch, or another release already tested with the installed
   Elementor version.
4. Regenerate Elementor CSS and data and clear caches.
5. Test the saved baseline, including editor access, Theme Builder conditions, forms, popups and WooCommerce flows.

Do not run both plugins in full mode. With Elementor Pro active, a current Modulix release runs only the readiness
report. If the rollback needs an older Modulix version, deactivate Elementor Pro before activating that version.

## Roll back to an earlier Modulix release

1. Confirm that the earlier release supports every widget, tag and document type currently stored on the site.
2. Deactivate the current Modulix version.
3. Replace it with the saved earlier release zip and activate it only while Elementor Pro is inactive.
4. Regenerate Elementor CSS and data, clear caches and repeat the baseline checks.

Content created with a module introduced after the target release can render empty after a downgrade. Restore the
database backup as well as the plugin files if the upgrade changed content that the earlier release cannot read.
