# Pubvana

### Blogging and Small business CMS

Pubvana is a re-brand of Open Blog v3 (with added functionality).  See Change Log below
  	
## BETA - Do Not use in production -
  		  
#### Composer Installation

* Latest Stable - `composer create-project enlivenapp/pubvana [project folder]` 
* Developers - `composer create-project --stability dev enlivenapp/pubvana [project folder]`   


#### Download

Visit https://pubvana.org/downloads to download the latest version of Pubvana to install on your self-hosted server.

#### Change Log

v 1.0.0 (First Pubvana Release after Open Blog v3.0.0)

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

* Automated Theme & Widget updating
* Social login/registering
* Author: info card/bio blip/etc
* WP migration to Pubvana

#### Premium Widgets: Visit https://pubvana.org

* Advanced Login
* Gallery
* Google Calendar & Maps (Maybe AdSense)
* YouTube Channel Feed
* and more...

