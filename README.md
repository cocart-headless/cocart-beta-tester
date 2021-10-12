<h1 align="center">CoCart - Beta Tester</h1>

<p align="center"><img src="https://raw.githubusercontent.com/co-cart/co-cart/master/.github/Logo-1024x534.png.webp" alt="CoCart" /></p>

**CoCart Beta Tester** allows you to try out new versions of CoCart Lite before they are officially released.

**Use with caution, not on production sites. Beta releases may not be stable.**

After activation, you'll be able to choose an update channel:

1. Beta - Update to beta releases, RC, or stable, depending on what is newest.
2. Release Candidate - Update to RC releases or stable, depending on what is newest.
3. Stable - No beta updates. Default WordPress behavior.

These will surface pre-releases via automatic updates in WordPress. Updates will replace your installed version of CoCart Lite.

**Note**, this will not check for updates on every admin page load unless you explicitly tell it to. You can do this by clicking the "Check Again" button from the WordPress updates screen or you can set the `COCART_BETA_TESTER_FORCE_UPDATE` to true in your `wp-config.php` file.

## Installation

You may:

1. [Download the latest release](https://github.com/co-cart/cocart-beta-tester/releases) from the GitHub repository.
2. Go to **WordPress Admin > Plugins > Add New**.
3. Click **Upload Plugin** at the top.
4. **Choose File** and select the `.zip` file you downloaded in **Step 1**.
5. Click **Install Now** and **Activate** the CoCart - Beta Tester.

## Settings

If you wish to change which builds become available, hover over the admin bar in the dashboard and click "Channel: Beta". Then you will be given the option to select between Beta, Release Candidates and Stable Releases.

You can also enable automatic updates.

## Switch Versions

If you wish to install a specific version, hover over the admin bar in the dashboard and click "Switch versions". Then you will be given a list of releases to select and switch over. Also helpful if you wish to roll back a release.

## Frequently Asked Questions

### Does this allow me to install multiple versions of CoCart Lite at the same time?

No, updates will replace your currently installed version of CoCart Lite. You can switch to any version from this plugin via the interface however.

### Where do updates come from?

Updates are downloaded from [the CoCart GitHub repository](https://github.com/co-cart/co-cart) where we tag prerelease versions specifically for this purpose.

### Does this rollback my data?

This plugin does not rollback or update data on your store automatically.

Database updates are manually ran like after regular updates. If you downgrade, data will not be modified. We don't recommend using this in production.

### How do I update the CoCart Beta Tester?

A plugin called [Git Updater](https://git-updater.com/) is supported. Install this plugin and it will identify CoCart Beta Tester. If an update is available, Git Updater will provide it via WordPress Updates.

### Where can I report bugs or contribute to CoCart Beta Tester?

Bugs can be reported to the [CoCart Beta Tester GitHub issue tracker](https://github.com/co-cart/cocart-beta-tester/issues).
