#WPextUsers
This is a Wordpress hack that allows you to have multiple WP installs (each with their own separate files and DB for content) and they all share one DB for user information. This allows each user to have a single login for multiple wordpress sites. The hack modifies Wordpress installations to refer to an external database when accessing the **wp_users** and **wp_usermeta** tables.

###Project goals (have been met)
* Create a proof of concept
* Modify a WP installs with 1 click (and make the installer easy to tweak)
* Share the code for others to use and improve.

###Hack Status
This is a proof of concept. It is not production ready yet.

I don't have time to develop it more. I suggest you fork it and improve it.

###Benefits of using this hack
* It's simple
* It works without any extra software or services

###Drawbacks of using this hack
* Novices can't use the automatic upgrade from Wordpress, you need to test it before making it available to them.

###Compatible with WP versions
* 4.0

###Successfully tested
* Log in and log-out using a single user account on multiple sites.
* Change password at any site, that change affects all sites. (obviously)
* Change usermeta data on one site for a user's profile and see it updated on the other site.

###Current Limitations
* When a user logs in at one WP site, they are not automatically logged in on another WP site. Users must log in at each site (same username and password). If you want to log in at one site and have authentication cookies for every site, you would need more WP modifications than just DB access.

###Requirements
* Same table prefix: All DB's in the entire set must use the same wordpress DB table prefix (*wp_* by default).
* Write permissions: The installer needs write permission to files and directories within the wordpress directory.
* Re-hack on update: After updating wordpress you might have to re-hack it.
* Files: Only one: *_WPextUsersInstall.php*

###The main principles of the hack
* Wordpress defines a *wpdb* class and creates a single instance of it called *$wpdb* to access the default database. I've created another instance of that class called *$wpUsersDB* and initialized it in the same way.
* Code that was referring to *$wpdb* to access *user data* has been modified to refer to *$wpUsersDB* instead.
* Tables that are not used are renamed by the installer in order to avoid confusion and also cause WP to produce an error if it tries to access the tables on the wrong DB. (An error is a much better indication of a missing code-patch than strange un-detected behavior).

###Suggestions if you want to go into production
* Test emails.
* Test forgot password.
* Test CRUD (create read update delete) users, posts, pages.
* Read the code to see what get's modified, check that everything looks okay.
* Check/modify /wp-admin/user-new.php (I never got around to it, maybe more files need attention).
* Create a test server for testing wordpress upgrades with WPextUsers
* Modify the wordpress automatic upgrade button so that it pulls the modified wordpress files from the test-server instead of Wordpress.

###What could go wrong?
* I'm not very familiar with the Wordpress. I've wildly monkey patched a few files that 'seem' to be 'all about users', replacing all references from the default DB to the Users DB, and have only skim read the WP code. I've not checked every single replacement carefully. Some replacements might be missing, some might be incorrect. Read the resulting code yourself. Or test thoroughly. It's your responsibility to make sure your site works.
* If WP tries to do any table joins between user tables and other tables, that simply wouldn't work and would result in an error. Again I'm a Wordpress novice so I don't know what the risk of this is. I saw JOIN in one or two places, I haven't checked what tables they're joining.

###How do you know if it's working?
* Firstly it should be obvious when you modify user data between two sites.
* Secondly (you should familiarize yourself with your dev environment) I used to get errors whenever WP tried to access an invalid DB object or tried to access a table that was not there. So if there are no errors that's a pretty good indication it's working. I didn't get any errors after completing the code as is, and my tests were successful.

###Future wishlist
* Modify WP's automatic upgrade button to pull the upgrade from a test-server instead, so that novice users can upgrade WP any time.
* A remote upgrade button would be nice. Tick the sites in a list, and click upgrade selected sites.

#####Initial development
WPextUsers was initially developed with WP version 4.0: http://wordpress.org/wordpress-4.0.tar.gz

###How to install (simple explanation)
1. Make a WP install for your Users DB and delete the files afterwards.
2. Make another WP install with its own DB.
3. Copy _WPextUsersInstall.php into the WP directory.
4. Open it in your browser, complete the 4 fields, click *Install Now*
5. Repeat steps 2-4 for more installs.

###How to install (detailed explanation)
1. Create a MySQL DB that will hold your *wp_users* & *wp_usermeta* tables (lets pretend the DB is called *wpusers*)
2. Create a normal WP installation that uses the *wpusers* database. Complete all the WP install questions until you see a WP login screen (let's say you call the admin account *mainAdmin*).
3. Now that the *wpusers* DB is initialized you can delete the wordpress files & dirs, just keep the database.
4. Create a new WP installation for the first site, let's pretend it's called *site A*. Complete all the WP install questions until you see a WP login screen
5. After you've completed the installation for *site A*, make sure the webserver (Apache, Nginx or HHVM) has write permissions to the files and directories inside the installation.
6. Put this php file (_WPextUsersInstall.php) in the root of *site A* and open it in your browser.
7. Fill in the database details for your central USERS database that will hold your *wp_users* & *wp_usermeta* tables for all of your sites. (*wpusers* is the name of that DB in this example)
8. Click install now
9. Now you can log in with the username *mainAdmin* at *site A*
10. Repeat steps 5-9 to add more sites

###Tips on setting file permissions
#####While you're doing the installation, you can set convenient permissions like this: (where /var/www is the root of your WP installation)
`sudo find /var/www -type d -exec chmod 777 {} +`
`sudo find /var/www -type f -exec chmod 666 {} +`
#####After you've finished the installation, more secure permissions might be something like:
`sudo find /home/web -type d -exec chmod 755 {} +`
`sudo find /home/web -type f -exec chmod 644 {} +`

