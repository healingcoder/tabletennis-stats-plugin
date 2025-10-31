<?php
/**
 * データベース管理クラス
 * テーブルの作成・更新を管理
 */

if (!defined('ABSPATH')) {
    exit;
}

class TT_Stats_DB_Manager {
    
    private $wpdb;
    private $charset_collate;
    
    // テーブル名
    private $players_table;
    private $player_videos_table;
    private $matches_table;
    private $match_participants_table;
    private $match_results_table;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->charset_collate = $wpdb->get_charset_collate();
        
        // テーブル名を定義
        $this->players_table = $wpdb->prefix . 'tt_players';
        $this->player_videos_table = $wpdb->prefix . 'tt_player_videos';
        $this->matches_table = $wpdb->prefix . 'tt_matches';
        $this->match_participants_table = $wpdb->prefix . 'tt_match_participants';
        $this->match_results_table = $wpdb->prefix . 'tt_match_results';
    }
    
    /**
     * すべてのテーブルを作成
     */
    public function create_tables() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $this->create_players_table();
        $this->create_player_videos_table();
        $this->create_matches_table();
        $this->create_match_participants_table();
        $this->create_match_results_table();
    }
    
    /**
     * 選手テーブルを作成
     */
    private function create_players_table() {
        $sql = "CREATE TABLE {$this->players_table} (
            player_id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            name_kana varchar(100) DEFAULT NULL,
            gender enum('male','female','other') NOT NULL,
            prefecture varchar(20) DEFAULT NULL,
            tactics enum('right_pen','left_pen','right_shake','left_shake','other') DEFAULT NULL,
            tactics_detail text DEFAULT NULL,
            photo_url varchar(255) DEFAULT NULL,
            profile_text text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (player_id),
            KEY name (name),
            KEY gender (gender),
            KEY prefecture (prefecture),
            KEY tactics (tactics)
        ) {$this->charset_collate};";
        
        dbDelta($sql);
    }
    
    /**
     * 選手動画テーブルを作成
     */
    private function create_player_videos_table() {
        $sql = "CREATE TABLE {$this->player_videos_table} (
            video_id bigint(20) NOT NULL AUTO_INCREMENT,
            player_id bigint(20) NOT NULL,
            video_url varchar(500) NOT NULL,
            video_title varchar(200) DEFAULT NULL,
            video_description text DEFAULT NULL,
            display_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (video_id),
            KEY player_id (player_id),
            KEY display_order (display_order)
        ) {$this->charset_collate};";
        
        dbDelta($sql);
    }
    
    /**
     * 試合テーブルを作成
     */
    private function create_matches_table() {
        $sql = "CREATE TABLE {$this->matches_table} (
            match_id bigint(20) NOT NULL AUTO_INCREMENT,
            match_name varchar(200) NOT NULL,
            match_date date NOT NULL,
            venue varchar(200) DEFAULT NULL,
            match_type enum('tournament','league','other') DEFAULT 'tournament',
            description text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (match_id),
            KEY match_date (match_date),
            KEY match_name (match_name)
        ) {$this->charset_collate};";
        
        dbDelta($sql);
    }
    
    /**
     * 試合参加者テーブルを作成
     */
    private function create_match_participants_table() {
        $sql = "CREATE TABLE {$this->match_participants_table} (
            participant_id bigint(20) NOT NULL AUTO_INCREMENT,
            match_id bigint(20) NOT NULL,
            player_id bigint(20) NOT NULL,
            final_rank int(11) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (participant_id),
            KEY match_id (match_id),
            KEY player_id (player_id),
            KEY final_rank (final_rank),
            UNIQUE KEY unique_match_player (match_id, player_id)
        ) {$this->charset_collate};";
        
        dbDelta($sql);
    }
    
    /**
     * 対戦結果テーブルを作成
     */
    private function create_match_results_table() {
        $sql = "CREATE TABLE {$this->match_results_table} (
            result_id bigint(20) NOT NULL AUTO_INCREMENT,
            match_id bigint(20) NOT NULL,
            round_info varchar(100) DEFAULT NULL,
            player1_id bigint(20) NOT NULL,
            player2_id bigint(20) NOT NULL,
            player1_games int(11) DEFAULT NULL,
            player2_games int(11) DEFAULT NULL,
            winner_id bigint(20) DEFAULT NULL,
            notes text DEFAULT NULL,
            result_date datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (result_id),
            KEY match_id (match_id),
            KEY player1_id (player1_id),
            KEY player2_id (player2_id),
            KEY result_date (result_date),
            KEY players_match (player1_id, player2_id, match_id)
        ) {$this->charset_collate};";
        
        dbDelta($sql);
    }
    
    /**
     * テーブルを削除（アンインストール時用）
     */
    public function drop_tables() {
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->match_results_table}");
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->match_participants_table}");
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->player_videos_table}");
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->matches_table}");
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->players_table}");
    }
    
    /**
     * テーブル名を取得（他のクラスから使用）
     */
    public function get_table_names() {
        return array(
            'players' => $this->players_table,
            'player_videos' => $this->player_videos_table,
            'matches' => $this->matches_table,
            'match_participants' => $this->match_participants_table,
            'match_results' => $this->match_results_table
        );
    }
}
