<?php
/**
 * CSVインポート処理クラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class TT_Stats_Import_Handler {
    
    private $wpdb;
    private $tables;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $db_manager = new TT_Stats_DB_Manager();
        $this->tables = $db_manager->get_table_names();
        
        // Ajax アクションを登録
        add_action('wp_ajax_tt_stats_import_players', array($this, 'ajax_import_players'));
        add_action('wp_ajax_tt_stats_import_matches', array($this, 'ajax_import_matches'));
        add_action('wp_ajax_tt_stats_import_participants', array($this, 'ajax_import_participants'));
        add_action('wp_ajax_tt_stats_import_results', array($this, 'ajax_import_results'));
    }
    
    /**
     * CSVファイルを読み込み（UTF-8 BOM対応）
     */
    private function read_csv($file_path) {
        $rows = array();
        
        if (!file_exists($file_path)) {
            return false;
        }
        
        // ファイルを読み込み
        $content = file_get_contents($file_path);
        
        // BOMを削除
        $content = str_replace("\xEF\xBB\xBF", '', $content);
        
        // 行ごとに分割
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            // CSVパース
            $row = str_getcsv($line);
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    /**
     * 選手インポート（Ajax）
     */
    public function ajax_import_players() {
        check_ajax_referer('tt_stats_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません');
        }
        
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $batch_size = 50; // 一度に処理する件数
        
        // 一時ファイルパスを取得
        $temp_file = get_transient('tt_stats_import_players_file');
        if (!$temp_file || !file_exists($temp_file)) {
            wp_send_json_error('ファイルが見つかりません');
        }
        
        $rows = $this->read_csv($temp_file);
        if ($rows === false) {
            wp_send_json_error('CSVの読み込みに失敗しました');
        }
        
        // ヘッダー行を除外
        $header = array_shift($rows);
        $total = count($rows);
        
        // バッチ処理
        $batch_rows = array_slice($rows, $offset, $batch_size);
        $success_count = 0;
        $error_count = 0;
        $errors = array();
        
        foreach ($batch_rows as $index => $row) {
            $line_number = $offset + $index + 2; // ヘッダー行+1
            
            $result = $this->import_player_row($row, $line_number);
            
            if ($result['success']) {
                $success_count++;
            } else {
                $error_count++;
                $errors[] = $result['message'];
            }
        }
        
        $new_offset = $offset + $batch_size;
        $is_complete = $new_offset >= $total;
        
        // 完了したら一時ファイルを削除
        if ($is_complete) {
            @unlink($temp_file);
            delete_transient('tt_stats_import_players_file');
        }
        
        wp_send_json_success(array(
            'offset' => $new_offset,
            'total' => $total,
            'success_count' => $success_count,
            'error_count' => $error_count,
            'errors' => $errors,
            'is_complete' => $is_complete,
            'progress' => $total > 0 ? round(min($new_offset, $total) / $total * 100) : 100
        ));
    }
    
    /**
     * 選手データを1行インポート
     */
    private function import_player_row($row, $line_number) {
        // CSVフォーマット: name, name_kana, gender, prefecture, tactics, tactics_detail, photo_url, profile_text
        
        if (count($row) < 3) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: データが不足しています"
            );
        }
        
        $name = trim($row[0]);
        $name_kana = isset($row[1]) ? trim($row[1]) : '';
        $gender = isset($row[2]) ? trim($row[2]) : 'male';
        $prefecture = isset($row[3]) ? trim($row[3]) : '';
        $tactics = isset($row[4]) ? trim($row[4]) : '';
        $tactics_detail = isset($row[5]) ? trim($row[5]) : '';
        $photo_url = isset($row[6]) ? trim($row[6]) : '';
        $profile_text = isset($row[7]) ? trim($row[7]) : '';
        
        if (empty($name)) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: 選手名が空です"
            );
        }
        
        // 性別の検証
        $valid_genders = array('male', 'female', 'other');
        if (!in_array($gender, $valid_genders)) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: 性別が不正です（male, female, otherのいずれか）"
            );
        }
        
        // 戦術の検証
        $valid_tactics = array('', 'right_pen', 'left_pen', 'right_shake', 'left_shake', 'other');
        if (!in_array($tactics, $valid_tactics)) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: 戦術が不正です"
            );
        }
        
        // 挿入
        $result = $this->wpdb->insert(
            $this->tables['players'],
            array(
                'name' => $name,
                'name_kana' => $name_kana,
                'gender' => $gender,
                'prefecture' => $prefecture,
                'tactics' => $tactics ? $tactics : null,
                'tactics_detail' => $tactics_detail,
                'photo_url' => $photo_url,
                'profile_text' => $profile_text
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: データベースエラー - " . $this->wpdb->last_error
            );
        }
        
        return array(
            'success' => true,
            'message' => "{$line_number}行目: {$name} を登録しました"
        );
    }
    
    /**
     * 試合インポート（Ajax）
     */
    public function ajax_import_matches() {
        check_ajax_referer('tt_stats_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません');
        }
        
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $batch_size = 50;
        
        $temp_file = get_transient('tt_stats_import_matches_file');
        if (!$temp_file || !file_exists($temp_file)) {
            wp_send_json_error('ファイルが見つかりません');
        }
        
        $rows = $this->read_csv($temp_file);
        if ($rows === false) {
            wp_send_json_error('CSVの読み込みに失敗しました');
        }
        
        $header = array_shift($rows);
        $total = count($rows);
        
        $batch_rows = array_slice($rows, $offset, $batch_size);
        $success_count = 0;
        $error_count = 0;
        $errors = array();
        
        foreach ($batch_rows as $index => $row) {
            $line_number = $offset + $index + 2;
            
            $result = $this->import_match_row($row, $line_number);
            
            if ($result['success']) {
                $success_count++;
            } else {
                $error_count++;
                $errors[] = $result['message'];
            }
        }
        
        $new_offset = $offset + $batch_size;
        $is_complete = $new_offset >= $total;
        
        if ($is_complete) {
            @unlink($temp_file);
            delete_transient('tt_stats_import_matches_file');
        }
        
        wp_send_json_success(array(
            'offset' => $new_offset,
            'total' => $total,
            'success_count' => $success_count,
            'error_count' => $error_count,
            'errors' => $errors,
            'is_complete' => $is_complete,
            'progress' => $total > 0 ? round(min($new_offset, $total) / $total * 100) : 100
        ));
    }
    
    /**
     * 試合データを1行インポート
     */
    private function import_match_row($row, $line_number) {
        // CSVフォーマット: match_name, match_date, venue, match_type, description
        
        if (count($row) < 2) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: データが不足しています"
            );
        }
        
        $match_name = trim($row[0]);
        $match_date = isset($row[1]) ? trim($row[1]) : '';
        $venue = isset($row[2]) ? trim($row[2]) : '';
        $match_type = isset($row[3]) ? trim($row[3]) : 'tournament';
        $description = isset($row[4]) ? trim($row[4]) : '';
        
        if (empty($match_name)) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: 試合名が空です"
            );
        }
        
        if (empty($match_date)) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: 開催日が空です"
            );
        }
        
        // 日付フォーマットの検証
        $date_obj = date_create($match_date);
        if (!$date_obj) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: 日付フォーマットが不正です（YYYY-MM-DD形式）"
            );
        }
        $match_date = date_format($date_obj, 'Y-m-d');
        
        // 試合種別の検証
        $valid_types = array('tournament', 'league', 'other');
        if (!in_array($match_type, $valid_types)) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: 試合種別が不正です（tournament, league, otherのいずれか）"
            );
        }
        
        // 挿入
        $result = $this->wpdb->insert(
            $this->tables['matches'],
            array(
                'match_name' => $match_name,
                'match_date' => $match_date,
                'venue' => $venue,
                'match_type' => $match_type,
                'description' => $description
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: データベースエラー - " . $this->wpdb->last_error
            );
        }
        
        return array(
            'success' => true,
            'message' => "{$line_number}行目: {$match_name} を登録しました"
        );
    }
    
    /**
     * 試合参加者インポート（Ajax）
     */
    public function ajax_import_participants() {
        check_ajax_referer('tt_stats_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません');
        }
        
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $batch_size = 50;
        
        $temp_file = get_transient('tt_stats_import_participants_file');
        if (!$temp_file || !file_exists($temp_file)) {
            wp_send_json_error('ファイルが見つかりません');
        }
        
        $rows = $this->read_csv($temp_file);
        if ($rows === false) {
            wp_send_json_error('CSVの読み込みに失敗しました');
        }
        
        $header = array_shift($rows);
        $total = count($rows);
        
        $batch_rows = array_slice($rows, $offset, $batch_size);
        $success_count = 0;
        $error_count = 0;
        $errors = array();
        
        foreach ($batch_rows as $index => $row) {
            $line_number = $offset + $index + 2;
            
            $result = $this->import_participant_row($row, $line_number);
            
            if ($result['success']) {
                $success_count++;
            } else {
                $error_count++;
                $errors[] = $result['message'];
            }
        }
        
        $new_offset = $offset + $batch_size;
        $is_complete = $new_offset >= $total;
        
        if ($is_complete) {
            @unlink($temp_file);
            delete_transient('tt_stats_import_participants_file');
        }
        
        wp_send_json_success(array(
            'offset' => $new_offset,
            'total' => $total,
            'success_count' => $success_count,
            'error_count' => $error_count,
            'errors' => $errors,
            'is_complete' => $is_complete,
            'progress' => $total > 0 ? round(min($new_offset, $total) / $total * 100) : 100
        ));
    }
    
    /**
     * 試合参加者データを1行インポート
     */
    private function import_participant_row($row, $line_number) {
        // CSVフォーマット: match_name, player_name, final_rank, notes
        
        if (count($row) < 2) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: データが不足しています"
            );
        }
        
        $match_name = trim($row[0]);
        $player_name = trim($row[1]);
        $final_rank = isset($row[2]) ? trim($row[2]) : '';
        $notes = isset($row[3]) ? trim($row[3]) : '';
        
        if (empty($match_name)) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: 試合名が空です"
            );
        }
        
        if (empty($player_name)) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: 選手名が空です"
            );
        }
        
        // 試合IDを検索
        $match = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT match_id FROM {$this->tables['matches']} WHERE match_name = %s LIMIT 1",
            $match_name
        ));
        
        if (!$match) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: 試合「{$match_name}」が見つかりません"
            );
        }
        
        // 選手IDを検索
        $player = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT player_id FROM {$this->tables['players']} WHERE name = %s LIMIT 1",
            $player_name
        ));
        
        if (!$player) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: 選手「{$player_name}」が見つかりません"
            );
        }
        
        // 順位の検証
        $final_rank_value = null;
        if ($final_rank !== '') {
            if (!is_numeric($final_rank) || intval($final_rank) < 1) {
                return array(
                    'success' => false,
                    'message' => "{$line_number}行目: 順位が不正です（1以上の数値）"
                );
            }
            $final_rank_value = intval($final_rank);
        }
        
        // 重複チェック
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT participant_id FROM {$this->tables['match_participants']} 
             WHERE match_id = %d AND player_id = %d",
            $match->match_id,
            $player->player_id
        ));
        
        if ($existing) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: すでに登録されています"
            );
        }
        
        // 挿入
        $result = $this->wpdb->insert(
            $this->tables['match_participants'],
            array(
                'match_id' => $match->match_id,
                'player_id' => $player->player_id,
                'final_rank' => $final_rank_value,
                'notes' => $notes
            ),
            array('%d', '%d', '%d', '%s')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: データベースエラー - " . $this->wpdb->last_error
            );
        }
        
        return array(
            'success' => true,
            'message' => "{$line_number}行目: {$match_name} - {$player_name} を登録しました"
        );
    }
    
    /**
     * 対戦結果インポート（Ajax）
     */
    public function ajax_import_results() {
        check_ajax_referer('tt_stats_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません');
        }
        
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $batch_size = 50;
        
        $temp_file = get_transient('tt_stats_import_results_file');
        if (!$temp_file || !file_exists($temp_file)) {
            wp_send_json_error('ファイルが見つかりません');
        }
        
        $rows = $this->read_csv($temp_file);
        if ($rows === false) {
            wp_send_json_error('CSVの読み込みに失敗しました');
        }
        
        $header = array_shift($rows);
        $total = count($rows);
        
        $batch_rows = array_slice($rows, $offset, $batch_size);
        $success_count = 0;
        $error_count = 0;
        $errors = array();
        
        foreach ($batch_rows as $index => $row) {
            $line_number = $offset + $index + 2;
            
            $result = $this->import_result_row($row, $line_number);
            
            if ($result['success']) {
                $success_count++;
            } else {
                $error_count++;
                $errors[] = $result['message'];
            }
        }
        
        $new_offset = $offset + $batch_size;
        $is_complete = $new_offset >= $total;
        
        if ($is_complete) {
            @unlink($temp_file);
            delete_transient('tt_stats_import_results_file');
        }
        
        wp_send_json_success(array(
            'offset' => $new_offset,
            'total' => $total,
            'success_count' => $success_count,
            'error_count' => $error_count,
            'errors' => $errors,
            'is_complete' => $is_complete,
            'progress' => $total > 0 ? round(min($new_offset, $total) / $total * 100) : 100
        ));
    }
    
    /**
     * 対戦結果データを1行インポート
     */
    private function import_result_row($row, $line_number) {
        // CSVフォーマット: match_name, round_info, player1_name, player2_name, player1_games, player2_games, notes, result_date
        
        if (count($row) < 6) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: データが不足しています"
            );
        }
        
        $match_name = trim($row[0]);
        $round_info = isset($row[1]) ? trim($row[1]) : '';
        $player1_name = trim($row[2]);
        $player2_name = trim($row[3]);
        $player1_games = trim($row[4]);
        $player2_games = trim($row[5]);
        $notes = isset($row[6]) ? trim($row[6]) : '';
        $result_date = isset($row[7]) ? trim($row[7]) : '';
        
        if (empty($match_name)) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: 試合名が空です"
            );
        }
        
        if (empty($player1_name) || empty($player2_name)) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: 選手名が空です"
            );
        }
        
        if (!is_numeric($player1_games) || !is_numeric($player2_games)) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: ゲーム数が不正です"
            );
        }
        
        $player1_games = intval($player1_games);
        $player2_games = intval($player2_games);
        
        // 試合IDを検索
        $match = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT match_id FROM {$this->tables['matches']} WHERE match_name = %s LIMIT 1",
            $match_name
        ));
        
        if (!$match) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: 試合「{$match_name}」が見つかりません"
            );
        }
        
        // 選手1のIDを検索
        $player1 = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT player_id FROM {$this->tables['players']} WHERE name = %s LIMIT 1",
            $player1_name
        ));
        
        if (!$player1) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: 選手「{$player1_name}」が見つかりません"
            );
        }
        
        // 選手2のIDを検索
        $player2 = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT player_id FROM {$this->tables['players']} WHERE name = %s LIMIT 1",
            $player2_name
        ));
        
        if (!$player2) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: 選手「{$player2_name}」が見つかりません"
            );
        }
        
        // 勝者を判定
        $winner_id = null;
        if ($player1_games > $player2_games) {
            $winner_id = $player1->player_id;
        } elseif ($player2_games > $player1_games) {
            $winner_id = $player2->player_id;
        }
        
        // 日時の検証
        $result_date_value = null;
        if (!empty($result_date)) {
            $date_obj = date_create($result_date);
            if ($date_obj) {
                $result_date_value = date_format($date_obj, 'Y-m-d H:i:s');
            }
        }
        
        // 挿入
        $result = $this->wpdb->insert(
            $this->tables['match_results'],
            array(
                'match_id' => $match->match_id,
                'round_info' => $round_info,
                'player1_id' => $player1->player_id,
                'player2_id' => $player2->player_id,
                'player1_games' => $player1_games,
                'player2_games' => $player2_games,
                'winner_id' => $winner_id,
                'notes' => $notes,
                'result_date' => $result_date_value
            ),
            array('%d', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => "{$line_number}行目: データベースエラー - " . $this->wpdb->last_error
            );
        }
        
        return array(
            'success' => true,
            'message' => "{$line_number}行目: {$player1_name} vs {$player2_name} を登録しました"
        );
    }
}
