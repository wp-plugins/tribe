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
    
    09:56 Colosus|work Yeah. That's fine. There are certain parts of the site that I consider required for release... News, User profiles, match history, calendar, sidebars and a completed style sheet
*/

class tribe {
    
// Public
    
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

    function get_matches() {
        global $wpdb;
        
        $matches = $wpdb->get_results('select * from wp_tribe_match order by played_on desc');
        
        foreach ($matches as $match) {
            $match->maps = $wpdb->get_results('select * from wp_tribe_match_map where match_id = '.$match->match_id);
            $match->attendance = $wpdb->get_results('select user_id, status from wp_tribe_attendance where match_id = '.$match->match_id);
        }
        
        return $matches;
    }
    
// Private

    function admin_menu() {
        add_options_page('tribe', 'tribe', 'can_edit_tribe_options', basename(__FILE__), array('tribe', 'options_page'));
        add_management_page('tribe', 'tribe - Matches', 'can_edit_matches', basename(__FILE__), array('tribe', 'matches_page'));
    }

    function activate() {
        $roles = get_option('wp_user_roles');
        $roles['administrator']['name'] = "Team Captain";
        $roles['administrator']['capabilities']['is_team_member'] = 1;
        $roles['administrator']['capabilities']['can_edit_tribe_options'] = 1;
        $roles['administrator']['capabilities']['can_edit_matches'] = 1;
        $roles['editor']['name'] = 'Coordinator';
        $roles['editor']['capabilities']['is_team_member'] = 1;
        $roles['author']['name'] = 'Member';
        $roles['author']['capabilities']['is_team_member'] = 1;
        update_option('wp_user_roles', $roles);
    
        update_option('users_can_register', 1);
        update_option('category_base', '/blogs');

        if (category_exists('Team News') <= 1) {
            wp_create_category('Team News');
        }        
        if (category_exists('Members') <= 1) {
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
            $templates = get_page_templates();
            if (isset($templates['Roster'])) {
                $args['page_template'] = $templates['Roster'];
            }
            wp_insert_post($args);
        }
        
        if (get_page_by_title('Matches') == null) {
            $args = array(
                        'post_title' => 'Matches',
                        'post_content' => '[tribe-matches]',
                        'post_type' => 'page',
                        'post_status' => 'publish',
                        'comment_status' => 'closed',
                        'ping_status' => 'closed'
                    );
            $matches_page_id = wp_insert_post($args);
            
            $args = array(
                        'post_title' => 'History',
                        'post_content' => '[tribe-history]',
                        'post_type' => 'page',
                        'post_status' => 'publish',
                        'comment_status' => 'closed',
                        'ping_status' => 'closed',
                        'post_parent' => $matches_page_id
                    );
            $templates = get_page_templates();
            if (isset($templates['Match History'])) {
                $args['page_template'] = $templates['Match History'];
            }
            wp_insert_post($args);
        }
        

        $users = get_users_of_blog();
        foreach($users as $userdata) {
            $user = new WP_User($userdata->user_id);
            // is_team_member doesn't work here yet, does cache need to be cleared?
            // wp_cache_flush doesn't work
            if (/*$user->allcaps['is_team_member'] &&*/
                (array_search('author', $user->roles) !== false ||
                array_search('editor', $user->roles) !== false || 
                array_search('administrator', $user->roles) !== false) &&
                $user->user_login != 'admin') {
                $member_cat_id = get_cat_ID('Members');
                if (category_exists($user->user_nicename)){
                    // they already have cat.  make sure its in the right place
                    // copy the name since get_category has side effects
                    $cat_name = $user->user_nicename;
                    $cat = get_category(get_cat_ID($cat_name));
                    if ($cat->category_parent != $member_cat_id) {
                        $args = array(
                                    'cat_ID' => $cat->cat_ID,
                                    'category_parent' => $member_cat_id
                                );
                        wp_update_category($args);
                    }
                } else {
                    // no cat, create it
                    $args = array(
                                'cat_name' => $user->user_nicename,
                                'category_parent' => $member_cat_id
                            );
                    wp_insert_category($args);
                }
                $page = get_page_by_title($user->user_nicename);
                if ($page) {
                    // they already have their page, make sure its in the right place
                    $roster_page = get_page_by_title('Roster');
                    if ($page->post_parent != $roster_page->ID) {
                        $args = array(
                                    'ID' => $page->ID,
                                    'post_parent' => $roster_page->ID
                                );
                        wp_update_post($args);
                    }
                } else {
                    // no page, create it
                    tribe::_create_user_profile_page($user);
                }
            }
        }
    }

    function deactivate() {
        $roles = get_option('wp_user_roles');
        $roles['administrator']['name'] = "Administrator";
        unset($roles['administrator']['capabilities']['is_team_member']);
        unset($roles['administrator']['capabilities']['can_edit_tribe_options']);
        unset($roles['administrator']['capabilities']['can_edit_matches']);
        $roles['editor']['name'] = 'Editor';
        unset($roles['editor']['capabilities']['is_team_member']);
        $roles['author']['name'] = 'Author';
        unset($roles['author']['capabilities']['is_team_member']);
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
   
    function publish_post($post_id) {
        global $wpdb;
        
        $cat_id = get_cat_ID('Team News');
        $is_news = $wpdb->get_var("select count(*) from $wpdb->post2cat where post_id = $post_id and category_id = $cat_id");
        // TODO: make turning off comments and pings an option
        if ($is_news) {
            $wpdb->query("update $wpdb->posts set comment_status = 'closed', ping_status = 'closed' where ID = $post_id");
        }
    }
    
    function template_redirect() {
        //global $post;
        //print_r($post);
        //include(TEMPLATEPATH . '/history.php');
        //exit;
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
            <input type="text" name="tribe_joindate" value="<?= attribute_escape($profileuser->tribe_joindate) ?>" /></label></p>
            <p><label>Age:<br />
            <input type="text" name="tribe_age" value="<?= attribute_escape($profileuser->tribe_age) ?>" /></label></p>
            <p><label>Location:<br />
            <input type="text" name="tribe_location" value="<?= attribute_escape($profileuser->tribe_location) ?>" /></label></p>
            <p><label>Quote:<br />
            <textarea name="tribe_quote"/><?= attribute_escape($profileuser->tribe_quote) ?></textarea></label></p>
        </fieldset>
        <fieldset>
            <legend>System Specs</legend>
            <?
            if (!is_array($profileuser->tribe_specs)) {
                $profileuser->tribe_specs = null;
            }
            if (!$profileuser->tribe_specs) {
                $profileuser->tribe_specs = array(
                                                'Motherboard' => '',
                                                'Processor' => '',
                                                'Graphics Card(s)' => '',
                                                'Sound Card' => '',
                                                'Memory' => '',
                                                'Hard Drive(s)' => '',
                                                'Monitor(s)' => '',
                                                'Keyboard' => '',
                                                'Mouse' => '',
                                                'OS' => ''
                                            );
            }
            foreach ($profileuser->tribe_specs as $key => $value) {
                ?>
                <p><label><?=$key?>: <input type="text" name="tribe_specs[<?=$key?>]" value="<?= attribute_escape($value) ?>" /></label></p>
                <?
            }
            ?>
        </fieldset>
        <fieldset>
            <legend>tribe Q &amp; A</legend>
            <?
            $questions = get_option('tribe_questions');
            if ($questions) {
                foreach ($questions as $index => $question) {
                    ?>                    
                    <p><label><?=attribute_escape($question)?><br />
                    <textarea name="tribe_answers[]"/><?= attribute_escape($profileuser->tribe_answers[$index]) ?></textarea></label></p>
                    <?
                }
            }
            ?>
        </fieldset>
        <?
    }
    
    function save_user_options() {
        global $userdata;

        update_usermeta($userdata->ID, 'tribe_status', stripslashes($_POST['tribe_status']));        
        update_usermeta($userdata->ID, 'tribe_joindate', stripslashes($_POST['tribe_joindate']));
        update_usermeta($userdata->ID, 'tribe_age', (int) $_POST['tribe_age']);
        update_usermeta($userdata->ID, 'tribe_location', stripslashes($_POST['tribe_location']));
        update_usermeta($userdata->ID, 'tribe_quote', stripslashes($_POST['tribe_quote']));
        update_usermeta($userdata->ID, 'tribe_specs', stripslashes_deep($_POST['tribe_specs']));
        update_usermeta($userdata->ID, 'tribe_answers', stripslashes_deep($_POST['tribe_answers']));
    }

    function options_page() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            update_option('tribe_name', stripslashes($_POST['tribe_name']));
            $questions = $_POST['tribe_questions'];
            foreach ($questions as $index => $question) {
                if (!$question) {
                    unset($questions[$index]);
                }
            }
            $questions = stripslashes_deep(array_values($questions));
            update_option('tribe_questions', $questions);
        }
        ?>
    	<div class="wrap">
    		<h2>tribe Options</h2>
    		<form method="post" action="<?=$_SERVER["REQUEST_URI"]?>">
    		<p class="submit">
            <input type="submit" name="Submit" value="Update Options &raquo;" />
            </p>
            <h3>General</h3>
            Team Name: (generally {Tag} {Name})<br/>
            <input type="text" name="tribe_name" value="<?= attribute_escape(get_option('tribe_name'))?>">
            <h3>Questions</h3>
    	    <p>Questions here will be be shown to your team members on their profile pages.</p>
    	    <?
    	    $questions = get_option('tribe_questions');
    	    if ($questions) {
    	        foreach ($questions as $index => $question) {
    	            ?>
    	            <br/>
    	            Question <?=($index+1)?>: <input size="100" type="text" name="tribe_questions[<?=$index?>]" value="<?=attribute_escape($question)?>">
    	            <?
    		    }
            }
    	    ?>
    	    <br/><br/>
    	    New Question:<br/><input size="100" type="text" name="tribe_questions[]">
    		<p class="submit">
            <input type="submit" name="Submit" value="Update Options &raquo;" />
            </p>
    		</form>
    	</div>
        <?
    }

    function matches_page() {
         if ($_SERVER["REQUEST_METHOD"] == "POST") {
                
            }
        ?>
    	<div class="wrap">
    		<h2>Match Management</h2>
    		<form method="post" action="<?=$_SERVER["REQUEST_URI"]?>">

    		</form>
    	</div>
        <? 
    }

    function profile_update($user_id) {
        $user = new WP_User($user_id);

        // TODO: bulk update of role doesn't trigger this action

        if ($user->allcaps['is_team_member'] && $user->user_login != 'admin') {
            if (!category_exists($user->user_nicename)) {
                // they don't have a category yet, lets create one underneath 'Members'
                $member_cat_ID = get_cat_ID('Members');
                wp_insert_category(array('cat_name' => $user->user_nicename,
                                         'category_parent' => $member_cat_ID));
            }
            // see if we need to create a roster profile page
            if (!get_page_by_title($user->user_nicename)) {
                _create_user_profile_page($user);
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
    
    function widget_init() {
        if ( !function_exists('register_sidebar_widget') )
    		return;
    	
    	function next_match($args) {
    	    extract($args);
    	    $title = 'Next Match';
    	    
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
    	
    	function profile_manager($args) {
    	    extract($args);
    	    $title = 'Profile Manager';
    	    
            echo $before_widget . $before_title . $title . $after_title;
    	    ?>    	    
            &nbsp;<b class="smtext">[Inq]Someguy</b>
            <div class="sepline2"></div>
            <div class="ebullet"><a class="smlink" href="">Edit Profile Info</a></div>
            <div class="bullet"><a class="smlink" href="">Log Out</a></div>
            <div class="sepline"></div>
            &nbsp;<b>Blog Options</b>
            <div class="sepline2"></div>
            <div class="abullet"><a class="smlink" href="">Add Blog Post</a></div>
            <br />
            &nbsp;<b>Keybind Options</b>
            <div class="sepline2"></div>

            <div class="ebullet"><a class="smlink" href="">Edit Keybinds</a></div>
            <div class="abullet"><a class="smlink" href="">Add Keybinds</a></div>
            <br />
            &nbsp;<b>Screenshot Options</b>
            <div class="sepline2"></div>
            <div class="ebullet"><a class="smlink" href="">Edit Screenshots</a></div>
            <div class="abullet"><a class="smlink" href="">Add Screenshots</a></div>
    	    <?
    	    echo $after_widget;
    	}
    	
    	register_sidebar_widget(array('Next Match', 'widgets'), 'next_match');
    	register_sidebar_widget(array('Our Servers', 'widgets'), 'server_status');
    	register_sidebar_widget(array('Match History', 'widgets'), 'match_history');
    	register_sidebar_widget(array('Team Roster', 'widgets'), 'roster');
    	register_sidebar_widget(array('Profile Manager', 'widgets'), 'profile_manager');
    }

    function _create_user_profile_page($user, $create_new = true) {
        if ($user) {
            $roster_page = get_page_by_title('Roster');
            
            $args = array(
                'post_title' => $user->user_nicename,
                'post_content' => '[tribe-profile '.$user->ID.']',
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_parent' => $roster_page->ID,
                'comment_status' => 'closed',
                'ping_status' => 'closed'
                );
            
            $templates = get_page_templates();
            if (isset($templates['Profile'])) {
                $args['page_template'] = $templates['Profile'];
            }
            
            if ($create_new) {
                return wp_insert_post($args);
            } else {
                return wp_update_post($args);
            }
        }
    }
}

add_action('profile_update', array('tribe', 'profile_update'));
add_action('activity_box_end', array( 'tribe', 'dashboard' ) );
add_action('widgets_init', array('tribe', 'widget_init'));
add_action('publish_post', array('tribe', 'publish_post'));
add_action('show_user_profile', array('tribe', 'user_options_form'));
//add_action('edit_user_profile', array('tribe', 'user_options_form'));
add_action('personal_options_update', array('tribe', 'save_user_options'));
add_action('admin_menu', array('tribe', 'admin_menu'));
add_action('template_redirect', array('tribe', 'template_redirect'));

register_activation_hook(__FILE__, array('tribe', 'activate'));
register_deactivation_hook(__FILE__, array('tribe', 'deactivate'));

?>