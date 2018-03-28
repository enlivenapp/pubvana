# Pubvana

### Blogging and Small business CMS

Pubvana is a re-brand of Open Blog v3 (with added functionality).  See Change Log below.
  		  
#### Installation

Visit https://pubvana.org/installation for more information.

* Latest Stable - `composer create-project enlivenapp/pubvana [project folder]` 
* Developers - `composer create-project --stability dev enlivenapp/pubvana [project folder]`   

## Bug Reports, Feature Requests

Please use the [Issues Tracker](https://github.com/enlivenapp/pubvana/issues).

## Links

[pubvana.org Home](http://pubvana.org) (Working Example and Live Blog)

Pubvana Addon Store (Themes, Widgets, and other Addons) - Coming Soon

[Facebook Page](https://www.facebook.com/pubvana.org)

[User Docs](http://pubvana.org)

## License Notice

Pubvana is released under the MIT Open Source License.

## Contributors & Team Members 

- Enliven Applications


## Translators & Translations

_Translators Wanted!_  

If you would like to help translate files, please fork this repo and send a PR. 

* French, Indonesian, and Portuguese need updates.

Please include a README.me update under 'Translators' with:

* Your Name
* Link to your site/github/wherever. (optional)

  
* French 
  - [Paul DUBOT](https://github.com/keeganpa)
  - [Léonard GAURIAU](https://github.com/leoDisjonct)
  - [Clément TRASSOUDAINE](https://github.com/intv0id)
  - [Jean-Baptiste VALLADEAU](https://github.com/ignamarte)
  - [Rhagngahr](https://github.com/Rhagngahr)

* Indonesian
  - [Suhindra](https://github.com/suhindra)

* Portuguese
  - [Samuel Fontebasso](https://github.com/fontebasso)



#### Change Log

##### v1.0.1

* Introduced blog post pagination in categories, archives, and general blog post display.
* Bug Fix - Basic Login Widget displaying Title when user logged in.
* Bug Fix - Primary Key/Auto_increment didn't happen on widget related tables.
* Bug Fix - Added Facebook Comment Plugin to Post comments
* Bug Fix - Archives header showed 'Jan' no matter the month passed to it.


##### v1.0.0 (First Pubvana Release after Open Blog v3.0.0)

* Added post and page search capabilities
* Added sitemap.xml(valid xml output) & robots.txt generator
* Added Google Analytics tracking
* Added Contact form (controller)
* Added `date_modified` to pages & posts
* Moved all google services to Google tab in Settings
* Refactored Open Graph output for pages & posts in `<head>`
* Added Twitter meta data output in `<head>`
* Added Media Manager (Admin)
* Added Theme Options Manager (Admin)
* Modified Settings display of tabs to vertical display
* Added nestedSortable to Navigation (admin) to add the ability to create dropdowns in main nav
* Changed comments to Comment System.  Added support for Facebook Comment Plugin
* Admin theme now looks for [group_permission:name]\_hdr when showing the title in the admin area.
* We now count (and can display) how many views a post has.  `post_count`
* Created Widget system
* updated Admin dashboard 
* updated /installer/ `mcrypt_create_iv()` error.
* Added `lang()`s to admin dashboard
* Added Confirm dialog to all remove/delete functions in Admin Dashboard


New Basic Widgets(v1.0.0)

* HTML - Add arbitrary HTML to your website
* Login - Simple widget to login to your site
* Recent Posts - A list of recent blog posts
* Links - Links to other sites
* Categories - List of blog categories
* Archives - List of older posts
* Featured Post
* Popular Posts

Widgets above are free and come installed.  A store will be created for other widgets to be purchased.




### Todo

* Automated Theme & Widget updating (from API)
* Social login/registering/posting/etc
* Author: info card/bio blip/etc
* WP migration to Pubvana

#### Premium Widgets: Visit https://pubvana.org

* Advanced Login
* Gallery
* Google Calendar & Maps (Maybe AdSense)
* YouTube Channel Feed
* and more...

