<?php
//
//	A simple PHP CAPTCHA script
//
//	Copyright 2011 by Cory LaViska for A Beautiful Site, LLC.
//
//	http://abeautifulsite.net/blog/2011/01/a-simple-php-captcha-script/
//

require_once __DIR__ . '/../../../phive.php';

function captcha($config = array(), string $requested_captcha = '', bool $enable_reset = false, ?string $captcha_session_key = null) {
	// Check for GD library
	if( !function_exists('gd_info') ) {
		throw new Exception('Required GD library is missing');
	}

	// Default values
	$captcha_config = phive()->getSetting('captcha-config', [
		'code' => '',
		'min_length' => 5,
		'max_length' => 5,
        'png_backgrounds' => [__DIR__ . '/default.png'],
        'fonts' => [__DIR__ . '/times_new_yorker.ttf'],
		'characters' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',
		'min_font_size' => 24,
		'max_font_size' => 30,
		'color' => '#000',
		'angle_min' => 0,
		'angle_max' => 0,
		'shadow' => true,
		'shadow_color' => '#CCC',
		'shadow_offset_x' => -2,
		'shadow_offset_y' => 2
	]);

	// Overwrite defaults with custom config values
	if( is_array($config) ) {
		foreach( $config as $key => $value ) $captcha_config[$key] = $value;
	}

	// Restrict certain values
	if( $captcha_config['min_length'] < 1 ) 	$captcha_config['min_length'] = 1;
	if( $captcha_config['angle_min'] < 0 ) 		$captcha_config['angle_min'] = 0;
	if( $captcha_config['angle_max'] > 15 ) 	$captcha_config['angle_max'] = 15;
	if( $captcha_config['angle_max'] < 			$captcha_config['angle_min'] ) $captcha_config['angle_max'] = $captcha_config['angle_min'];
	if( $captcha_config['min_font_size'] < 10 ) $captcha_config['min_font_size'] = 10;
	if( $captcha_config['max_font_size'] < 	$captcha_config['min_font_size'] )
		$captcha_config['max_font_size'] = $captcha_config['min_font_size'];

	// Use milliseconds instead of seconds
	srand(microtime() * 100);

	// Generate CAPTCHA code if not set by user
	if( empty($captcha_config['code']) ) {
		$captcha_config['code'] = '';
		$length = rand($captcha_config['min_length'], $captcha_config['max_length']);
		while( strlen($captcha_config['code']) < $length ) {
			$captcha_config['code'] .= substr($captcha_config['characters'], rand() % (strlen($captcha_config['characters'])), 1);
		}
	}

	// Generate image src
    $micro_time = microtime();
	$image_src = substr(__FILE__, strlen($_SERVER['DOCUMENT_ROOT'])) . "?_CAPTCHA&t=" . urlencode($micro_time);
	$image_src = '/' . ltrim(preg_replace('/\\\\/', '/', $image_src), '/');

	if ($requested_captcha) {
        $_SESSION["_CAPTCHA_$requested_captcha"]['config'] = $captcha_config;
        $_SESSION["_CAPTCHA_$requested_captcha"]['micro_time'] = $micro_time;
        if ($enable_reset) {
            $_SESSION[getLimitAttemptKey("_CAPTCHA_$requested_captcha")]['config'] = $captcha_config;
            $_SESSION[getLimitAttemptKey("_CAPTCHA_$requested_captcha")]['micro_time'] = $micro_time;
        }
    }

    $_SESSION['_CAPTCHA']['config'] = $captcha_config;

    if ($enable_reset) {
        $_SESSION[getLimitAttemptKey('_CAPTCHA')]['config'] = $captcha_config;
    }

    if(! is_null($captcha_session_key)) {
        phMset($captcha_session_key, $captcha_config['code'], 60);
    }

	return array(
		'code' => $captcha_config['code'],
		'image_src' => $image_src
	);

}


if( !function_exists('hex2rgb') ) {
	function hex2rgb($hex_str, $return_string = false, $separator = ',') {
		$hex_str = preg_replace("/[^0-9A-Fa-f]/", '', $hex_str); // Gets a proper hex string
		$rgb_array = array();
		if( strlen($hex_str) == 6 ) {
			$color_val = hexdec($hex_str);
			$rgb_array['r'] = 0xFF & ($color_val >> 0x10);
			$rgb_array['g'] = 0xFF & ($color_val >> 0x8);
			$rgb_array['b'] = 0xFF & $color_val;
		} elseif( strlen($hex_str) == 3 ) {
			$rgb_array['r'] = hexdec(str_repeat(substr($hex_str, 0, 1), 2));
			$rgb_array['g'] = hexdec(str_repeat(substr($hex_str, 1, 1), 2));
			$rgb_array['b'] = hexdec(str_repeat(substr($hex_str, 2, 1), 2));
		} else {
			return false;
		}
		return $return_string ? implode($separator, $rgb_array) : $rgb_array;
	}
}


// Draw the image
if( isset($_GET['_CAPTCHA']) ) {
    $enable_reset = isset($_GET['reset']) && $_GET['reset'] === 'true';

    if ($enable_reset) {
        $requested = $_GET['requested'] ?? '';
        $captcha_session_key = $_GET['captcha_session_key'] ?? null;
        captcha(array(), $requested, true, $captcha_session_key);
    }

	phive()->sessionStart();

	// This will happen on timeouts
	if (!isset($_SESSION['_CAPTCHA'])){
		die("timeout");
	}

    // Determine which data to use and which $_SESSION variable to unset if applicable.
    $t = $_GET['t'];
    $requested_captcha = $_SESSION['_CAPTCHA_username']['micro_time'] == $t ? 'username' : '';
    $requested_captcha = $_SESSION['_CAPTCHA_password']['micro_time'] == $t ? 'password' : $requested_captcha;

    if ($requested_captcha) {
        $captcha_config = $_SESSION["_CAPTCHA_$requested_captcha"]['config'];
    }
    else {
        $captcha_config = $_SESSION['_CAPTCHA']['config'];
    }

	// Use milliseconds instead of seconds
	srand(microtime() * 100);

	// Pick random background, get info, and start captcha
	$background = $captcha_config['png_backgrounds'][rand(0, count($captcha_config['png_backgrounds']) -1)];
	list($bg_width, $bg_height, $bg_type, $bg_attr) = getimagesize($background);

	// Create captcha object
	$captcha = imagecreatefrompng($background);
    imagealphablending($captcha, true);
    imagesavealpha($captcha , true);

	$color = hex2rgb($captcha_config['color']);
	$color = imagecolorallocate($captcha, $color['r'], $color['g'], $color['b']);

	// Determine text angle
	$angle_direction = (rand(0, 1) == 1 ? -1 : 1);
	$angle = rand($captcha_config['angle_min'], $captcha_config['angle_max']) * $angle_direction;
	
	// Select font randomly
	$font = $captcha_config['fonts'][rand(0, count($captcha_config['fonts']) - 1)];

	// Verify font file exists
	if( !file_exists($font) ) throw new Exception('Font file not found: ' . $font);

	//Set the font size.
	$font_size = rand($captcha_config['min_font_size'], $captcha_config['max_font_size']);
	$text_box_size = imagettfbbox($font_size, $angle, $font, $captcha_config['code']);

	// Determine text position
	$box_width = abs($text_box_size[6] - $text_box_size[2]);
	$x_offset = $bg_width * 0.035;
	$text_pos_x_min = $x_offset;
	$text_pos_x_max = abs($bg_width - $box_width) < 30 ? $x_offset : $bg_width - $box_width - $x_offset * 2;
	$text_pos_x = rand($text_pos_x_min, $text_pos_x_max);
	$text_pos_y_min = ($angle_direction < 0) ? $bg_height * 0.3 : $bg_height * 0.8;
	$text_pos_y_max = ($angle_direction < 0) ? $bg_height * 0.45 : $bg_height * 0.95;
	$text_pos_y = rand($text_pos_y_min, $text_pos_y_max);

	// Draw shadow
	if( $captcha_config['shadow'] ){
		$shadow_color = hex2rgb($captcha_config['shadow_color']);
	 	$shadow_color = imagecolorallocate($captcha, $shadow_color['r'], $shadow_color['g'], $shadow_color['b']);
		imagettftext($captcha, $font_size, $angle, $text_pos_x + $captcha_config['shadow_offset_x'], $text_pos_y + $captcha_config['shadow_offset_y'], $shadow_color, $font, $captcha_config['code']);
	}

	// Draw text
	imagettftext($captcha, $font_size, $angle, $text_pos_x, $text_pos_y, $color, $font, $captcha_config['code']);

	// Output image
	header("Content-type: image/png");

	if ($requested_captcha) {
	    unset($_SESSION["_CAPTCHA_$requested_captcha"]); // Remove unnecessary variables from $_SESSION.
        if (!$_SESSION['_CAPTCHA_username'] && !$_SESSION['_CAPTCHA_password']) {
            unset($_SESSION['_CAPTCHA']);
        }
    }
	else {
        unset($_SESSION['_CAPTCHA']);
    }
    imagepng($captcha);
}
