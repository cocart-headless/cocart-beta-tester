<p align="center"><img src="https://raw.githubusercontent.com/co-cart/co-cart/trunk/.wordpress-org/banner-772x250.png" alt="Headless API for Developers" /></p>

### CoCart Beta Tester allows you to try out new versions of CoCart before they are officially released.

**Use with caution, not on production sites. Beta releases may not be stable.**

After activation, you'll be able to choose an update channel:

1. Nightly - Update to experimental functionality, may include features that are unstable and may not move forward into a beta release.
2. Beta - Update to beta releases, RC, or stable, depending on what is newest.
3. Release Candidate - Update to RC releases or stable, depending on what is newest.
4. Stable - No beta updates. Default WordPress behavior.

These will surface pre-releases via automatic updates in WordPress. Updates will replace your installed version of CoCart.

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

### Does this allow me to install multiple versions of CoCart at the same time?

No, updates will replace your currently installed version of CoCart. You can switch to any version from this plugin via the interface however.

### Where do updates come from?

Updates are downloaded from [the CoCart GitHub repository](https://github.com/co-cart/co-cart) where we tag prerelease versions specifically for this purpose.

### Does this rollback my data?

This plugin does not rollback or update data on your store automatically.

Database updates are manually ran like after regular updates. If you downgrade, data will not be modified. We don't recommend using this in production.

### How do I update the CoCart Beta Tester?

A plugin called [Git Updater](https://git-updater.com/) is supported. Install this plugin and it will identify CoCart Beta Tester. If an update is available, Git Updater will provide it via WordPress Updates.

### Where can I report bugs or contribute to CoCart Beta Tester?

Bugs can be reported to the [CoCart Beta Tester GitHub issue tracker](https://github.com/co-cart/cocart-beta-tester/issues).

---

## CoCart Channels

We have different channels at your disposal where you can find information about the CoCart project, discuss it and get involved:

[![Twitter: cocartapi](https://img.shields.io/twitter/follow/cocartapi?style=social)](https://twitter.com/cocartapi) [![CoCart GitHub Stars](https://img.shields.io/github/stars/co-cart/co-cart?style=social)](https://github.com/co-cart/co-cart)

<ul>
  <li>📖 <strong>Docs</strong>: this is the place to learn how to use CoCart API. <a href="https://cocartapi.com/docs/">Get started!</a></li>
  <li>👪 <strong>Community</strong>: use our Discord chat room to share any doubts, feedback and meet great people. This is your place too to share <a href="https://cocartapi.com/community/?utm_medium=repo&utm_source=github.com&utm_campaign=readme&utm_content=cocart">how are you planning to use CoCart!</a></li>
  <li>🐞 <strong>GitHub</strong>: we use GitHub for bugs and pull requests, doubts are solved with the community.</li>
  <li>🐦 <strong>Social media</strong>: a more informal place to interact with CoCart users, reach out to us on <a href="https://twitter.com/cocartapi">Twitter.</a></li>
  <li>💌 <strong>Newsletter</strong>: do you want to receive the latest plugin updates and news? Subscribe <a href="https://xyz.us1.list-manage.com/subscribe?u=48ead612ad85b23fe2239c6e3&id=d462357844i">here.</a></li>
</ul>

---

## Get involved

Do you like the idea of creating a headless store with WooCommerce? Got questions or feedback? We'd love to hear from you. Come [join our community](https://cocartapi.com/community/?utm_medium=repo&utm_source=github.com&utm_campaign=readme&utm_content=cocart)! ❤️

CoCart also welcomes contributions. There are many ways to support the project! If you don't know where to start, this guide might help >> [How to contribute?](https://github.com/co-cart/co-cart/blob/development/.github/CONTRIBUTING.md)

---

## Credits

Website [cocartapi.com](https://cocartapi.com) &nbsp;&middot;&nbsp;
GitHub [@co-cart](https://github.com/co-cart) &nbsp;&middot;&nbsp;
Twitter [@cocartapi](https://twitter.com/cocartapi)

---

CoCart is developed and maintained by [Sébastien Dumont](https://github.com/seb86).
Founder of [CoCart Headless, LLC](https://github.com/cocart-headless).

Website [sebastiendumont.com](https://sebastiendumont.com) &nbsp;&middot;&nbsp;
GitHub [@seb86](https://github.com/seb86) &nbsp;&middot;&nbsp;
Twitter [@sebd86](https://twitter.com/sebd86)