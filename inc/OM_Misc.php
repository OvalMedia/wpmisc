<?php

if (!class_exists('OM_Misc')) {
    add_action('init', ['OM_Misc', 'init'], 0);

    class OM_Misc
    {
        /**
         * @var
         */
        static private $_instance;

        /**
         *
         */
        public function __construct()
        {
            $this->_addActions();
            $this->_addFilters();
            $this->_removeActions();
        }

        /**
         * @return void
         */
        protected function _addActions()
        {
            add_action('init', [$this, 'disable_emojis']);
            add_action('init', [$this, 'disable_comments_admin_bar']);
            add_action('admin_init', [$this, 'disable_comments_post_types_support']);
            add_action('admin_init', [$this, 'disable_comments_dashboard'], 100);
            add_action('admin_menu', [$this, 'disable_comments_admin_menu'], 100);
            add_action('wp_head', [$this, 'remove_generators'], 1);
            add_action('wp_print_styles', [$this, 'remove_germanized_inline_styles'], 100);
        }

        /**
         * @return void
         */
        protected function _addFilters()
        {
            add_filter('xmlrpc_methods', [$this, 'disable_rpc_methods']);
            add_filter('tiny_mce_plugins', [$this, 'disable_emojis_tinymce']);
            add_filter('wp_resource_hints', [$this, 'disable_emojis_remove_dns_prefetch'], 10, 2);
            add_filter('comments_open', [$this, 'disable_comments'], 10, 2);
            add_filter('pings_open', [$this, 'disable_comments'], 10, 2);
            add_filter('comments_array', [$this, 'hide_comments'], 10, 2);

            // Funktioniert bei portpatient nicht!?!
            //add_filter('xmlrpc_enabled', '__return_false', 100);
        }

        /**
         * @return void
         */
        protected function _removeActions()
        {
            remove_action('wp_head', 'wp_generator');

            /**
             * Soll den Willkommens-Banner im Dashboard deaktivieren.
             * Funktioniert nur irgendwie nicht?
             */
            remove_action('welcome_panel', 'wp_welcome_panel');

            /** @todo: Funktioniert nicht. Siehe https://wpforthewin.com/remove-hook-from-class-based-wp-plugin/ */
            //global $ip2location_country_blocker;
            //remove_action('wp_footer', [$ip2location_country_blocker, 'footer'], 100);
        }

        /**
         * @return \OM_Order
         */
        public static function init(): OM_Misc
        {
            if (null === self::$_instance) {
                self::$_instance = new self;
            }

            return self::$_instance;
        }

        /**
         * Remove comments links from admin bar
         *
         * @return void
         */
        public function disable_comments_admin_bar()
        {
            if (is_admin_bar_showing()) {
                remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
            }
        }

        /**
         * Remove comments metabox from dashboard
         *
         * @return void
         */
        public function disable_comments_dashboard()
        {
            remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
        }

        /**
         * @return void
         */
        public function disable_comments_admin_menu()
        {
            remove_menu_page('edit-comments.php');
        }

        /**
         * Disable support for comments and trackbacks in post types
         *
         * @return void
         */
        public function disable_comments_post_types_support()
        {
            $post_types = get_post_types();
            foreach ($post_types as $post_type) {
                if (post_type_supports($post_type, 'comments')) {
                    remove_post_type_support($post_type, 'comments');
                    remove_post_type_support($post_type, 'trackbacks');
                }
            }
        }

        /**
         * @return false
         */
        public function disable_comments(): bool
        {
            return false;
        }

        /**
         * @param $comments
         * @return array
         */
        public function hide_comments($comments): array
        {
            return array();
        }

        /**
         * @return void
         */
        public function remove_germanized_inline_styles()
        {
            wp_dequeue_style('woocommerce-gzd-layout');
        }

        /**
         * @return void
         */
        public function remove_generators()
        {
            if (class_exists('Vc_Manager')) {
                remove_action('wp_head', array(visual_composer(), 'addMetaData'));
            }
        }

        /**
         * @return void
         */
        public function disable_emojis()
        {
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_action('admin_print_styles', 'print_emoji_styles');
            remove_filter('the_content_feed', 'wp_staticize_emoji');
            remove_filter('comment_text_rss', 'wp_staticize_emoji');
            remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        }

        /**
         * Filter function used to remove the tinymce emoji plugin.
         *
         * @param array $plugins
         * @return array Difference betwen the two arrays
         */
        public function disable_emojis_tinymce($plugins): array
        {
            if (is_array($plugins)) {
                return array_diff($plugins, array('wpemoji'));
            } else {
                return array();
            }
        }

        /**
         * Remove emoji CDN hostname from DNS prefetching hints.
         *
         * @param array $urls URLs to print for resource hints.
         * @param string $relation_type The relation type the URLs are printed for.
         * @return array Difference betwen the two arrays.
         */
        public function disable_emojis_remove_dns_prefetch(array $urls, string $relation_type): array
        {
            if ($relation_type == 'dns-prefetch') {
                /** This filter is documented in wp-includes/formatting.php */
                $emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/');
                $urls = array_diff($urls, array($emoji_svg_url));
            }

            return $urls;
        }

        /**
         * @return array
         */
        public function disable_rpc_methods(): array
        {
            return array();
        }
    }
}