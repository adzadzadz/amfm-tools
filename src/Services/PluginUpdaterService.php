<?php

namespace App\Services;

use AdzWP\Core\Service;

/**
 * Plugin Updater Service - handles WordPress-style plugin updates via GitHub releases
 *
 * Integrates with WordPress's native update system to provide seamless updates
 * from GitHub releases without requiring git commands on the server
 */
class PluginUpdaterService extends Service
{
    /**
     * GitHub repository information
     */
    private const GITHUB_USER = 'adzadzadz';
    private const GITHUB_REPO = 'amfm-tools';

    /**
     * Plugin information
     */
    private string $plugin_file;
    private string $plugin_slug;
    private string $current_version;
    private string $plugin_path;

    /**
     * Initialize the updater service
     */
    public function __construct()
    {
        parent::__construct();

        $this->plugin_file = plugin_basename(AMFM_TOOLS_PATH . 'amfm-tools.php');
        $this->plugin_slug = dirname($this->plugin_file);
        $this->current_version = AMFM_TOOLS_VERSION;
        $this->plugin_path = AMFM_TOOLS_PATH;

        $this->init();
    }

    /**
     * Initialize hooks
     */
    public function init(): void
    {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'checkForUpdate']);
        add_filter('plugins_api', [$this, 'pluginInfo'], 20, 3);
        add_filter('upgrader_pre_download', [$this, 'downloadPackage'], 10, 3);
        add_filter('upgrader_source_selection', [$this, 'fixSourceDirectory'], 10, 4);
    }

    /**
     * Check for plugin updates
     */
    public function checkForUpdate($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        // Check if our plugin is in the checked list
        if (!isset($transient->checked[$this->plugin_file])) {
            return $transient;
        }

        // Get remote version info
        $remote_version = $this->getRemoteVersion();

        if ($remote_version && version_compare($this->current_version, $remote_version['version'], '<')) {
            $transient->response[$this->plugin_file] = (object) [
                'slug' => $this->plugin_slug,
                'plugin' => $this->plugin_file,
                'new_version' => $remote_version['version'],
                'url' => $remote_version['homepage'],
                'package' => $remote_version['download_url'],
                'icons' => [],
                'banners' => [],
                'banners_rtl' => [],
                'tested' => get_bloginfo('version'),
                'requires_php' => '7.4',
                'compatibility' => []
            ];
        }

        return $transient;
    }

    /**
     * Provide plugin information for the update/install process
     */
    public function pluginInfo($result, $action, $args)
    {
        if ($action !== 'plugin_information' || $args->slug !== $this->plugin_slug) {
            return $result;
        }

        $remote_version = $this->getRemoteVersion();

        if (!$remote_version) {
            return $result;
        }

        return (object) [
            'name' => 'AMFM Tools',
            'slug' => $this->plugin_slug,
            'version' => $remote_version['version'],
            'author' => '<a href="https://adzbyte.com/">Adrian T. Saycon</a>',
            'author_profile' => 'https://adzbyte.com/adz',
            'homepage' => $remote_version['homepage'],
            'requires' => '5.0',
            'tested' => get_bloginfo('version'),
            'requires_php' => '7.4',
            'download_link' => $remote_version['download_url'],
            'trunk' => $remote_version['download_url'],
            'last_updated' => $remote_version['last_updated'],
            'sections' => [
                'description' => 'A comprehensive plugin for AMFM custom functionalities including shortcodes, Elementor widgets, and content management tools.',
                'changelog' => $this->getChangelog($remote_version)
            ],
            'banners' => [],
            'icons' => []
        ];
    }

    /**
     * Handle the download process
     */
    public function downloadPackage($result, $package, $upgrader)
    {
        if (strpos($package, 'github.com') === false || strpos($package, self::GITHUB_REPO) === false) {
            return $result;
        }

        // Let WordPress handle the download normally
        return $result;
    }

    /**
     * Fix the source directory name after extraction
     * GitHub archives extract as 'repo-branch' but WordPress expects 'plugin-name'
     */
    public function fixSourceDirectory($source, $remote_source, $upgrader, $args = [])
    {
        global $wp_filesystem;

        // Only process our plugin updates
        if (!isset($args['plugin']) || $args['plugin'] !== $this->plugin_file) {
            return $source;
        }

        // Build the corrected source path
        $corrected_source = trailingslashit($remote_source) . 'amfm-tools/';

        // Check if the source needs to be renamed
        if ($source !== $corrected_source) {
            // Check if the incorrect folder exists (like amfm-tools-development)
            $source_files = $wp_filesystem->dirlist($remote_source);

            if ($source_files) {
                // Find the extracted folder (should be something like amfm-tools-development)
                foreach ($source_files as $file => $file_info) {
                    if ($file_info['type'] === 'd' && strpos($file, 'amfm-tools') === 0) {
                        $old_source = trailingslashit($remote_source) . $file;

                        // Rename the folder to the correct name
                        if ($wp_filesystem->move($old_source, $corrected_source)) {
                            return $corrected_source;
                        }
                    }
                }
            }
        }

        return $source;
    }

    /**
     * Get the current update channel setting
     */
    private function getUpdateChannel(): string
    {
        return get_option('amfm_update_channel', 'stable');
    }

    /**
     * Get the branch name based on update channel
     */
    private function getBranchName(): string
    {
        return $this->getUpdateChannel() === 'development' ? 'development' : 'stable';
    }

    /**
     * Get remote version information from GitHub API
     */
    private function getRemoteVersion(): ?array
    {
        $channel = $this->getUpdateChannel();
        $cache_key = 'amfm_tools_remote_version_' . $channel;
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // For stable channel, try releases first, then fallback to branch
        // For development channel, always use branch
        if ($channel === 'stable') {
            $version_info = $this->getLatestRelease();
            if ($version_info) {
                set_transient($cache_key, $version_info, HOUR_IN_SECONDS);
                return $version_info;
            }
        }

        // Fallback to branch-based check
        $version_info = $this->getBranchVersion();
        if ($version_info) {
            set_transient($cache_key, $version_info, HOUR_IN_SECONDS);
        }

        return $version_info;
    }

    /**
     * Get latest release from GitHub
     */
    private function getLatestRelease(): ?array
    {
        $api_url = "https://api.github.com/repos/" . self::GITHUB_USER . "/" . self::GITHUB_REPO . "/releases/latest";

        $response = wp_remote_get($api_url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'AMFM-Tools-Updater'
            ]
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || !isset($data['tag_name'])) {
            return null;
        }

        return [
            'version' => ltrim($data['tag_name'], 'v'),
            'download_url' => $data['zipball_url'] ?? $data['tarball_url'] ?? null,
            'homepage' => $data['html_url'],
            'last_updated' => $data['published_at'],
            'changelog' => $data['body'] ?? '',
            'channel' => 'stable'
        ];
    }

    /**
     * Check branch for version
     */
    private function getBranchVersion(): ?array
    {
        $branch = $this->getBranchName();
        $api_url = "https://api.github.com/repos/" . self::GITHUB_USER . "/" . self::GITHUB_REPO . "/contents/amfm-tools.php?ref=" . $branch;

        $response = wp_remote_get($api_url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'AMFM-Tools-Updater'
            ]
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || !isset($data['content'])) {
            return null;
        }

        $content = base64_decode($data['content']);

        // Extract version from plugin header
        if (preg_match('/\* Version:\s*(.+)/', $content, $matches)) {
            $version = trim($matches[1]);
            $channel = $this->getUpdateChannel();

            return [
                'version' => $version,
                'download_url' => "https://github.com/" . self::GITHUB_USER . "/" . self::GITHUB_REPO . "/archive/refs/heads/" . $branch . ".zip",
                'homepage' => "https://github.com/" . self::GITHUB_USER . "/" . self::GITHUB_REPO,
                'last_updated' => date('Y-m-d H:i:s'),
                'changelog' => "Latest updates from the {$branch} branch.",
                'channel' => $channel
            ];
        }

        return null;
    }

    /**
     * Format changelog for display
     */
    private function getChangelog(array $version_info): string
    {
        if (empty($version_info['changelog'])) {
            return 'No changelog available.';
        }

        // Convert markdown-style changelog to HTML
        $changelog = $version_info['changelog'];
        $changelog = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $changelog);
        $changelog = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $changelog);
        $changelog = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $changelog);
        $changelog = preg_replace('/^\* (.+)$/m', '<li>$1</li>', $changelog);
        $changelog = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $changelog);

        return $changelog;
    }

    /**
     * Update the channel setting
     */
    public function setUpdateChannel(string $channel): bool
    {
        if (!in_array($channel, ['stable', 'development'])) {
            return false;
        }

        // Clear cache when channel changes
        $old_channel = $this->getUpdateChannel();
        if ($old_channel !== $channel) {
            delete_transient('amfm_tools_remote_version_stable');
            delete_transient('amfm_tools_remote_version_development');
        }

        return update_option('amfm_update_channel', $channel);
    }

    /**
     * Manually check for updates (for testing)
     */
    public function checkNow(): array
    {
        $channel = $this->getUpdateChannel();
        delete_transient('amfm_tools_remote_version_' . $channel);
        $remote_version = $this->getRemoteVersion();

        return [
            'current_version' => $this->current_version,
            'remote_version' => $remote_version['version'] ?? 'Unknown',
            'update_available' => $remote_version && version_compare($this->current_version, $remote_version['version'], '<'),
            'channel' => $channel,
            'remote_info' => $remote_version
        ];
    }
}