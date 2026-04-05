<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit;

class GithubPluginUpdater
{

    private $file;
    private $plugin_slug;
    private $version;
    private $github_repo;
    private $github_api_url;
    private $access_token;
    private $cache_key;
    private $cache_ttl = 6 * HOUR_IN_SECONDS; // 6 hours

    public function __construct($file, $github_repo, $version, $access_token = '')
    {
        $this->file = $file;
        $this->plugin_slug = plugin_basename($file);
        $this->version = $version;
        $this->github_repo = $github_repo;
        $this->github_api_url = "https://api.github.com/repos/{$github_repo}/releases/latest";
        $this->access_token = $access_token;
        $this->cache_key = 'gh_updater_' . md5($github_repo);

        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
        add_filter('upgrader_post_install', [$this, 'after_install'], 10, 3);

        error_log("#1 GitHub Plugin Updater initialized for {$github_repo} with version {$version}");
    }

    /**
     * Check for plugin updates
     */
    public function check_update($transient)
    {


        if (empty($transient->checked)) return $transient;

        $remote = $this->get_remote_release();

        error_log("#2 Remote release: " . wp_json_encode($remote));


        if (!$remote || empty($remote->tag_name)) return $transient;


        $remote_version = ltrim($remote->tag_name, 'v');

        if (version_compare($this->version, $remote_version, '<')) {
            $plugin = [
                'slug'        => dirname($this->plugin_slug),
                'new_version' => $remote_version,
                'url'         => $remote->html_url,
                'package'     => $remote->zipball_url,
            ];

            $transient->response[$this->plugin_slug] = (object) $plugin;
        }

        return $transient;
    }

    /**
     * Plugin info popup
     */
    public function plugin_info($res, $action, $args)
    {
        if ($action !== 'plugin_information') return $res;
        if ($args->slug !== dirname($this->plugin_slug)) return $res;

        $remote = $this->get_remote_release();

        if (!$remote) return $res;

        $res = new stdClass();
        $res->name = dirname($this->plugin_slug);
        $res->slug = dirname($this->plugin_slug);
        $res->version = ltrim($remote->tag_name, 'v');
        $res->author = '<a href="https://github.com/' . explode('/', $this->github_repo)[0] . '">Author</a>';
        $res->homepage = $remote->html_url;
        $res->download_link = $remote->zipball_url;

        $res->sections = [
            'description' => !empty($remote->body) ? $remote->body : 'No description provided.',
        ];

        return $res;
    }

    /**
     * After install fix folder name
     */
    public function after_install($response, $hook_extra, $result)
    {
        global $wp_filesystem;

        $install_dir = plugin_dir_path($this->file);

        // Move plugin folder to correct location
        $wp_filesystem->move($result['destination'], $install_dir);
        $result['destination'] = $install_dir;

        activate_plugin($this->plugin_slug);

        return $result;
    }

    /**
     * Get GitHub release with caching + rate limit protection
     */
    private function get_remote_release()
    {

        // Check cache first
        $cached = get_transient($this->cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $headers = [
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress',
        ];

        if (!empty($this->access_token)) {
            $headers['Authorization'] = 'token ' . $this->access_token;
        }

        $response = wp_remote_get($this->github_api_url, [
            'headers' => $headers,
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);

        // Handle GitHub rate limit
        if ($code === 403) {
            // Cache failure to prevent repeated hits
            set_transient($this->cache_key, false, HOUR_IN_SECONDS);
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response));

        if (!empty($body)) {
            set_transient($this->cache_key, $body, $this->cache_ttl);
        }

        return $body;
    }
}
