koken-xml-sitemap
=================
This is a plugin for the Koken cms/image publishing system (http://koken.me/).
It generates an xml sitemap which can be submited to search engines like Google, Bing etc.

__NOTE: The plugin does not generate an actual file. It catches the request to a configurable url (e.g. /sitemap.xml) and outputs the sitemap dynamically (Cached though unless explcitly disabled)__

##Usage
###Install
Download or clone this repository and copy everything to:
 ```
storage/plugins/pulponair-xml-sitemap/
```
###Configure the plugin
Login to your koken installation and switch to settings->plugin. You should see a new plugin entry called "XML Sitemap". If not, you might need to clear the "system caches" and/or reload the koken admin interface.

Next click on setup and configure the plugins behavior.
You need to provide at least a sitemap url!

**Example setup**
![ScreenShot](http://i.imgur.com/XaPueCk.jpg)

When finished: enabled the plugin

You can check the output by surfig: http://yourwebsite.tld/[sitemap_url]

##Troubleshooting

###Surfing the the sitemap url gives you a 404 error
If you do not have url rewriting enabled or if does not work for you for any reasons you won't be able to reach the sitemap via http://yourwebsite.tld/[sitemap_url].

In that case use: http://yourwebsite.tld/index.php?[sitemap_url]

You might also want to add a sitemap entry to your robots.txt file in that case.
See: http://www.advancedhtml.co.uk/robots-sitemaps.htm

##Feedback
Should you experience a problem, find a bug or feel that anything is missing, just file an issues and will look into it.

Any feedback would be highly appreciated