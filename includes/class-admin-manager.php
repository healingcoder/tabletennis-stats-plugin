<?php
/**
 * 管理画面管理クラス
 * WordPress管理画面にメニューと機能を追加
 */

if (!defined('ABSPATH')) {
    exit;
}

class TT_Stats_Admin_Manager {
    
    public function __construct() {
        // 管理メニューを追加
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // スタイルとスクリプトを読み込み
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * 管理メニューを追加
     */
    public function add_admin_menu() {
        // メインメニュー
        add_menu_page(
            '卓球成績管理',
            '卓球成績',
            'manage_options',
            'tt-stats',
            array($this, 'dashboard_page'),
            'dashicons-awards',
            30
        );
        
        // ダッシュボード
        add_submenu_page(
            'tt-stats',
            'ダッシュボード',
            'ダッシュボード',
            'manage_options',
            'tt-stats',
            array($this, 'dashboard_page')
        );
        
        // 選手管理
        add_submenu_page(
            'tt-stats',
            '選手管理',
            '選手管理',
            'manage_options',
            'tt-stats-players',
            array($this, 'players_page')
        );
        
        // 試合管理
        add_submenu_page(
            'tt-stats',
            '試合管理',
            '試合管理',
            'manage_options',
            'tt-stats-matches',
            array($this, 'matches_page')
        );
        
        // 対戦結果管理
        add_submenu_page(
            'tt-stats',
            '対戦結果管理',
            '対戦結果',
            'manage_options',
            'tt-stats-results',
            array($this, 'results_page')
        );
        
        // インポート
        add_submenu_page(
            'tt-stats',
            'データインポート',
            'インポート',
            'manage_options',
            'tt-stats-import',
            array($this, 'import_page')
        );
        
        // 設定
        add_submenu_page(
            'tt-stats',
            '設定',
            '設定',
            'manage_options',
            'tt-stats-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * 管理画面用のCSSとJSを読み込み
     */
    public function enqueue_admin_assets($hook) {
        // このプラグインの管理ページでのみ読み込み
        if (strpos($hook, 'tt-stats') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'tt-stats-admin',
            TT_STATS_PLUGIN_URL . 'admin/css/admin-style.css',
            array(),
            TT_STATS_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'tt-stats-admin',
            TT_STATS_PLUGIN_URL . 'admin/js/admin-script.js',
            array('jquery'),
            TT_STATS_VERSION,
            true
        );
        
        // Ajax用のデータを渡す
        wp_localize_script('tt-stats-admin', 'ttStatsAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tt_stats_admin_nonce')
        ));
    }
    
    /**
     * ダッシュボードページ
     */
    public function dashboard_page() {
        include TT_STATS_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    /**
     * 選手管理ページ
     */
    public function players_page() {
        include TT_STATS_PLUGIN_DIR . 'admin/views/players.php';
    }
    
    /**
     * 試合管理ページ
     */
    public function matches_page() {
        include TT_STATS_PLUGIN_DIR . 'admin/views/matches.php';
    }
    
    /**
     * 対戦結果管理ページ
     */
    public function results_page() {
        include TT_STATS_PLUGIN_DIR . 'admin/views/results.php';
    }
    
    /**
     * インポートページ
     */
    public function import_page() {
        include TT_STATS_PLUGIN_DIR . 'admin/views/import.php';
    }
    
    /**
     * 設定ページ
     */
    public function settings_page() {
        include TT_STATS_PLUGIN_DIR . 'admin/views/settings.php';
    }
}
