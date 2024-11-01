<?php
function about_render() : void {
    ?>
    <div class="wporg">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <style>
            .tabs-container {
                margin-bottom: 20px;
            }

            .tabs {
                display: flex;
                justify-content: space-around;
                margin-bottom: 10px;
                position: relative;
                border-bottom: 2px solid #ccc;
            }

            .tablinks {
                padding: 10px;
                cursor: pointer;
                position: relative;
                text-align: center;
            }

            .tablinks.active::after {
                content: '';
                display: block;
                height: 2px;
                background-color: #4A86E8;
                position: absolute;
                bottom: -2px;
                left: 0;
                right: 0;
            }

            .tabcontent {
                display: none;
                width: 100%;
                overflow-y: auto;
            }

            .tabcontent.active {
                display: block;
            }

            .info-container {
                margin-top: 20px;
            }

            .info-section {
                position: relative;
                margin-bottom: 20px;
                padding: 10px;
            }

            .info-section h2 {
                margin-bottom: 10px;
            }

            .info-section table {
                width: 100%;
                border-collapse: collapse;
            }

            .info-section table th,
            .info-section table td {
                padding: 8px;
                border: 1px solid #ccc;
                text-align: left;
            }
            .scrollable-text {
                max-height: 300px;
                max-width: 80%;
                overflow-y: auto;
                border: 1px solid #ccc;
                padding: 10px;
                margin-top: 10px;
                background-color: #f0f0f1;
            }
            .system-info-output{
                display: inline-block;
                white-space: pre-wrap; 
                font-family: monospace; 
                max-width: 100%; 
                overflow: auto; 
                padding: 10px; 
            }
            #submit-form{
                background-color: #2271b1;
                color: #f6f7f7;
                padding: 4px 10px;
                text-align: center;
                text-decoration: none;
                font-size: 13px;
                font-weight: 400;
                border-radius: 3px;
                position: relative;
                overflow: hidden;
                border: 1px solid #f6f7f7;
            }
            #vma-station{
                color: white;
                border-radius: 6px;
                padding: 5px 40px;
                background: linear-gradient(90deg, #256da1, #3553a0, #5146b1, #b831ba);
            }
            .copy-notification {
                position: relative;
                display: flex;
                justify-content: center;
                margin: 10px 0px;
                max-width: 80%;
                flex-direction: column;
                flex-wrap: wrap;
                align-items: flex-end;
                align-content: flex-end;
            }
            .notification {
                position: relative;
                color: #0d6efd;
                visibility: hidden;
                z-index: 1000;
                font-size: 13px;
                font-weight: 400;
            }
            #copy-info-btn {
                color: #2271b1;
                background-color: #f6f7f7;
                padding: 4px 10px;
                border-radius: 3px;
                font-size: 13px;
                font-weight: 400;
                border: 1px solid #2271b1;
            }
            #copy-info-btn:focus , #submit-form:focus {
                border-color: #3582c4;
                box-shadow: 0 0 0 1px #3582c4;
                outline: 2px solid transparent;
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var tablinks = document.getElementsByClassName('tablinks');
                var tabcontents = document.getElementsByClassName('tabcontent');

                for (var i = 0; i < tablinks.length; i++) {
                    tablinks[i].addEventListener('click', function() {
                        var activeTabs = document.getElementsByClassName('active');
                        for (var j = 0; j < activeTabs.length; j++) {
                            activeTabs[j].classList.remove('active');
                        }

                        var tabName = this.getAttribute('data-tab');
                        this.classList.add('active');
                        document.getElementById(tabName).classList.add('active');

                        // Hide the other tab content
                        var otherTabName = (tabName === 'about') ? 'system' : 'about';
                        document.getElementById(otherTabName).classList.remove('active');
                    });
                }

                // Set the first tab as active by default
                tablinks[0].click();

                // Event listener for the submit button
                var vmaButton = document.getElementById('vma-station');
                var submitButton = document.getElementById('submit-form');
                if (submitButton) {
                    submitButton.addEventListener('click', function() {
                        window.open('https://docs.google.com/forms/d/e/1FAIpQLSeBEZwzuPGbCOJ6PgLdplJtQAtYSJAKXq7x53QcE0pZMIW0GQ/viewform','_blank');
                    });
                }
                if (vmaButton) {
                    vmaButton.addEventListener('click', function() {
                        window.open('https://vma-plus-station.virtual-space-market.com/user/','_blank');
                    });
                }
            
                const copyButton = document.getElementById('copy-info-btn');
                const systemInfo = document.querySelector('.system-info');
                const notification = document.getElementById('notification');

                if (copyButton) {
                    copyButton.addEventListener('click', function() {
                        const selection = window.getSelection();
                        const range = document.createRange();
                        range.selectNodeContents(systemInfo);
                        selection.removeAllRanges();
                        selection.addRange(range);

                        try {
                            document.execCommand('copy');
                            showNotification( myTranslation.copySuccess, 'success');
                        } catch (err) {
                            console.error('Failed to copy:', err);
                            showNotification( myTranslation.copyFail, 'error');
                        }

                        selection.removeAllRanges();
                    });
                }

                function showNotification(message, type) {
                    notification.textContent = message;
                    notification.classList.add(type);
                    notification.style.visibility = 'visible';

                    setTimeout(function() {
                        notification.style.visibility = 'hidden';
                        notification.classList.remove(type);
                    }, 2000); 
                }
            });
        </script>

        <div class="tabs-container">
            <!-- Tabs -->
            <div class="tabs">
                <div class="tablinks" data-tab="about"><?php echo esc_html('Vma plus'); ?></div>
                <?php $support = __('Support', 'vma-plus-station');
                ?> 
                <div class="tablinks" data-tab="system"><?php echo esc_html($support); ?></div>
            </div>

            <!-- About Us Tab Content -->
            <div id="about" class="tabcontent">
                <div class="info-container">
                    <div class="info-section">
                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'assets/images/vma-plus-logo.png'); ?>" alt="logo" style="max-width:650px;">
                    <?php
                    $translate_about_metaverse = __('Metaverse content planning and management', 'vma-plus-station');
                    ?>    
                    <p style ="font-size:16px;"><?php echo esc_html($translate_about_metaverse); ?></p>
                        <?php
                        // Define your URLs
                        $site_url = esc_url('https://www.vma-plus.com');
                        $social_media_url = esc_url('https://linktr.ee/vma_plus');

                        // Define the translated content with placeholders
                        $translated_content = __(
                            'In the "Metaverse," a virtual world of infinite possibilities,<br>people can achieve their dreams equally, regardless of where they were born, where they grew up, their family environment, or the color of their skin. They can even create a paradigm shift in things that would be impossible to achieve in the real world.<br>Vma+ builds a community world where people from all over the world gather in the Metaverse, and by matching everything to the market (ma) of the virtual (V) space (+), we provide new options for solving social issues.<br><br>Vma+ operates the metaverse platform "Vma plus Station," which can be easily enjoyed in a browser.<br>Try out the lightweight and beautiful metaverse space.<br><br>Web site: <a href="%s" target="_blank" rel="noopener noreferrer">%s</a><br>Social media: <a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                            'vma-plus-station'
                        );

                        // Format the content with the actual URLs
                        $formatted_content = sprintf(
                            $translated_content,
                            $site_url,
                            esc_html__('https://www.vma-plus.com', 'vma-plus-station'),
                            $social_media_url,
                            esc_html__('https://linktr.ee/vma_plus', 'vma-plus-station')
                        );

                        // Output the content
                        echo '<p style="max-width: 640px;font-size: 14px;">' . $formatted_content . '</p>';
                        ?>
                        <?php
                        $translated_about_button = __('Open Vma plus Station', 'vma-plus-station');
                        ?>
                        <button id="vma-station"><?php echo esc_html($translated_about_button); ?></button>
                    </div>
                </div> <!-- end info-container -->
            </div> <!-- end about tabcontent -->

            <!-- System Information Tab Content -->
            <div id="system" class="tabcontent">
                <div class="info-container">
                    <div class="info-section">
                        <?php
                        $system_information = __('System Information', 'vma-plus-station')
                        ?>
                        <h2><?php echo esc_html($system_information); ?></h2>
                        <div class="system-info scrollable-text">
                            <?php echo esc_html(debug_info());?>
                        </div>
                        <div class ="copy-notification">
                        <?php
                        $translated_about_copy = __('Copy System Information', 'vma-plus-station');
                        ?>
                        <button id="copy-info-btn"><?php echo esc_html($translated_about_copy); ?></button>
                        <div id="notification" class="notification" style="visibility: hidden;">Failed to copy system information.</div>
                        </div>
                        <p>
                        <?php
                        $translated_about_copy_detail = __('Displays system information to be used for debugging and support.<br>When contacting technical support, please attach your system information to the inquiry form and submit it.', 'vma-plus-station');

                        echo wp_kses_post($translated_about_copy_detail); ?>
                        </p>
                        <?php
                        $translated_about_submit = __('Open Inquiry Form', 'vma-plus-station');
                        ?>
                        <button id="submit-form"><?php echo esc_html($translated_about_submit); ?></button>
                    </div>
                </div> <!-- end info-container -->
            </div> <!-- end system tabcontent -->
        </div> <!-- end tabs-container -->
    </div> <!-- end wporg -->
    <?php
}

function debug_info() {
    // Gather all necessary debug information here dynamically
    $multisite = is_multisite() ? 'Yes' : 'No';
    $site_url = esc_url(get_site_url());
    $home_url = esc_url(get_home_url());
    $wp_version = esc_html(get_bloginfo('version'));
    $permalink_structure = esc_html(get_option('permalink_structure'));
    $active_theme = esc_html(wp_get_theme()->get('Name'));
    $registered_post_types = get_post_types([], 'names');
    $php_version = esc_html(phpversion());
    global $wpdb;
    $mysql_version = esc_html($wpdb->db_version());
    $web_server_info = esc_html($_SERVER['SERVER_SOFTWARE']);
    $show_on_front = esc_html(get_option('show_on_front'));
    $page_on_front = esc_html(get_option('page_on_front'));
    $page_for_posts = esc_html(get_option('page_for_posts'));
    $memory_limit = esc_html(WP_MEMORY_LIMIT);
    $max_upload_size = wp_max_upload_size();
    $total_plugins = count(get_plugins());
    $mu_plugins = wp_get_mu_plugins();
    $plugin_version = PLUGIN_VERSION;
    $plugin_plan = PLUGIN_PLAN;
    $all_plugins = get_plugins();
    $active_plugins_paths = get_option('active_plugins');
    $active_plugins = array();

    foreach ($active_plugins_paths as $plugin_path) {
        if (isset($all_plugins[$plugin_path])) {
            $active_plugins[] = $all_plugins[$plugin_path]['Name'];
        }
    }
    ?>
    <pre class="system-info-output">
        MULTISITE:                  <?php echo esc_html($multisite); ?><br>

        SITE_URL:                   <?php echo esc_url($site_url); ?><br>
        HOME_URL:                   <?php echo esc_url($home_url); ?><br>

        WordPress VERSION:          <?php echo esc_html($wp_version); ?><br>
        Vma plus Station VERSION:   <?php echo esc_html($plugin_version); ?><br>
        PLAN:                       <?php echo esc_html($plugin_plan); ?><br>
        PERMALINK STRUCTURE:        <?php echo esc_html($permalink_structure); ?><br>
        ACTIVE THEME:               <?php echo esc_html($active_theme); ?><br>

        REGISTERED POST TYPES:      <?php echo implode(', ', array_map('esc_html', $registered_post_types)); ?><br>

        PHP VERSION:                <?php echo esc_html($php_version); ?><br>
        MySQL VERSION:              <?php echo esc_html($mysql_version); ?><br>
        WEB SERVER INFO:            <?php echo esc_html($web_server_info); ?><br>

        SHOW ON FRONT:              <?php echo esc_html($show_on_front); ?><br>
        PAGE ON FRONT:              <?php echo esc_html($page_on_front); ?><br>
        PAGE FOR POSTS:             <?php echo esc_html($page_for_posts); ?><br>

        WordPress MEMORY LIMIT:     <?php echo esc_html($memory_limit); ?><br>

        MAXIMUM UPLOAD SIZE:        <?php echo esc_html(size_format($max_upload_size)); ?> <br>

        TOTAL PLUGINS:              <?php echo esc_html($total_plugins); ?><br>
        MU PLUGINS:                 <?php echo count($mu_plugins); ?><br>

        ACTIVE PLUGINS:             <?php echo implode(', ', array_map('esc_html', $active_plugins)); ?>
    </pre>
    <?php
}
?>