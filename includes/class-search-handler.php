<?php
/**
 * 検索機能管理クラス
 * 選手検索、試合検索、対戦検索の処理
 */

if (!defined('ABSPATH')) {
    exit;
}

class TT_Stats_Search_Handler {
    
    private $wpdb;
    private $tables;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $db_manager = new TT_Stats_DB_Manager();
        $this->tables = $db_manager->get_table_names();
        
        // Ajax アクションを登録
        add_action('wp_ajax_tt_stats_search_players', array($this, 'ajax_search_players'));
        add_action('wp_ajax_nopriv_tt_stats_search_players', array($this, 'ajax_search_players'));
        
        add_action('wp_ajax_tt_stats_search_matches', array($this, 'ajax_search_matches'));
        add_action('wp_ajax_nopriv_tt_stats_search_matches', array($this, 'ajax_search_matches'));
        
        add_action('wp_ajax_tt_stats_search_vs', array($this, 'ajax_search_vs'));
        add_action('wp_ajax_nopriv_tt_stats_search_vs', array($this, 'ajax_search_vs'));
    }
    
    /**
     * 選手検索（Ajax）
     */
    public function ajax_search_players() {
        check_ajax_referer('tt_stats_public_nonce', 'nonce');
        
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $gender = isset($_POST['gender']) ? sanitize_text_field($_POST['gender']) : '';
        $prefecture = isset($_POST['prefecture']) ? sanitize_text_field($_POST['prefecture']) : '';
        $tactics = isset($_POST['tactics']) ? sanitize_text_field($_POST['tactics']) : '';
        
        $results = $this->search_players($name, $gender, $prefecture, $tactics);
        
        wp_send_json_success($results);
    }
    
    /**
     * 選手検索処理
     */
    public function search_players($name = '', $gender = '', $prefecture = '', $tactics = '', $limit = 50) {
        $where = array('1=1');
        $params = array();
        
        if (!empty($name)) {
            $where[] = '(name LIKE %s OR name_kana LIKE %s)';
            $params[] = '%' . $this->wpdb->esc_like($name) . '%';
            $params[] = '%' . $this->wpdb->esc_like($name) . '%';
        }
        
        if (!empty($gender)) {
            $where[] = 'gender = %s';
            $params[] = $gender;
        }
        
        if (!empty($prefecture)) {
            $where[] = 'prefecture = %s';
            $params[] = $prefecture;
        }
        
        if (!empty($tactics)) {
            $where[] = 'tactics = %s';
            $params[] = $tactics;
        }
        
        $where_clause = implode(' AND ', $where);
        
        $query = "SELECT * FROM {$this->tables['players']} WHERE {$where_clause} ORDER BY name LIMIT %d";
        $params[] = $limit;
        
        $sql = $this->wpdb->prepare($query, $params);
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * 試合検索（Ajax）
     */
    public function ajax_search_matches() {
        check_ajax_referer('tt_stats_public_nonce', 'nonce');
        
        $match_name = isset($_POST['match_name']) ? sanitize_text_field($_POST['match_name']) : '';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        $results = $this->search_matches($match_name, $date_from, $date_to);
        
        wp_send_json_success($results);
    }
    
    /**
     * 試合検索処理
     */
    public function search_matches($match_name = '', $date_from = '', $date_to = '', $limit = 50) {
        $where = array('1=1');
        $params = array();
        
        if (!empty($match_name)) {
            $where[] = 'match_name LIKE %s';
            $params[] = '%' . $this->wpdb->esc_like($match_name) . '%';
        }
        
        if (!empty($date_from)) {
            $where[] = 'match_date >= %s';
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $where[] = 'match_date <= %s';
            $params[] = $date_to;
        }
        
        $where_clause = implode(' AND ', $where);
        
        $query = "SELECT * FROM {$this->tables['matches']} WHERE {$where_clause} ORDER BY match_date DESC LIMIT %d";
        $params[] = $limit;
        
        $sql = $this->wpdb->prepare($query, $params);
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * 対戦検索（Ajax）
     */
    public function ajax_search_vs() {
        check_ajax_referer('tt_stats_public_nonce', 'nonce');
        
        $player1_name = isset($_POST['player1_name']) ? sanitize_text_field($_POST['player1_name']) : '';
        $player2_name = isset($_POST['player2_name']) ? sanitize_text_field($_POST['player2_name']) : '';
        
        $results = $this->search_vs($player1_name, $player2_name);
        
        wp_send_json_success($results);
    }
    
    /**
     * 対戦検索処理（2選手の直接対決）
     */
    public function search_vs($player1_name, $player2_name, $limit = 50) {
        // 選手IDを取得
        $player1_query = $this->wpdb->prepare(
            "SELECT player_id FROM {$this->tables['players']} WHERE name LIKE %s OR name_kana LIKE %s LIMIT 1",
            '%' . $this->wpdb->esc_like($player1_name) . '%',
            '%' . $this->wpdb->esc_like($player1_name) . '%'
        );
        $player1 = $this->wpdb->get_row($player1_query);
        
        $player2_query = $this->wpdb->prepare(
            "SELECT player_id FROM {$this->tables['players']} WHERE name LIKE %s OR name_kana LIKE %s LIMIT 1",
            '%' . $this->wpdb->esc_like($player2_name) . '%',
            '%' . $this->wpdb->esc_like($player2_name) . '%'
        );
        $player2 = $this->wpdb->get_row($player2_query);
        
        if (!$player1 || !$player2) {
            return array();
        }
        
        // 2選手の対戦結果を検索
        $query = $this->wpdb->prepare(
            "SELECT r.*, m.match_name, m.match_date, 
                    p1.name as player1_name, p2.name as player2_name
             FROM {$this->tables['match_results']} r
             INNER JOIN {$this->tables['matches']} m ON r.match_id = m.match_id
             INNER JOIN {$this->tables['players']} p1 ON r.player1_id = p1.player_id
             INNER JOIN {$this->tables['players']} p2 ON r.player2_id = p2.player_id
             WHERE (r.player1_id = %d AND r.player2_id = %d) 
                OR (r.player1_id = %d AND r.player2_id = %d)
             ORDER BY m.match_date DESC
             LIMIT %d",
            $player1->player_id,
            $player2->player_id,
            $player2->player_id,
            $player1->player_id,
            $limit
        );
        
        return $this->wpdb->get_results($query);
    }
    
    /**
     * 最近の試合を取得（トップページ用）
     */
    public function get_recent_matches($limit = 5) {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->tables['matches']} 
             ORDER BY match_date DESC 
             LIMIT %d",
            $limit
        );
        
        return $this->wpdb->get_results($query);
    }
    
    /**
     * 試合のベスト16を取得
     */
    public function get_match_top_players($match_id, $limit = 16) {
        $query = $this->wpdb->prepare(
            "SELECT mp.*, p.name, p.name_kana 
             FROM {$this->tables['match_participants']} mp
             INNER JOIN {$this->tables['players']} p ON mp.player_id = p.player_id
             WHERE mp.match_id = %d AND mp.final_rank <= %d
             ORDER BY mp.final_rank ASC",
            $match_id,
            $limit
        );
        
        return $this->wpdb->get_results($query);
    }
}
