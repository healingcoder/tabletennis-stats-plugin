<?php
/**
 * フロントエンド管理クラス
 * 公開側の表示を管理
 */

if (!defined('ABSPATH')) {
    exit;
}

class TT_Stats_Frontend_Manager {
    
    public function __construct() {
        // ショートコードを登録
        add_action('init', array($this, 'register_shortcodes'));
        
        // スタイルとスクリプトを読み込み
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // カスタムリライトルールを追加
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'template_redirect'));
    }
    
    /**
     * ショートコードを登録
     */
    public function register_shortcodes() {
        add_shortcode('tt_stats_home', array($this, 'home_shortcode'));
        add_shortcode('tt_stats_search', array($this, 'search_shortcode'));
        add_shortcode('tt_stats_player', array($this, 'player_shortcode'));
        add_shortcode('tt_stats_match', array($this, 'match_shortcode'));
    }
    
    /**
     * フロントエンド用のCSSとJSを読み込み
     */
    public function enqueue_frontend_assets() {
        // CSS
        wp_enqueue_style(
            'tt-stats-public',
            TT_STATS_PLUGIN_URL . 'public/css/style.css',
            array(),
            TT_STATS_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'tt-stats-search',
            TT_STATS_PLUGIN_URL . 'public/js/search.js',
            array('jquery'),
            TT_STATS_VERSION,
            true
        );
        
        // Ajax用のデータを渡す
        wp_localize_script('tt-stats-search', 'ttStatsPublic', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tt_stats_public_nonce')
        ));
    }
    
    /**
     * カスタムリライトルールを追加
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^tt-stats/player/([0-9]+)/?$',
            'index.php?tt_stats_type=player&tt_stats_id=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^tt-stats/match/([0-9]+)/?$',
            'index.php?tt_stats_type=match&tt_stats_id=$matches[1]',
            'top'
        );
    }
    
    /**
     * クエリ変数を追加
     */
    public function add_query_vars($vars) {
        $vars[] = 'tt_stats_type';
        $vars[] = 'tt_stats_id';
        return $vars;
    }
    
    /**
     * テンプレートリダイレクト
     */
    public function template_redirect() {
        $type = get_query_var('tt_stats_type');
        $id = get_query_var('tt_stats_id');
        
        if ($type && $id) {
            if ($type === 'player') {
                $this->load_player_template($id);
            } elseif ($type === 'match') {
                $this->load_match_template($id);
            }
            exit;
        }
    }
    
    /**
     * 選手詳細テンプレートを読み込み
     */
    private function load_player_template($player_id) {
        include TT_STATS_PLUGIN_DIR . 'public/templates/player-detail.php';
    }
    
    /**
     * 試合詳細テンプレートを読み込み
     */
    private function load_match_template($match_id) {
        include TT_STATS_PLUGIN_DIR . 'public/templates/match-detail.php';
    }
    
    /**
     * ホームページショートコード
     */
    public function home_shortcode($atts) {
        ob_start();
        include TT_STATS_PLUGIN_DIR . 'public/templates/home.php';
        return ob_get_clean();
    }
    
    /**
     * 検索ページショートコード
     */
    public function search_shortcode($atts) {
        ob_start();
        include TT_STATS_PLUGIN_DIR . 'public/templates/search.php';
        return ob_get_clean();
    }
    
    /**
     * 選手詳細ショートコード
     */
    public function player_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);
        
        ob_start();
        $player_id = intval($atts['id']);
        include TT_STATS_PLUGIN_DIR . 'public/templates/player-detail.php';
        return ob_get_clean();
    }
    
    /**
     * 試合詳細ショートコード
     */
    public function match_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);
        
        ob_start();
        $match_id = intval($atts['id']);
        include TT_STATS_PLUGIN_DIR . 'public/templates/match-detail.php';
        return ob_get_clean();
    }
}
