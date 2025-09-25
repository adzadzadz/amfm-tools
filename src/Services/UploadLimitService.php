<?php

namespace App\Services;

use AdzWP\Core\Service;

/**
 * Upload Limit Service - handles file upload size restrictions for images
 */
class UploadLimitService extends Service
{
    private const IMAGE_UPLOAD_LIMIT = 204800; // 200KB in bytes

    /**
     * Constructor - register hooks immediately
     */
    public function __construct()
    {
        parent::__construct();

        // Hook into WordPress upload process
        add_filter('wp_handle_upload_prefilter', [$this, 'checkImageUploadSize']);

        // Modify upload size display text for images
        add_filter('gettext', [$this, 'modifyUploadSizeText'], 10, 3);

        // Add image naming guidelines to upload modal
        add_action('post-upload-ui', [$this, 'addImageNamingGuidelines']);
    }

    /**
     * Modify the upload size display text in media uploader to show image limit
     *
     * @param string $translation Translated text
     * @param string $text Original text
     * @param string $domain Text domain
     * @return string Modified text
     */
    public function modifyUploadSizeText($translation, $text, $domain)
    {
        // Only modify if upload limit is enabled
        if (!self::isEnabled()) {
            return $translation;
        }

        // Target the specific upload size text in admin area
        if ($text === 'Maximum upload file size: %s.' && $domain === 'default') {
            // Only modify in admin context to avoid affecting frontend
            if (is_admin() || wp_doing_ajax()) {
                return 'Maximum upload file size: %s (Images limited to ' . self::getImageUploadLimitFormatted() . ').';
            }
        }

        return $translation;
    }

    /**
     * Add image naming guidelines to the upload modal
     */
    public function addImageNamingGuidelines()
    {
        if (!self::isEnabled()) {
            return;
        }

        echo '<div class="amfm-image-naming-guidelines" style="margin: 15px 0; padding: 12px; background: #f0f6fc; border: 1px solid #c3dcf4; border-radius: 4px; font-size: 13px; line-height: 1.4;">';
        echo '<strong style="color: #0073aa; display: block; margin-bottom: 6px;">Image Naming Guidelines:</strong>';
        echo '<div style="color: #555;">Name images descriptively (e.g., <code>woman-smiling-outdoors.jpg</code>). ';
        echo 'Avoid generic names like <code>image1.jpg</code> or default AI names. ';
        echo '<a href="https://docs.google.com/document/d/1vCNSfHT5R0PGdhMBKWLB63FsiSG94i55ipBm7Ega8eQ/edit?tab=t.0#heading=h.dkqs7odqmhvr" target="_blank" style="color: #0073aa; text-decoration: none;">View complete guide →</a></div>';
        echo '</div>';
    }

    /**
     * Check image upload size and restrict to 200KB for images only
     *
     * @param array $file Upload file array
     * @return array Modified file array with error if needed
     */
    public function checkImageUploadSize($file)
    {
        // Only check if image upload limit is enabled
        if (!self::isEnabled()) {
            return $file;
        }

        // Check if file is an image
        if (!$this->isImage($file['type'])) {
            return $file; // Not an image, allow normal upload
        }

        // Check file size
        if ($file['size'] > self::IMAGE_UPLOAD_LIMIT) {
            $file['error'] = sprintf(
                __('Image file size exceeds the maximum allowed size of %s. Please compress your image or choose a smaller file.', 'amfm-tools'),
                size_format(self::IMAGE_UPLOAD_LIMIT)
            );
        }

        return $file;
    }

    /**
     * Check if file type is an image
     *
     * @param string $mime_type MIME type of the file
     * @return bool True if image, false otherwise
     */
    private function isImage($mime_type)
    {
        $image_mime_types = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'image/bmp',
            'image/tiff'
        ];

        return in_array($mime_type, $image_mime_types);
    }

    /**
     * Get the current upload limit in bytes
     *
     * @return int Upload limit in bytes
     */
    public static function getImageUploadLimit()
    {
        return self::IMAGE_UPLOAD_LIMIT;
    }

    /**
     * Get the current upload limit formatted for display
     *
     * @return string Formatted upload limit
     */
    public static function getImageUploadLimitFormatted()
    {
        return size_format(self::IMAGE_UPLOAD_LIMIT);
    }

    /**
     * Check if image upload limit is enabled
     *
     * @return bool True if enabled, false otherwise
     */
    public static function isEnabled()
    {
        // Use the standard option name that matches what SettingsService uses
        $option = get_option('amfm_components_upload_limit', 1);

        // Handle all possible stored values
        if ($option === '' || $option === false || $option === '0' || $option === 0) {
            return false;
        }

        return true;
    }

}