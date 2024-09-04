<?php
/*
Plugin Name: Privilege Pilot
Description: Manage custom user roles and capabilities through an admin interface.
Version: 1.6
Author: Angelo Marasa
*/

require 'puc/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/amarasa/privilege-pilot',
    __FILE__,
    'privilege-pilot-plugin'
);


// Optional: If you're using a private repository, specify the access token like this:
// $myUpdateChecker->setAuthentication('your-token-here');


// Hook to initialize custom role
function pp_create_custom_role()
{
    if (!get_role('client')) {
        add_role('client', 'Client', [
            'read' => true, // Allows reading posts
        ]);
    }
}
register_activation_hook(__FILE__, 'pp_create_custom_role');

// Hook to remove custom role upon plugin deactivation
function pp_remove_custom_role()
{
    remove_role('client');
}
register_deactivation_hook(__FILE__, 'pp_remove_custom_role');

// Add settings page to the admin menu
function pp_add_settings_page()
{
    add_menu_page(
        'Privilege Pilot',      // Page title
        'Privilege Pilot',      // Menu title
        'manage_options',       // Capability required to see the menu
        'pp-role-manager',      // Menu slug
        'pp_render_settings_page' // Callback function to display the page
    );
}
add_action('admin_menu', 'pp_add_settings_page');

// Render the settings page
function pp_render_settings_page()
{
?>
    <div class="wrap">
        <h1>Privilege Pilot</h1>
        <button id="pp-toggle-all" class="button button-primary">Toggle All</button>
        <form method="post" action="options.php">
            <?php
            settings_fields('pp_settings_group'); // Output nonce, action, and option_page fields for the settings page
            ?>
            <div class="pp-capability-grid">
                <?php
                // Manually output each settings field
                $capabilities = pp_get_capabilities_list();
                foreach ($capabilities as $capability => $description) {
                    pp_capability_toggle(['capability' => $capability, 'description' => $description]);
                }
                ?>
            </div>
            <?php
            submit_button();
            ?>
        </form>
    </div>
    <style>
        .pp-capability-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            /* 4 items per row */
            gap: 20px;
            margin-top: 20px;
        }

        .pp-capability-item {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pp-toggle-switch {
            display: flex;
            align-items: center;
        }

        .pp-toggle-switch input[type="checkbox"] {
            width: 0;
            height: 0;
            visibility: hidden;
        }

        .pp-toggle-switch label {
            cursor: pointer;
            text-indent: -9999px;
            width: 50px;
            height: 25px;
            background: grey;
            display: block;
            border-radius: 100px;
            position: relative;
        }

        .pp-toggle-switch label:after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 21px;
            height: 21px;
            background: #fff;
            border-radius: 90px;
            transition: 0.3s;
        }

        .pp-toggle-switch input:checked+label {
            background: #4caf50;
        }

        .pp-toggle-switch input:checked+label:after {
            left: calc(100% - 2px);
            transform: translateX(-100%);
        }

        .pp-toggle-switch label:active:after {
            width: 28px;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleAllButton = document.getElementById('pp-toggle-all');
            const toggles = document.querySelectorAll('.pp-toggle-switch input[type="checkbox"]');

            toggleAllButton.addEventListener('click', function() {
                const allChecked = Array.from(toggles).every(checkbox => checkbox.checked);
                toggles.forEach(checkbox => checkbox.checked = !allChecked);
            });
        });
    </script>
<?php
}

// Helper function to get the capabilities list
function pp_get_capabilities_list()
{
    return [
        'read'                   => 'Allows reading posts and pages.',
        'edit_posts'             => 'Allows editing of posts.',
        'edit_pages'             => 'Allows editing of pages.',
        'edit_others_posts'      => 'Allows editing other users\' posts.',
        'edit_others_pages'      => 'Allows editing other users\' pages.',
        'edit_published_posts'   => 'Allows editing of published posts.',
        'edit_published_pages'   => 'Allows editing of published pages.',
        'publish_posts'          => 'Allows publishing of posts.',
        'publish_pages'          => 'Allows publishing of pages.',
        'delete_posts'           => 'Allows deletion of posts.',
        'delete_pages'           => 'Allows deletion of pages.',
        'delete_others_posts'    => 'Allows deletion of other users\' posts.',
        'delete_others_pages'    => 'Allows deletion of other users\' pages.',
        'delete_published_posts' => 'Allows deletion of published posts.',
        'delete_published_pages' => 'Allows deletion of published pages.',
        'upload_files'           => 'Allows uploading of files to the media library.',
        'moderate_comments'      => 'Allows moderating comments.',
        'edit_private_posts'     => 'Allows editing of private posts.',
        'edit_private_pages'     => 'Allows editing of private pages.',
        'read_private_posts'     => 'Allows reading of private posts.',
        'read_private_pages'     => 'Allows reading of private pages.',
        'delete_private_posts'   => 'Allows deletion of private posts.',
        'delete_private_pages'   => 'Allows deletion of private pages.',
        'manage_categories'      => 'Allows managing of categories.',
        'manage_links'           => 'Allows managing of links.',
        'unfiltered_html'        => 'Allows unfiltered HTML.',
        'edit_theme_options'     => 'Allows editing of theme options.',
        'edit_nav_menus'         => 'Allows editing of navigation menus.',
        'edit_files'             => 'Allows editing of files.',
        'edit_attachments'       => 'Allows editing of media attachments.',
    ];
}

// Callback function to render the toggle switch
function pp_capability_toggle($args)
{
    $capabilities = get_option('pp_capabilities', []);
    $capability = $args['capability'];
    $description = $args['description'];
    $checked = in_array($capability, $capabilities) ? 'checked' : '';
    echo '<div class="pp-capability-item">';
    echo '<span>' . esc_html($description) . '</span>';
    echo '<div class="pp-toggle-switch">';
    echo '<input type="checkbox" id="pp_' . esc_attr($capability) . '" name="pp_capabilities[]" value="' . esc_attr($capability) . '" ' . esc_attr($checked) . '>';
    echo '<label for="pp_' . esc_attr($capability) . '"></label>';
    echo '</div>';
    echo '</div>';
}

// Update role capabilities based on settings
function pp_update_role_capabilities()
{
    $capabilities = get_option('pp_capabilities', []);
    $role = get_role('client');

    if ($role) {
        // First, remove all capabilities
        foreach ($role->capabilities as $cap => $enabled) {
            $role->remove_cap($cap);
        }

        // Then, add selected capabilities
        if (is_array($capabilities)) {
            foreach ($capabilities as $cap) {
                $role->add_cap($cap);
            }
        }
    }
}
add_action('admin_init', 'pp_update_role_capabilities');
