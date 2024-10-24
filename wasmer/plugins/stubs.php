<?

function recursive_copy($src,$dst) {
	$dir = opendir($src);
	@mkdir($dst);
	while(( $file = readdir($dir)) ) {
		if (( $file != '.' ) && ( $file != '..' )) {
			if ( is_dir($src . '/' . $file) ) {
				recursive_copy($src .'/'. $file, $dst .'/'. $file);
			}
			else {
				copy($src .'/'. $file,$dst .'/'. $file);
			}
		}
	}
	closedir($dir);
}

add_action( 'template_redirect', function() {
    if (!file_exists(WP_CONTENT_DIR)) {
        mkdir(WP_CONTENT_DIR);
    }
    if (!file_exists(get_theme_root())) {
        mkdir(get_theme_root());
        recursive_copy(WPMU_PLUGIN_DIR . '/default-themes/twentytwentyfour/', get_theme_root() . '/twentytwentyfour/');
        header('Location: ' . home_url());
        die();
    }
});
