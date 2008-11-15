=== Hyper Cache ===
Tags: cache,chaching
Requires at least: 2.1
Tested up to: 2.6.3
Stable tag: 2.0.2
Donate link: http://www.satollo.com/english/donate
Contributors: satollo,momo360modena

Hyper Cache is an extremely aggressive cache for WordPress.

== Description ==

_Hyper Cache 2.0 has a lot of new features and was widely rewritten. If you
have problems with it, roll back to version 1.2.6 or 1.1.1 
(http://wordpress.org/extend/plugins/hyper-cache/download/)._

Hyper Cache is a new cache system for WordPress, specifically written for
people which have their blogs on *low resources hosting provider* 
(cpu and mysql).

Hyper Cache has the nice feature to be compatible with the plugin "wp-pda"
which enables a blog to be *accessible from mobile devices* showing the
contents with a different ad optimized theme.

Hyper Cache can manage (both) *plain and gzip compressed pages*, reducing the
bandwidth usage and making the pages load faster.

Hyper Cache can do *cache autoclean* to reduce the disk usage removing the old 
cached pages at specified intervals of time.

Hyper Cache caches the not found requests, the WordPress redirect requests, 
the feed requests.

Hyper Cache can be easly translated and the translation tested without compile
a language file: just copy the en_US.php file and start to translate.

Global Translator detection.

More information on Hyper Cache page (below) or write me to info@satollo.com.

http://www.satollo.com/english/wordpress/hyper-cache

Thanks to:
- Amaury Balmer for internationalization and other modifications
- Frank Luef for german translation
- HypeScience, Martin Steldinger, Giorgio Guglielmino for test and bugs submissions
- Ishtiaq to ask me about compatibility with wp-pda
- Gene Steinberg to ask for an autoclean system
- many others I don't remember

To do:
- make the cache directory configurable because if you are on NFS the plugin can load too much the server (Deepak Gupta)
- execute the cache page so it's possible to insert php code - has many drawbacks I need to evluate well how to implement this feature (RT Cunningham)

== Installation ==

1. Put the plugin folder into [wordpress_dir]/wp-content/plugins/
2. Go into the WordPress admin interface and activate the plugin
3. Optional: go to the options page and configure the plugin

Before upgrade DEACTIVATE the plugin and then ACTIVATE and RECONFIGURE!

== Frequently Asked Questions ==

No questions have been asked.

== Screenshots ==

No screenshots are available.