=== wp-mailman ===
Tags: admin,integration,email,manage
Requires at least: 2.0
Tested up to: 2.8.5

wp-mailman integrates Wordpress with the GNU Mailman mailing list manager.

== Description ==

wp-mailman integrates Wordpress with the [GNU Mailman](http://www.gnu.org/software/mailman/index.html) mailing list manager. As long as mailman runs on the same host as Wordpress, the Wordpress site admin can select one or more lists of those publicly available from mailman. Newly registered users will be subscribed to the selected list(s).

User can see their list of subscriptions and unsubscribe from mailman lists that they don't want anymore.

= Is this project dead? =

wp-mailman solved a particular problem for me, and continues to do so. If you happen to have the same requirements, I'm happy if my little plugin can be of any help. Besides from that, wp-mailman doesn't get much attention, so it can take a while until the next release. In the meantime, suggestions and patches are more than welcome. If you need help more desperately, drop me a mail under *datenimperator (at) me (dot) com*. I do paid consulting for a living and I'd surely be happy to help you out.

== Installation ==

1. Unzip mailman.zip to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Plugin configuration is under `Options > Mailman integration`

= First-time setup =

wp-mailman needs to know the path to your installation of GNU Mailman. Put in the path (like `/var/lib/mailman/`) into the field *Mailman home dir* and press *Check path*. If everything's alright, wp-mailman displays the version of your GNU Mailman software installed under that path.

wp-mailman verifies the path that you've entered by looking for a number of executable files under that path. In particular, it looks for:

* bin/add_members
* bin/remove_members
* bin/config_list
* bin/list_lists
* bin/list_members
* bin/version

All these files belong to the standard GNU Mailman installation. If one of them is missing, path verification fails.

= List activation =

After successful path verfication (see above), wp-mailman displays the list of configured Mailman lists along with list name and list comment. Activate the ones that should be accessible to users of your Wordpress site and optionally select those that new user accounts will be subscribed to.

== Change log ==
= 1.2 =
* Tested with WP 2.8.5
* Users can subscribe/unsubscribe using their profile page
* Bug fixes

= 1.1 =
* More error checks, better error messages
* Better UI compatibility with standard WP screens

= 1.0 =
* Initial release

== Frequently asked questions ==

= It gives me errors about files not found, or wrong permissions. =

wp-mailman heavily relies on correct file permissions. It executes programs on the web server to get information from Mailman, which is questionable, at least from a security point of view. However, there's no other way to get stuff out of the mailman control interface.

Best bet is to add the user that your web server is running under to the group *mailman* (or *_mailman* on some systems). Check your mailman installation for the correct value. wp-mailman does some checks to help you getting things straight and prints (hopefully) helpful error messages when needed.
