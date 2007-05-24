<?php
/*
Plugin Name: tribe
Plugin URI: http://www.afex2win.com/stuff/tribe/
Description: Turns Wordpress into a video game tribe/clan/guild/team management system
Version: 0.1b
Author: Keith 'afex' Thornhill
Author URI: http://www.afex2win.com/
*/

/*
Ok, but what about match history, upcoming games, etc
12:48 afex basically we just need to identify what we want our site to do, team-wise
12:48 afex just like what you just posted
12:49 Colosus Ok. So here's my thoughts. A news page obviously. A roster page that has user image, quote, date of last blog post, date of joining the team, favorite class. This "profile" also links to their personal blog.
12:50 Colosus A match history page that shows a high-level win/loss record and scores
12:50 Colosus And then links to match details page that has screen caps of the score screens, who played in the match and links to public demos
12:51 Colosus A forum obviously
*/

/*
    04:07 Colosus <Colosus> Basically, I am thinking of having the match page having different boxes for each match (about 10 per page). Inside each box would be the maps played, who it was against, dates played, scores for each map and a screenshot of the map/score screen.
    04:07 Colosus <Colosus> By clicking on the map, that would take you to that map/match details page where it would list out who played in the match and a writeup of what happened.
    04:07 Colosus <Colosus> The team roster page would be kind of like the TW staff page where each user has a profile picture, name, location, date joined, age and a small blurb about themselves. It would then link to a page like the Insider page you made where it has a Q&A section of basic info, system setup, keybinds, etc plus news (blog) that the user can edit and their own screenshot section.
    04:07 Colosus <Colosus> The upcoming match page would be just like the match details, but without all the extra info like who played and a writeup. Just the maps being played and against who.
*/

/*
 * main category for front page news
 * individual categories for each member
    permissions need to restrict their posting abilities
 * roster page listing all team members
 * additional user info for member stuff
*/

class tribe {
    function activate() {
        $roles = get_option('wp_user_roles');
        $roles['administrator']['name'] = "Team Captain";
        $roles['administrator']['capabilities']['personal_cat'] = 1;
        $roles['editor']['name'] = 'Coordinator';
        $roles['editor']['capabilities']['personal_cat'] = 1;
        $roles['author']['name'] = 'Member';
        $roles['author']['capabilities']['personal_cat'] = 1;
        update_option('wp_user_roles', $roles);
        
        update_option('users_can_register', 1);
        update_option('category_base', '/blogs');

        if (get_cat_ID('Team News') <= 1) {
            wp_create_category('Team News');
        }        
        if (get_cat_ID('Members') <= 1) {
            wp_create_category('Members');
        }
        
        if (get_page_by_title('Roster') == null) {
            $args = array(
                        'post_title' => 'Roster',
                        'post_content' => '[tribe-roster]',
                        'post_type' => 'page',
                        'post_status' => 'publish',
                        'comment_status' => 'closed',
                        'ping_status' => 'closed'
                    );
            wp_insert_post($args);
        }
        
        // TODO: find all admins, editors, and authors and create pages and categories
    }
    
    function deactivate() {
        $roles = get_option('wp_user_roles');
        $roles['administrator']['name'] = "Administrator";
        unset($roles['administrator']['capabilities']['personal_cat']);
        $roles['editor']['name'] = 'Editor';
        unset($roles['editor']['capabilities']['personal_cat']);
        $roles['author']['name'] = 'Author';
        unset($roles['author']['capabilities']['personal_cat']);
        update_option('wp_user_roles', $roles);
        
        update_option('users_can_register', 0);
        update_option('category_base', '');
        
        // i wish i could hide team news and members cats here, oh well
        // don't want to delete them since we would lose the association
        // b/t those cats and their existing posts.
    }
    
    function dashboard() {
        echo "<pre>";
        echo "</pre>";
    }
    
    function profile_update($user_id) {
        $user = new WP_User($user_id);

        // TODO: bulk update of role doesn't trigger this action

        if ($user->allcaps['personal_cat']) {
            if (get_cat_ID($user->user_nicename) <= 1) {
                // they don't have a category yet, lets create one underneath 'Members'
                $member_cat_ID = get_cat_ID('Members');
                wp_insert_category(array('cat_name' => $user->user_nicename,
                                         'category_parent' => $member_cat_ID));
            }
            // see if we need to create a roster profile page
            if (!get_page_by_title($user->user_nicename)) {
                $roster_page = get_page_by_title('Roster');
                $args = array(
                            'post_title' => $user->user_nicename,
                            'post_content' => "[tribe-profile $user->ID]",
                            'post_type' => 'page',
                            'post_status' => 'publish',
                            'post_parent' => $roster_page->ID,
                            'comment_status' => 'closed',
                            'ping_status' => 'closed'
                        );
                wp_insert_post($args);    
            }
        } else {
            // check if they have a leftover cat that needs to be cleaned up
            $user_cat_ID = get_cat_ID($user->user_nicename);
            if ($user_cat_ID > 1) {
                wp_delete_category($user_cat_ID);
            }
            // clean up roster profile page
            $user_page = get_page_by_title($user->user_nicename);
            if ($user_page) {
                wp_delete_post($user_page->ID);
            }
        }
        
    }
    
    function replace_page($text) {
        global $wpdb;
        
        if (preg_match('/\[tribe-roster\]/', $text)) {
            $part = get_roster_output();
            $text = preg_replace('/\[tribe-roster\]/', $part, $text);
        } else if (preg_match('/\[tribe-profile (.*)\]/', $text, $matches)) {
            $part = get_profile_output($matches[1]);
            $text = preg_replace('/\[tribe-profile .*\]/', $part, $text);
        }
        return $text;
    }
    
    function get_members($type = 'author') {
        $users = get_users_of_blog();
        
        $return_users = array();
        foreach ($users as $user) {
            $user_roles = unserialize($user->meta_value);
            if ($user_roles[$type] && $user->user_login != 'admin') {
                $return_users[] = new WP_User($user->user_id);
            }
        }
        
        return $return_users;
    }
    
    function get_roster_output() {
        ob_start();
        include(dirname(__FILE__).'/roster.php');
        return ob_get_clean();
    }
    
    function get_profile_output($user_id) {
        ob_start();
        include(dirname(__FILE__).'/profile.php');
        return ob_get_clean();
    }
    
    function publish_post($post_id) {
        global $wpdb;
        
        $cat_id = get_cat_ID('Team News');
        $is_news = $wpdb->get_var("select count(*) from $wpdb->post2cat where post_id = $post_id and category_id = $cat_id");
        if ($is_news) {
            $wpdb->query("update $wpdb->posts set comment_status = 'closed', ping_status = 'closed' where ID = $post_id");
        }
    }
    
    function user_options_form() {
        global $profileuser;
        
        ?>
        <fieldset>
            <legend>tribe Member Profile</legend>
            <p><label>Status:<br />
            <select name="tribe_status">
                <option<?= ($profileuser->tribe_status=='Active'?' selected':'') ?>>Active</option>
                <option<?= ($profileuser->tribe_status=='Inactive'?' selected':'') ?>>Inactive</option>
            </select></label></p>
            <p><label>Join Date: (mm/dd/yyyy)<br />
            <input type="text" name="tribe_joindate" value="<?= htmlspecialchars($profileuser->tribe_joindate) ?>" /></label></p>
        </fieldset>
        <?
    }
    
    function save_user_options() {
        global $wpdb, $userdata;

        update_usermeta($userdata->ID, 'tribe_status', $wpdb->escape($_POST['tribe_status']));        
        update_usermeta($userdata->ID, 'tribe_joindate', $wpdb->escape(stripslashes($_POST['tribe_joindate'])));
    }
    
    function widget_init() {
        if ( !function_exists('register_sidebar_widget') )
    		return;
    	
    	function future_matches($args) {
    	    extract($args);
    	    $title = 'Future Matches';
    	    
            echo $before_widget . $before_title . $title . $after_title;
            ?>
            <div class="bigtextc">Inquisition<br />

            <div class="vs"><div class="hidden"><h3>Vs</h3></div></div>
            Judean People's Front</div>
            <div class="hidden"><br /></div>
            <div class="sepline"></div>
            <span class="smtextw">Date</span>:&nbsp;12/20/2007<br />
            <span class="smtextw">Time</span>:&nbsp;12:00pm EST<br />
            <span class="smtextw">Server</span>:&nbsp;Crack Suicide Squad!

            <div class="morelink"><a href="">more&nbsp;&gt;</a></div>
            <?
            echo $after_widget;
    	}
    	
    	function server_status($args) {
    	    extract($args);
    	    $title = 'Our Servers';
    	    
            echo $before_widget . $before_title . $title . $after_title;
            ?>
            <div class="sbullet"><b>Server 1</b></div>
            <span class="smtextw">Name</span>:&nbsp;Server Name<br />
            <span class="smtextw">Active</span>:&nbsp;<span class="mwin">Yes</span><br />

            <div class="sepline"></div>
            <div class="bullet"><b>Server 2</b></div>
            <span class="smtextw">Name</span>:&nbsp;Server Name<br />
            <span class="smtextw">Active</span>:&nbsp;No<br />
            <?
            echo $after_widget;
    	}
    	
    	function match_history($args) {
    	    extract($args);
    	    $title = 'Match History';
    	    
            echo $before_widget . $before_title . $title . $after_title;
            ?>
            <div class="wbullet"><b>[Inq] vs. {HoH}</b></div>
            <span class="smtextw">Date</span>:&nbsp;08/18/2007<br />
            <span class="smtextw">Total Score</span>:&nbsp;<span class="mwin">170</span> - 80
            <div class="hidden"><br /></div>
            <div class="sepline"></div>
            <div class="lbullet"><b>[Inq] vs. -z|0-</b></div>
            <span class="smtextw">Date</span>:&nbsp;08/15/2007<br />

            <span class="smtextw">Total Score</span>:&nbsp;52 - <span class="mwin">78</span>
            <div class="hidden"><br /></div>
            <div class="sepline"></div>
            <div class="wbullet"><b>[Inq] vs. {"@"}</b></div>
            <span class="smtextw">Date</span>:&nbsp;08/10/2007<br />
            <span class="smtextw">Total Score</span>:&nbsp;<span class="mwin">200</span> - 120

            <div class="morelink"><a href="">more&nbsp;&gt;</a></div>
            <?
            echo $after_widget;
    	}
    	
    	function roster($args) {
    	    extract($args);
    	    $title = 'Team Roster';
    	    
            echo $before_widget . $before_title . $title . $after_title;
    	    ?>
            <div class="r1bullet"><a class="smlink" href="#r1">[Inq]Someguy1</a></div>

            <div class="r1bullet"><a class="smlink" href="#r2">[Inq]Someguy2</a></div>
            <div class="r2bullet"><a class="smlink" href="#r3">[Inq]Someguy3</a></div>
            <div class="sepline"></div>
            <div class="bullet"><a class="smlink" href="#r4">[Inq]Someguy4</a></div>
            <div class="bullet"><a class="smlink" href="#r5">[Inq]Someguy5</a></div>
            <div class="bullet"><a class="smlink" href="#r6">[Inq]Someguy6</a></div>
            <div class="bullet"><a class="smlink" href="#r7">[Inq]Someguy7</a></div>
    	    <?
    	    echo $after_widget;
    	}
    	
    	register_sidebar_widget(array('Future Matches', 'widgets'), 'future_matches');
    	register_sidebar_widget(array('Our Servers', 'widgets'), 'server_status');
    	register_sidebar_widget(array('Match History', 'widgets'), 'match_history');
    	register_sidebar_widget(array('Team Roster', 'widgets'), 'roster');
    }
}

add_filter('the_content', array('tribe', 'replace_page'));
add_action('profile_update', array('tribe', 'profile_update'));
add_action('activity_box_end', array( 'tribe', 'dashboard' ) );
add_action('widgets_init', array('tribe', 'widget_init'));
add_action('publish_post', array('tribe', 'publish_post'));
add_action('show_user_profile', array('tribe', 'user_options_form'));
//add_action('edit_user_profile', array('tribe', 'user_options_form'));
add_action('personal_options_update', array('tribe', 'save_user_options'));
register_activation_hook(__FILE__, array('tribe', 'activate'));
register_deactivation_hook(__FILE__, array('tribe', 'deactivate'));

?>