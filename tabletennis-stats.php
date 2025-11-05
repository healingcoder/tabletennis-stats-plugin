<?php
/**
 * Plugin Name: 卓球プレーヤー成績管理システム
 * Plugin URI: https://example.com
 * Description: 卓球プレーヤーの試合成績・対戦結果を管理・検索するWordPressプラグイン
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: tabletennis-stats
 */

// セキュリティ: 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

// プラグインの定数定義
define('TT_STATS_VERSION', '1.0.0');
define('TT_STATS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TT_STATS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TT_STATS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * メインクラス
 */
class TableTennis_Stats {
    
    private static $instance = null;
    
    /**
     * シングルトンインスタンスを取得
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * コンストラクタ
     */
    private function __construct() {
        $this->load_dependencies();
        $this->define_hooks();
    }
    
    /**
     * 依存ファイルの読み込み
     */
    private function load_dependencies() {
        // データベース管理
        require_once TT_STATS_PLUGIN_DIR . 'includes/class-db-manager.php';

        // インポートハンドラー
        require_once TT_STATS_PLUGIN_DIR . 'includes/class-import-handler.php';
        
        // 管理画面
        if (is_admin()) {
            require_once TT_STATS_PLUGIN_DIR . 'includes/class-admin-manager.php';
        }
        
        // スマホアプリ
        require_once TT_STATS_PLUGIN_DIR . 'includes/class-mobile-app.php';
    }
    
    /**
     * WordPressフックの定義
     */
    private function define_hooks() {
        // プラグイン有効化・無効化
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // 初期化
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * プラグイン有効化時の処理
     */
    public function activate() {
        $db_manager = new TT_Stats_DB_Manager();
        $db_manager->create_tables();
        
        // バージョン情報を保存
        update_option('tt_stats_version', TT_STATS_VERSION);
        
        // リライトルールをフラッシュ
        flush_rewrite_rules();
    }
    
    /**
     * プラグイン無効化時の処理
     */
    public function deactivate() {
        // リライトルールをフラッシュ
        flush_rewrite_rules();
    }
    
    /**
     * プラグイン初期化
     */
    public function init() {
        // 管理画面の初期化
        if (is_admin()) {
            $admin_manager = new TT_Stats_Admin_Manager();
        }

        // インポートハンドラーの初期化
        new TT_Stats_Import_Handler();

        // スマホアプリの初期化
        new TT_Stats_Mobile_App();
    }
}

/**
 * プラグインを起動
 */
function tt_stats_init() {
    return TableTennis_Stats::get_instance();
}

// プラグイン起動
tt_stats_init();
