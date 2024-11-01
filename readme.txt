=== WP Get Personal ===
Contributors: stevehenty, freemius
Donate link: https://wpgetpersonal.com/
Tags: email marketing,personalization,personalize pages,personalisation,personalizer,PURL
Requires at least: 4.0
Tested up to: 5.1
Stable tag: trunk

Get your list to love you! WP Get Personal allows you to personalize pages by adding a name to the end of the URL.

== Description ==

WP Get Personal allows you to personalise pages by adding a name to the end of the URL. It works well when used with an email system/autoresponder.


== Installation ==

**Manual installation**

1. Download the zipped file.
1. Extract and upload the contents of the folder to /wp-contents/plugins/ folder
1. Go to the Plugin management page of WordPress admin section and enable the 'Wp Get Personal' plugin

**Configuration**

1. Make sure you're using pretty permalinks
1. Go to a WordPress page and enable WP Get Personal virtual URLS
1. Insert the shortcode [wpgetpersonal] where you'd like the name to appear
1. Add the name to the end of the page URL e.g. mydomain.com/mypage/Name

== FAQ ==

= Where are the settings? =
You can find the settings page in the "Settings" menu section of your WordPress dashboard.

= How do I integrate with AWeber/GetReponse/MailChimp/etc? =

While composing your email in your email system. Add the first name personalization variable to the URL of your WP Get Personal Page.

**GetResponse**<br />
"http://wpgetpersonal.com/\[\[firstname&#093;&#093;"


**MailChimp**<br />
"http://wpgetpersonal.com/\*|FNAME|\*"

**iContact**<br />
"http://wpgetpersonal.com/\[fname\]"

**iContact**<br />
"http://wpgetpersonal.com/%User:FirstName%"

**Constant Contact**<br />
Please use the "Insert" toolbox they give you to insert the contact's first name. Look for "Contact details" and then select "First name". It will look like this after you insert it:<br />
"http://wpgetpersonal.com/First Name"<br />
<br>Note: if you just type in "First Name" or (Contact First Name) it won't work - you need to insert it using the toolbox.

**AWeber**<br />
"http://WPGetPersonal.com/secret/{!firstname_fix}"<br />
Note about AWeber: they don't support click tracking for this kind of link. If you need click tracking please use the following workaround:<br />
"http://wpgetpersonal.com/?wpgp=\{!firstname_fix\}"<br />
It's not as pretty but it works!

== Screenshots ==

A demo is worth a thousand screenshots:<br />
https://wpgetpersonal.com<br />
Enter your name and personalize the page!

== ChangeLog ==

= 1.5.2 =
* [fix] Fixed security issue.

= 1.5.1 =
* [fix] Fixed support for https.

= 1.5 =
* [add] Added support for Freemius.

= 1.4.12 =
* [fix] Fixed the CSV lookup to display the fallback text when row is not found.

= 1.4.9 =
* [fix] fixed an issue with AJAX to avoid conflicts with third party plugins

= 1.4.8 =
* [fix] fixed PHP warnings and added support for various csv line endings

= 1.4.7 =
* [fix] removed debug notices

= 1.4.6 =
* [fix] empty columns in CSV files now display as empty strings instead of the tokens

= 1.4.4 =
* [fix] fixed warning notice in WordPress 3.5

= 1.4.3 =
* [fix] fixed issue with pages that have parents
* [fix] on plugin deactivation settings are not removed (removed only on uninstall)

= 1.4 =
* new feature: look up data in a CSV file
* new feature: add additional text or punctuation when using personalization

= 1.3 =
* added geolocation data using the maxmind Javascript service
* added support for geolocation specific content
* remove options on deactivate

= 1.2.4 =
* added options page in WordPress settings section
* added setting for Cookie life in days
* added setting for section separator
* added activation settings
* changed auto update server

= 1.2.3 =
* added auto update
* fixed compatability issues with some plugins

= 1.2.1 =
* Fixed compatibility issues with some plugins
* Fixed problem with uninstall where some options weren't getting deleted
* Fixed warning in error logs for undefined option values

= 1.2 =
* Added support for homepage
* Added support for page and posts
* Added 30 day cookie
* Added user meta data for logged in users
* Added uninstall file
* Fixed virtual URL for logged in users
* Fixed url encoding for cookies
* Add link to instructions


== Upgrade Notice ==

= 1.5.1 =
* [fix] Fixed support for https.

= 1.5 =
* [add] Added support for Freemius.

= 1.4.12 =
* [fix] Fixed the CSV lookup to display the fallback text when row is not found.

= 1.4.8 =
* [fix] fixed PHP warnings and added support for various csv line endings

= 1.4.7 =
* [fix] removed debug notices

= 1.4.6 =
* [fix] empty columns in CSV files now display as empty strings instead of the tokens

= 1.4.5 =
* [fix] fixed warning notice in WordPress 3.5

= 1.4.3 =
* [fix] fixed issue with pages that have parents

= 1.4 =
* new feature: look up data in a CSV file
* new feature: add additional text or punctuation when using personalization

= 1.3 =
Added support for geolocation

= 1.2.4 =
Added settings page and additional configuration options.

= 1.2.3 =
Added automatic updates and fixed some compatability issues with other plugins.

= 1.0 =
Initial release.
