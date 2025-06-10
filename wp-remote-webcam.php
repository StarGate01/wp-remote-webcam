<?php

/**
 * Plugin Name: Remote Webcam
 * Description: Securely streams a remote image using HTTP
 * Version: 1.5
 * Author: Christoph Honal
 */

if (!defined('ABSPATH')) exit;

/* 
define('WEBCAM_REMOTE_URL', 'https://secure.example.com/protected/image.jpg');
*/

if (!defined('WEBCAM_REMOTE_URL')) {
    return new WP_Error('plugin_config_error', 'Remote Webcam is not configured.', ['status' => 500]);
}

add_action('rest_api_init', function () {
    register_rest_route('wp-remote-webcam', '/img', [
        'methods'             => 'GET',
        'callback'            => 'webcam_stream_image',
        'permission_callback' => '__return_true',
    ]);
});

function webcam_stream_image() {
    $ch = curl_init(WEBCAM_REMOTE_URL);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,                     // Return response as string
        CURLOPT_HEADER         => false,                    // Don't include headers in output
        CURLOPT_FOLLOWLOCATION => true,                     // Follow 3xx redirects
        CURLOPT_SSL_VERIFYPEER => false,                    // Don't verify SSL certificates
        CURLOPT_SSL_VERIFYHOST => 0,                        // Don't verify host matches cert
    ]);

    $image = curl_exec($ch);

    if (curl_errno($ch) || curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200 || !$image) {
        curl_close($ch);
        return new WP_Error('image_fetch_failed', 'Unable to retrieve remote image.', ['status' => 502]);
    }

    curl_close($ch);

    $src_image = imagecreatefromstring($image);
    if (!$src_image) {
        return new WP_Error('image_processing_failed', 'Failed to create image from string.', ['status' => 500]);
    }

    $resized_image = imagescale($src_image, 640, 480);
    header('Content-Type: image/jpeg');
    imagejpeg($resized_image);

    imagedestroy($src_image);
    imagedestroy($resized_image);
    exit;
}
