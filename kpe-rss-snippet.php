<?php
/*
Plugin Name: RSS Snippet
Description: Wordpress plugin will have an admin that allows the user to add snippets of content, including hyperlinks, to the RSS feed xml. This customizable content is fixed in the feed in a specific position to allow email marketing campaigns to use the RSS with specific content inserted automatically as snippets.
Version: 1.4
Author: Searchworxx KPE
*/

// Create table when plugin is activated
function kpe_create_plugin_database_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'kpe_rss_snippet';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        `id` int(0) NOT NULL AUTO_INCREMENT,
        `snippet_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL,
        `feed` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL,
        `category_tag` int(0) NULL DEFAULT NULL,
        `snippet_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL,
        `snippet_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL,
        `snippet_summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
        `position_in_feed` int(0) NULL DEFAULT NULL,
        `date_created` datetime(0) NULL DEFAULT CURRENT_TIMESTAMP(0),
        PRIMARY KEY (`id`)
    );";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'kpe_create_plugin_database_table');

// Create Admin Menu and Sub Menu Page
function kpe_rss_snippet_plugin_menu() {
    add_menu_page(
        'RSS Snippet Settings',
        'RSS Snippet',
        'manage_options',
        'kpe-rss-snippet-plugin',
        'kpe_rss_snippet_plugin_page'
    );

    add_submenu_page(
        'kpe-rss-snippet-plugin',
        'Create RSS Snippet',
        'Add New RSS Snippet',
        'manage_options',
        'kpe-create-rss-snippet',
        'kpe_create_rss_snippet_page'
    );

    // External Javascript file
    wp_register_script( 'kpe_rss_snippet_script', plugins_url('/kpe_rss_snippet.js', __FILE__), array('jquery'), '1.1');
    wp_enqueue_script( 'kpe_rss_snippet_script' );

    // Handle AJAX requests
    wp_localize_script('kpe_rss_snippet_script', 'myAjax', array('ajax_url' => admin_url('admin-ajax.php')));
}

add_action('admin_menu', 'kpe_rss_snippet_plugin_menu');

// Main plugin page
function kpe_rss_snippet_plugin_page() {
    echo '
        <div class="wrap">
            <h1 class="wp-heading-inline">RSS Snippets</h1>
            <a href="'.get_admin_url().'admin.php?page=kpe-create-rss-snippet" class="page-title-action">Create RSS Snippet</a>
            <h2>Instructions: Add "/?feed=rss-snippet" after the feed URL to get the modified feed.</h2>
            <hr class="wp-header-end">
            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                    <th>Name</th>
                    <th>Feed Type</th>
                    <th>Taxonomy</th>
                    <th>Date Created</th>
                    <th>Action</th>
                    </tr>
                </thead>
                <tbody>
    ';

    global $wpdb;

    $appTable = $wpdb->prefix . "kpe_rss_snippet";
    $query = $wpdb->prepare("SELECT * FROM $appTable");
    $applications = $wpdb->get_results($query);

    foreach ( $applications as $application ) {
        $cat_tag = '';
        if ($application->feed == 'Category') {
            $cat_tag = get_cat_name($application->category_tag);
        } elseif ($application->feed == 'Tag') {
            $tag = get_tag($application->category_tag);
            $cat_tag = $tag->name;
        }

        echo '<tr>' . 
            '<td><strong>' . $application->snippet_name . '</strong></td>'. 
            '<td><strong>' . $application->feed . '</strong></td>'.
            '<td><strong>' . $cat_tag . '</strong></td>'. 
            '<td><strong>' . $application->date_created . '</strong></td>'. 
            '<td><strong><a href="'. esc_url(admin_url("admin.php?page=kpe-create-rss-snippet&myid=".$application->id)) .'">EDIT</a>&nbsp;|&nbsp;<a href="'. esc_url(admin_url("admin-post.php?action=kpe_delete_rss&myid=".$application->id)) .'">DELETE</a></strong></td>'. 
        '</tr>';
    }

    echo '</tbody></table></div>';
}

// Delete function
function kpe_delete_rss() {
    global $wpdb;
    if (isset($_GET['myid'])) {
        $snippet_id = $_GET['myid'];

        $wpdb->delete(
            $wpdb->prefix . 'kpe_rss_snippet', 		// table name with dynamic prefix
            ['id' => $snippet_id], 						// which id need to delete
            ['%d'], 							// make sure the id format
        );
    }

    // Redirect back to the admin page after deletion
    wp_redirect(admin_url('admin.php?page=kpe-rss-snippet-plugin'));
    exit;
}
add_action('admin_post_kpe_delete_rss', 'kpe_delete_rss');

// Add New Form
function kpe_create_rss_snippet_page() {
    if (isset($_GET['myid'])) {
        kpe_edit_rss_snippet_page($_GET['myid']);
    } else {
        echo '
            <div class="wrap">
                <h1 class="wp-heading-inline">Add New RSS Snippet</h1>
                <hr class="wp-header-end">
                <form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">
                    <input type="hidden" name="action" value="addrsssnippet">
                    <input type="hidden" name="plugin_url" id="plugin_url" value="'.plugin_dir_url( __FILE__ ).'">
                    <input type="hidden" name="cat_tag" id="cat_tag" value="">
                    <input type="hidden" name="old_feed" id="old_feed" value="">
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr class="form-field form-required">
                                <th scope="row"><label for="snippet_name">RSS Snippet Name <span class="description">(required)</span></label></th>
                                <td><input name="snippet_name" type="text" id="snippet_name" value=""></td>
                            </tr>
                            <tr class="form-field form-required">
                                <th scope="row"><label for="feed">Which Feed</label></th>
                                <td>
                                    <select name="feed" id="feed">
                                        <option selected="selected" value="Global">Global</option>
                                        <option value="Category">Category</option>
                                        <option value="Tag">Tag</option>
                                    </select>
                                </td>
                            </tr>
                            <tr class="form-field form-required">
                                <th scope="row"><label for="category_tag">Select Category or Tag</label></th>
                                <td>
                                    <select name="category_tag" id="category_tag"></select>
                                </td>
                            </tr>
                            <tr class="form-field form-required">
                                <th scope="row"><label for="snippet_title">Snippet Title <span class="description">(required)</span></label></th>
                                <td><input name="snippet_title" type="text" id="snippet_title" value=""></td>
                            </tr>
                            <tr class="form-field form-required">
                                <th scope="row"><label for="snippet_url">Link Snippet To (URL) <span class="description">(required)</span></label></th>
                                <td><input name="snippet_url" type="text" id="snippet_url" value=""></td>
                            </tr>
                            <tr class="form-field form-required">
                                <th scope="row"><label for="snippet_summary">Snippet Summary <span class="description">(required)</span></label></th>
                                <td><textarea name="snippet_summary" id="snippet_summary" rows="5" cols="30"></textarea></td>
                            </tr>
                            <tr class="form-field form-required">
                                <th scope="row"><label for="position_in_feed">Position in Feed</label></th>
                                <td>
                                    <select name="position_in_feed" id="position_in_feed">
                                        <option selected="selected" value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                        <option value="4">4</option>
                                        <option value="5">5</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="submit"><input type="submit" name="addrsssnippet" id="addrsssnippetsub" class="button button-primary" value="Add New RSS Snippet"></p>
                </form>
            </div>';
    }
}

// Add New record
function kpe_add_new_form_submission() {
    global $wpdb;
    
    $snippet_name = sanitize_text_field($_POST['snippet_name']);
    $feed = $_POST['feed'];
    if ($feed == 'Global') {
        $category_tag = "NULL";
    } else {
        $category_tag = $_POST['category_tag'];
    }
    $snippet_title = sanitize_text_field($_POST['snippet_title']);
    $snippet_url = sanitize_text_field($_POST['snippet_url']);
    $snippet_summary = sanitize_textarea_field($_POST['snippet_summary']);
    $position_in_feed = $_POST['position_in_feed'];

    $table_name = $wpdb->prefix . 'kpe_rss_snippet';
    $sql = "INSERT INTO $table_name (`snippet_name`,`feed`,`category_tag`,`snippet_title`,`snippet_url`,`snippet_summary`,`position_in_feed`) 
        VALUES('".$snippet_name."','".$feed."','".$category_tag."','".$snippet_title."','".$snippet_url."','".$snippet_summary."',".$position_in_feed.");";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    wp_redirect(admin_url('admin.php?page=kpe-rss-snippet-plugin'));
    exit;
}
add_action('admin_post_addrsssnippet', 'kpe_add_new_form_submission');

// Edit Form
function kpe_edit_rss_snippet_page($snippet_id) {
    global $wpdb;

    $appTable = $wpdb->prefix . "kpe_rss_snippet";
    $query = $wpdb->prepare("SELECT * FROM $appTable where id = ".$snippet_id);
    $rss_snippet = $wpdb->get_row($query);

    echo '
            <div class="wrap">
                <h1 class="wp-heading-inline">Edit RSS Snippet</h1>
                <hr class="wp-header-end">
                <form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">
                    <input type="hidden" name="action" value="editrsssnippet">
                    <input type="hidden" name="plugin_url" id="plugin_url" value="'.plugin_dir_url( __FILE__ ).'">
                    <input type="hidden" name="snippet_id" id="snippet_id" value="'.$snippet_id.'">
                    <input type="hidden" name="cat_tag" id="cat_tag" value="'.$rss_snippet->category_tag.'">
                    <input type="hidden" name="old_feed" id="old_feed" value="'.$rss_snippet->feed.'">
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr class="form-field form-required">
                                <th scope="row"><label for="snippet_name">RSS Snippet Name <span class="description">(required)</span></label></th>
                                <td><input name="snippet_name" type="text" id="snippet_name" value="'.$rss_snippet->snippet_name.'"></td>
                            </tr>
                            <tr class="form-field form-required">
                                <th scope="row"><label for="feed">Which Feed</label></th>
                                <td>
                                    <select name="feed" id="feed">
                                        <option value="Global">Global</option>
                                        <option value="Category">Category</option>
                                        <option value="Tag">Tag</option>
                                    </select>
                                </td>
                            </tr>
                            <tr class="form-field form-required">
                                <th scope="row"><label for="category_tag">Select Category or Tag</label></th>
                                <td>
                                    <select name="category_tag" id="category_tag"></select>
                                </td>
                            </tr>
                            <tr class="form-field form-required">
                                <th scope="row"><label for="snippet_title">Snippet Title <span class="description">(required)</span></label></th>
                                <td><input name="snippet_title" type="text" id="snippet_title" value="'.$rss_snippet->snippet_title.'"></td>
                            </tr>
                            <tr class="form-field form-required">
                                <th scope="row"><label for="snippet_url">Link Snippet To (URL) <span class="description">(required)</span></label></th>
                                <td><input name="snippet_url" type="text" id="snippet_url" value="'.$rss_snippet->snippet_url.'"></td>
                            </tr>
                            <tr class="form-field form-required">
                                <th scope="row"><label for="snippet_summary">Snippet Summary <span class="description">(required)</span></label></th>
                                <td><textarea name="snippet_summary" id="snippet_summary" rows="5" cols="30">'.$rss_snippet->snippet_summary.'</textarea></td>
                            </tr>
                            <tr class="form-field form-required">
                                <th scope="row"><label for="position_in_feed">Position in Feed</label></th>
                                <td>
                                    <select name="position_in_feed" id="position_in_feed">
                                        <option '; if ($rss_snippet->position_in_feed == 1) echo 'selected="selected"';
                                        echo 'value="1">1</option>
                                        <option '; if ($rss_snippet->position_in_feed == 2) echo 'selected="selected"';
                                        echo 'value="2">2</option>
                                        <option '; if ($rss_snippet->position_in_feed == 3) echo 'selected="selected"';
                                        echo 'value="3">3</option>
                                        <option '; if ($rss_snippet->position_in_feed == 4) echo 'selected="selected"';
                                        echo 'value="4">4</option>
                                        <option '; if ($rss_snippet->position_in_feed == 5) echo 'selected="selected"';
                                        echo 'value="5">5</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="submit"><input type="submit" name="editrsssnippet" id="editrsssnippetsub" class="button button-primary" value="Save RSS Snippet"></p>
                </form>
            </div>';
}

// Save/Update record
function kpe_edit_form_submission() {
    global $wpdb;
    
    $snippet_id = sanitize_text_field($_POST['snippet_id']);
    $snippet_name = sanitize_text_field($_POST['snippet_name']);
    $feed = $_POST['feed'];
    if ($feed == 'Global') {
        $category_tag = "NULL";
    } else {
        $category_tag = $_POST['category_tag'];
    }
    $snippet_title = sanitize_text_field($_POST['snippet_title']);
    $snippet_url = sanitize_text_field($_POST['snippet_url']);
    $snippet_summary = sanitize_textarea_field($_POST['snippet_summary']);
    $position_in_feed = $_POST['position_in_feed'];

    $table_name = $wpdb->prefix . 'kpe_rss_snippet';
    $sql = "UPDATE $table_name set `snippet_name` = '".$snippet_name."',`feed` = '".$feed."',`category_tag` = '".$category_tag."',`snippet_title` = '".$snippet_title."',
        `snippet_url` = '".$snippet_url."',`snippet_summary` = '".$snippet_summary."',`position_in_feed` = ".$position_in_feed." WHERE id = " . $snippet_id;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    wp_redirect(admin_url('admin.php?page=kpe-rss-snippet-plugin'));
    exit;
}
add_action('admin_post_editrsssnippet', 'kpe_edit_form_submission');

// find the string position of the nth occurrence of a substring within another string
function kpe_findNthOccurrencePosition($haystack, $needle, $n) {
    $offset = 0;
    for ($i = 1; $i <= $n; $i++) {
        $pos = strpos($haystack, $needle, $offset);
        if ($pos === false) {
            return false; // Not found
        }
        $offset = $pos + strlen($needle);
    }
    return $pos;
}

// Create Custom RSS Feed
function kpe_create_my_customfeed() {
    load_template( plugin_dir_path( __FILE__ ) . 'kpe-feed-rss2.php'); // You'll create a your-custom-feed.php file in your theme's directory
}
add_action('do_feed_rss-snippet', 'kpe_create_my_customfeed', 10, 1); // Make sure to have 'do_feed_customfeed'

/*
    example urls:
    - https://[DOMAIN]/?feed=rss-snippet
    - https://[DOMAIN]/feed/rss-snippet/
    - https://[DOMAIN]/category/diabetes/?feed=rss-snippet
    - https://[DOMAIN]/tag/tag-1/?feed=rss-snippet
*/
function kpe_custom_feed_rewrite($wp_rewrite) {
    $feed_rules = array(
        'feed/(.+)' => 'index.php?feed=' . $wp_rewrite->preg_index(1),
        '(.+).xml' => 'index.php?feed='. $wp_rewrite->preg_index(1)
    );
    $wp_rewrite->rules = $feed_rules + $wp_rewrite->rules;
}
add_filter('generate_rewrite_rules', 'kpe_custom_feed_rewrite'); // Save permalinks after putting this code


// Change RSS links in the header to use the custom RSS
function kpe_custom_rss_url($output, $show) {
    if (in_array($show, array('rss_url', 'rss2_url', 'rss', 'rss2', '')))
    $output = site_url() . '/feed/rss-snippet';
    return $output; 
}
add_filter('bloginfo_url', 'kpe_custom_rss_url', 10, 2);
add_filter('feed_link', 'kpe_custom_rss_url', 10, 2);


// Used by Wordpress Ajax to fetch category or tag records
function kpe_fetch_cat_tag() {
    // Your server-side logic here
    // Fetch records from your custom database table
    // Return the records (e.g., echo or return JSON)

    if (isset($_REQUEST['xfeed'])) {

        if ($_REQUEST['xfeed'] == 'Category') {
            $results = get_categories(array('get'=>'all'));
        } else {
            $results = get_tags(array('get'=>'all'));
        }
    
        if ($results) {
            echo json_encode($results);
        } else {
            echo json_encode(['message' => 'No records found']);
        }
    
    }

    wp_die(); // Always include this to terminate the script
}
add_action('wp_ajax_fetch_records', 'kpe_fetch_cat_tag');
