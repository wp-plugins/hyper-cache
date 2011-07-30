=== Hyper Cache ===
Tags: cache,chaching,speed,performance,super cache,wp cache
Requires at least: 2.5
Tested up to: 3.2.1
Stable tag: trunk
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=2545483
Contributors: satollo,momo360modena

Hyper Cache is flexible and easy to configure cache system for WordPress. It's aim is to work on any installation.

== Description ==

Hyper Cache is a new cache system for WordPress, specifically written for
people which have their blogs on low resources hosting provider 
(cpu and mysql). It works even with hosting based on Microsoft IIS (just tuning
the configuration). It has three invalidation method: all the cache, single post
based and nothing but with control on home and archive pages invalidation.

It has not yet tested for multisite configuration (WordPress 3.0 feature).

Some features:

* compatible with the plugin wp-pda which enables a blog to be *accessible from mobile devices*
* manages (both) *plain and gzip compressed pages*
* autoclean system to reduce the disk usage
* 404 caching
* redirects caching
* easy to configure
* Global Translator compatibility
* Last Modified http header compatibility with 304 responses
* compressed storage to reduce the disk space usage
* agents, urls and cookies based rejection configurable
* easy to integrated with other plugins

More can be read on the [official plugin page](http://www.satollo.net/plugins/hyper-cache) and write me
if you have issues to info@satollo.net.

**Check out my other plugins**:

* [Post Layout](http://www.satollo.net/plugins/post-layout "Post Layout WordPress plugin: the easy way to enrich your posts")
* [Comment Notifier](http://www.satollo.net/plugins/comment-notifier "Keep your blog discussions on fire")
* [Feed Layout](http://www.satollo.net/plugins/feed-layout "Feed Layout WordPress plugin: the easy way to enrich your feed contents")
* [Dynatags](http://www.satollo.net/plugins/dynatags "Dynatags WordPress plugin: Create your own custom short tag in seconds")
* [Header and Footer](http://www.satollo.net/plugins/header-footer)
* [Newsletter](http://www.satollo.net/plugins/newsletter)

Thanks to:

* Amaury Balmer for internationalization and other modifications
* Frank Luef for german translation
* HypeScience, Martin Steldinger, Giorgio Guglielmino for test and bugs submissions
* Ishtiaq to ask me about compatibility with wp-pda
* Gene Steinberg to ask for an autoclean system
* Marcis Gasun (fatcow.com) for Bielorussian translation
* many others I don't remember

== Installation ==

1. Put the plugin folder into [wordpress_dir]/wp-content/plugins/
2. Go into the WordPress admin interface and activate the plugin
3. Optional: go to the options page and configure the plugin

Before upgrade DEACTIVATE the plugin and then ACTIVATE and RECONFIGURE!

== Frequently Asked Questions ==

**How can I submit a bug?**

Write me to info@satollo.com, please, it's the quicker way to have it fixed. You can write to the 
WordPress forum, too, but I read it rarely.

**Where can I find versions history?**

On this page: [Post Layout versions](http://www.satollo.com/english/wordpress/post-layout/versions).

**Other FAQs?**

I'm collection tips and FAQ on [this page](http://www.satollo.com/english/wordpress/hyper-cache/configuration) 
so I can update it more easly.
    
== Screenshots ==

No screenshots are available.

== Changelog ==

= 2.8.5 =

* fixed the "is_home" warning issue

= 2.8.4 =

* fixed the single page invalidation

= 2.8.3 =

* fixed the clean from admin panel

= 2.8.2 =

* moved the cache folder to wp-content/cache/hyper-cache
* configuration panel has no more expandable sections
* the cached pages are no more deleted on update

= 2.8.1 =

* fixed the Last Modified header (thanks Yuri C.)