=== Plugin Name ===
Contributors: afex
Tags: conversion, video game, team, tribe, clan, guild
Requires at least: 2.2
Tested up to: 2.2
Stable tag: 0.1b

tribe is a conversion plugin which turns a normal Wordpress installation into a team management site for competitive video game teams.

== Description ==

tribe aims to provide team leaders with all the tools they'll need in order to maintain a web presence for their team.  It provides both public facing pages and widgets as well as the administration screens to manage members, matches, etc.

Currently in development, this plugin will see rapid change as it is developed alongside [my team's web site](http://www.teaminq.com/).

Planned features include

* Roster and member profile management integrated with Wordpress' existing user system.
* Enhanced user profiles including photo, location, age, system specs, and team specific info (date joined, favorite loadout, etc).
* Member blogs so that your team members can have their own voice on their teams site.
* View your match history and plan your upcoming match by letting your members note their intent to attend.

Feel free to try it out the development version as-is, but please consider that until 1.0 it will be incomplete.

== Installation ==

1. Upload all plugin files to the `/wp-content/plugins/tribe/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Enjoy! Please read the detailed usage guide for more information

== Frequently Asked Questions ==

= What about MMO guilds? =

Currently the design of tribe is centered around competitive teams playing FPS games.  As the plugin matures, more general team management things may be introduced, but its focus will remain the same.  (i.e. I don't ever foresee a DKP system being coded into tribe)

== Version Map ==

* 0.1b
** Member blogs
** User profile extensions
** Roster and profile pages
* 0.2b
** Match History page
** Upcoming Matches page
* 0.3b
** Recruitment form
* 0.4b
** Member Match History page
** Member Keybinds page
** Member Screenshots page
* 0.5b
** Next Match widget
** Match History widget
** Server status widget

== Usage Guide ==

= Member Roles =

In tribe, some of the default Wordpress user roles are replaced by team-specific ones.  Administrator becomes Team Captain, Editor becomes Coordinator, and Author becomes Member.  This allows you to organize your team's roster while providing varying levels of administrative access to your team leaders.  Note: the 'admin' user is excluded from being a team member.

= Profile extension =

tribe adds fields to each user's profile for team-related information.  These fields include:

* Status (Active, Inactive)
* Join Date
* Age
* Location
* Quote
* System Specs
* Q & A

In the main plugin options page, any Team Captain can create 'Questions' which will be displayed on each member's profile admin screen.  Your team members can answer these questions so that the resulting Q&A can be displayed on their profile page.

= Categories =

Upon activation, tribe will create various categories.  These are:

* Team News
* Members

Any post which is assigned to the 'Team News' category will automatically have its comments and pings disabled.  In the future, this will be a plugin option.

Underneath the 'Members' category, a category will be created for each team member.  Currently any member can post to any category, but I may add in some extra permission code in the future.  For now just tell your team to keep it to their own category! :]  By having separate categories for each member, it allows you to have links such as the following: http://www.yourteam.com/blogs/members/nickname.  (tribe defaults your category base to '/blogs', this can be changed under Options -> Permalinks)

= Pages =

Upon activation, tribe will also create various pages.  These are:

* Roster

Underneath the 'Roster' page, a page will be created for each team member.  When the 'Roster' page is viewed, the plugin will instead display the 'roster.php' template located in the plugin directory.  To override this template, copy it into your theme directory and edit as needed.  Use the `tribe::get_members($type)` function to retrieve you team members.

When any page underneath 'Roster' is viewed, the plugin will instead display the 'profile.php' template located in the plugin directory.  To override this template, copy it into your theme directory and edit as needed.

Remember to keep the original templates in the plugin directory in case your alterations break the template and you need to start over.