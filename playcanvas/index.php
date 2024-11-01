<!doctype html>
<html>
<head>
    <meta name='viewport' content='width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no, viewport-fit=cover' />
    <meta charset='utf-8'>
    <?php 
    echo '<link rel="stylesheet" href="' . esc_url( plugins_url( '/styles.css', __FILE__ ) ) . '" type="text/css" media="screen" />';
    echo '<link rel="manifest" href="' .esc_url( plugins_url( '/manifest.json', __FILE__ ) ) . '" />';
    ?>
    <style></style>
    <title>VMA plus station Word Press</title>
    <script src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'playcanvas-stable.min.js' ); ?>"></script>
    <script>
        var PLUGIN_URL = "<?php echo esc_url( plugin_dir_url( __FILE__ )  ); ?>";
        var SCENE_PATH = "2052152.json";
    </script>
    <script src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '__settings__.js' ); ?>"></script>
    <link href="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'bootstrap.css'  ); ?>" rel="stylesheet">
</head>
<body>
<?php
        if(!function_exists('ensureHttps')){
            /**
             * Ensures the URL starts with 'https://'.
             *
             * @param string $url The URL to be checked.
             * @return string The URL with 'https://' prepended if needed.
             */
            function ensureHttps($url) {
                // Check if URL is empty or already starts with 'http://' or 'https://'
                if (empty($url) || preg_match('/^https?:\/\//', $url)) {
                    return $url;
                }
                // Prepend 'https://' to the URL
                return 'https://' . $url;
            }
        }
        $options = get_option('wporg_options');
        if ( !function_exists(  'vmaplus_option_is_iterable' ) )
        {

            function vmaplus_option_is_iterable( $obj )
            {
                return is_array( $obj ) || ( is_object( $obj ) && ( $obj instanceof \Traversable ) );
            }

        }

        if(vmaplus_option_is_iterable($options)) {
            $index = 1;
            while ($index <= 18) {
                if (!empty($options['poster_image'.$index])) {
                        ?>
                        <div id='<?php echo esc_attr( 'Poster' . $index . '_image' ); ?>'>
                            <?php echo esc_html($options['poster_image' . $index]); ?>
                        </div>
                        <?php
                } else {
                    ?>
                        <div id='<?php echo esc_attr( 'Poster' . $index . '_image' ); ?>'>
                            <?php echo ""; ?>
                        </div>
                        <?php
                }

                if (!empty($options['poster_url'.$index])) {
                    // Get the URL from the options array
                    $url = isset($options['poster_url' . $index]) ? esc_html($options['poster_url' . $index]) : '';

                    $fullUrl = ensureHttps($url);
                        ?>
                        <div id='<?php echo esc_attr( 'Poster' . $index . '_url' ); ?>'>
                            <?php echo esc_html($fullUrl); ?>
                        </div>
                        <?php
                } else {
                    ?>
                        <div id='<?php echo esc_attr( 'Poster' . $index . '_url' ); ?>'>
                            <?php echo ""; ?>
                        </div>
                        <?php
                }

                $index++;
            }
        }
    ?>
    <script src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '__modules__.js' ); ?>"></script>
    <script src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '__start__.js' ); ?>"></script>
    <script src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '__loading__.js' ); ?>"></script>
    <script src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'bootstrap.bundle.js' ); ?>"></script>
</body>
</html>
