<?php
    function worlds_render() : void {
        // Path to the assets folder
            $assets_url = plugin_dir_url(__FILE__) . 'assets/images/';
            $assets_path = plugin_dir_path(__FILE__) . 'assets/images/';

            // Get all image files from the assets/images folder
            $images = glob($assets_path . 'world*.{jpg,jpeg,png,gif}', GLOB_BRACE);
            $popular_worlds = glob($assets_path . 'popular*.{jpg,jpeg,png,gif}', GLOB_BRACE);
            sort($images);
            sort($popular_worlds);

            $lock_icon_url = $assets_url . 'lock-icon.svg';

            echo '<div class="wporg">';
            ?>
            <style>
                .world-heading {
					display: flex;
					align-items: center;
					justify-content: flex-start;
				}

                .wporg .worlds {
                    list-style-type: none;
                    padding: 0;
                    display: flex;
                    flex-wrap: nowrap;
                    overflow-x: auto;
                }

                .wporg .world-images {
                    position: relative;
                    margin: 10px;
                    width: 150px;
                    height: 150px;
                    flex: 0 0 auto;
                }

                .wporg .world-images .image-container {
                    position: relative;
                }

                .wporg .world-images img {
                    width: 100%;
                    height: 100%;
                    display: block;
                }

                .wporg ul li.first-image {
                    border: 3px solid #36b122;
                }

                .wporg .world-images .pro-text {
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

                .wporg .world-images .lock-icon {
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
                .popular-world-container{
                    position: relative;
                }

                .coming-soon {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    font-size: 24px;
                    background-color: rgba(0, 0, 0, 0.7); 
                    color: white;
                    text-transform: uppercase;
                }

            </style>
            <h1 class="world-heading"><?php echo esc_html(get_admin_page_title()); ?>
            <!--Upload World-->
            <button type="submit" class="create-button-lock" disabled>
                <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'assets/images/lock-icon.svg'); ?>" alt="Lock Icon" width="15" height="15">
                
                <span style="vertical-align: middle;"><?php echo esc_html(__('Upload World', 'vma-plus-station')); ?></span>
                
                <span class="pro-badge">Pro</span>
                
			</button>
            </h1>
            <h3><?php echo esc_html(__('REGISTERED WORLD', 'vma-plus-station')); ?></h3>
            <ul class="worlds">
                <?php foreach ($images as $index => $image_path): ?>
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
            </ul>
            <br>
            <hr>
            <h3><?php echo esc_html(__('POPULAR WORLD', 'vma-plus-station')); ?></h3>
            <div class = "popular-world-container"> 
            <ul class="worlds popular-world">
                <?php foreach ($popular_worlds as $popular_world):?>
                    <?php
                    //Get the filename from the full path
                    $popular_world_name = basename($popular_world);
                    ?>
                    <li class = "world-images">
                        <div class = "image-container">
                            <img src="<?php echo esc_url($assets_url . $popular_world_name);?>" alt="<?php echo esc_attr($popular_world_name); ?>">
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <span class = "coming-soon">Coming Soon</span>
            </div>
            <?php
            echo '</div>';
    }
?>