<?php
// Initialize session if not already started
if (!session_id()) {
    session_start();
}

/**
 * Render the settings page.
 */
function render_edit_page($capability, $page_id) : void {
    // Check user capabilities
    if (!current_user_can($capability)) {
        return;
    }

    // Show error/update messages
    settings_errors('wporg_messages');

    // Nonce for view_more action
    $view_more_nonce = wp_create_nonce('view_more_action');
    // Store nonce in session for verification
    $_SESSION['view_more_nonce'] = $view_more_nonce;

    // Path to the assets folder
    $assets_url = plugin_dir_url(__FILE__) . 'assets/images/';
    $assets_path = plugin_dir_path(__FILE__) . 'assets/images/';

    // Get all image files from the assets/images folder
    $images = glob($assets_path . 'world*.{jpg,jpeg,png,gif}', GLOB_BRACE);
    sort($images);

    $lock_icon_url = $assets_url . 'lock-icon.svg';
    ?>
    <div class="wporg-option">
    <style>
        .wporg-option .worlds {
            list-style-type: none;
            padding: 0;
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
        }

        .wporg-option .world-images {
            position: relative;
            margin: 10px;
            width: 150px;
            height: 150px;
            flex: 0 0 auto;
        }

        .wporg-option .world-images .image-container {
        position: relative; 
        }

        .wporg-option .world-images img {
            width: 100%;
            height: 100%;
            display: block;
        }

        .wporg-option ul li.selected .image-container {
            border: 3px solid #36b122;
        }

        .wporg-option ul li.more {
            font-size: 24px;
            background-color: #e0e0e0;
            width: 150px;
            height: 150px;
            text-align: center;
            padding: 50px;
            text-decoration: none; 
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer; 
        }

        .wporg-option ul li.more:hover {
            background-color: #ccc; 
        }

        .wporg-option ul li.first-image {
            border: 3px solid #36b122;
        }

        .wporg-option ul li.locked {
            opacity: 90%;
        }

        .wporg-option .world-images .lock-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 150px; /* Adjust icon size */
            height: 150px; /* Adjust icon size */
            background-image: url('<?php echo esc_url($lock_icon_url); ?>');
            background-size: cover;
            z-index: 1;
            opacity: 50%;
        }
        .wporg-option .world-images .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .wporg-option .world-images .pro-text {
            position: absolute;
            top: 5px;
            right: 5px; 
            background-color: rgba(54, 177, 34, 0.5); 
            color: white; 
            font-size: 16px; 
            padding: 0px 11px; 
            z-index: 2; 
            border-radius: 20%;
			vertical-align: middle;
        }

        /* #image-map-container {
        position: relative;
        } */

        #map-close-button, #maximize{
            position: absolute;
            top: 0;
            right: 0;
            border: none;
            cursor: pointer;
            background-color: #2271b1;
            color: #f0f0f0;
        }

        #toggle-image-map {
            padding: 4px 45px 4px 25px;
            cursor: pointer;
            margin: 0;
        }

    </style>
    <script>
    jQuery(document).ready(function($) {
        jQuery('#view-more').on('click', function(event) {
            event.preventDefault(); // Prevent default action (if any)
            $('<form>', {
                'action': '<?php echo esc_url(admin_url('admin.php')); ?>',
                'method': 'POST'
            }).append($('<input>', {
                'type': 'hidden',
                'name': 'view_more_nonce',
                'value': '<?php echo esc_html(wp_create_nonce('view_more_action')); ?>'
            })).append($('<input>', {
                'type': 'hidden',
                'name': 'action',
                'value': 'view_more'
            })).append($('<input>', {
                'type': 'hidden',
                'name': 'page',
                'value': 'metaverse-plugin-setting'
            })).appendTo('body').submit();
        });
    });
    </script>
    <h1><?php echo esc_html(__('Edit Site','vma-plus-station')) ;?></h1>
    <h2 class="description">
        <?php
            $translated_metaverse_select_world = __('SELECT WORLD','vma-plus-station'); 
            echo esc_html($translated_metaverse_select_world); 
        ?>
    </h2>
    <ul class="worlds">
        <?php foreach (array_slice($images, 0, 3) as $index => $image_path): ?>
            <?php
            // Get the filename from the full path
            $image_filename = basename($image_path);
            ?>
            <li class="world-images <?php echo $index === 0 ? 'first-image' : 'locked'; ?>">
                <div class="image-container">
                    <img src="<?php echo esc_url($assets_url . $image_filename); ?>" alt="<?php echo esc_attr($image_filename); ?>">
                    <?php if ($index !== 0) : ?>
                        <span class="pro-text">Pro</span>
                        <div class="lock-icon"></div>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>

        <?php if (count($images) >= 3): ?>
            <li class="world-images more" id="view-more">
                ... <!-- Display as plain text -->
            </li>
        <?php endif; ?>
    </ul>
    <form action="options.php" method="post">
        <?php
        // Output nonce field
        wp_nonce_field('metaverse-settings', 'metaverse_nonce');
        // Output security fields for the registered setting "my-plugin-settings"
        settings_fields('my-plugin-settings');
        do_settings_sections('my-plugin-settings');
        
        echo '<div class ="image-map">';
        echo '<p id ="toggle-image-map">' . esc_html(__('Image Map', 'vma-plus-station')) . '</p>';
        echo '<button id = "maximize"><i class="fas fa-expand-alt"></i></button>';
        echo '<div id="image-map-container" style="display: none;">';
        $image_url = plugin_dir_url(__FILE__) . 'assets/images/gallery_top.png';
        echo '<img id="map_image" src="' . esc_url($image_url) . '" alt="Gallery Top Image">';
        echo '<button id="map-close-button"><i class="fas fa-compress-alt"></i></button>';
        echo '</div>';
        echo '</div>';

        // Output security fields for the registered setting "metaverse-setting"
        settings_fields('metaverse-setting');
        do_settings_sections('metaverse-setting');

        echo '<input type="hidden" name="metaverse_settings_submit" value="1">';
        echo '<input type="hidden" name="page_id" value="' . esc_attr($page_id) . '">';

        // Define the translated label
        $button_label = __('Save Settings', 'vma-plus-station');

        // Output the submit button with the translated label
        submit_button($button_label);
        ?>
    </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleImageMap = document.getElementById('toggle-image-map');
            const imageMapContainer = document.getElementById('image-map-container');
            const closeButton = document.getElementById('map-close-button');
            const maximize = document.getElementById('maximize');

            // Toggle image map visibility on h3 click
            toggleImageMap.addEventListener('click', (event) => {
                event.preventDefault();
                if (imageMapContainer.style.display === 'none') {
                    imageMapContainer.style.display = 'block';
                    document.querySelector('.image-map').style.marginTop = '-435px';
                    document.querySelector('.image-map').style.marginLeft = '-330px';
                    maximize.style.display = 'none';
                } else {
                    imageMapContainer.style.display = 'none';
                    document.querySelector('.image-map').style.marginTop = '-119px';
                    document.querySelector('.image-map').style.marginLeft = '-170px';
                    maximize.style.display = 'block';
                }
            });

            // Hide image map container on close button click
            closeButton.addEventListener('click', (event) => {
                event.preventDefault();
                imageMapContainer.style.display = 'none';
                document.querySelector('.image-map').style.marginTop = '-119px';
                document.querySelector('.image-map').style.marginLeft = '-170px';
                maximize.style.display = 'block';
            });

            //maximize
            maximize.addEventListener('click', (event) => {
                event.preventDefault();
                imageMapContainer.style.display = 'block';
                document.querySelector('.image-map').style.marginTop = '-435px';
                document.querySelector('.image-map').style.marginLeft = '-330px';
                maximize.style.display = 'none';
            });
            
        });

    </script>
    <?php
}

/**
 * Hook the redirection function to admin_init with a priority higher than 10.
 */
add_action('admin_init', 'handle_more_action_redirect', 5);

function handle_more_action_redirect() {
    if (isset($_POST['page'], $_POST['action']) && $_POST['page'] === 'metaverse-plugin-setting' && $_POST['action'] === 'view_more') {
        // Verify nonce
        if (isset($_POST['view_more_nonce']) && wp_verify_nonce($_POST['view_more_nonce'], 'view_more_action')) {
            wp_redirect(admin_url('admin.php?page=worlds-plugin-setting'));
            exit;
        } else {
            // Nonce verification failed, handle error or redirect as needed
            wp_die('Security check failed. Please try again.');
        }
    }
}
session_write_close();
?>
