<?php
/**
 * バックアップ＆復元機能クラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class TT_Stats_Backup_Manager {
    
    private $wpdb;
    private $tables;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $db_manager = new TT_Stats_DB_Manager();
        $this->tables = $db_manager->get_table_names();
        
        // Ajax アクションを登録
        add_action('wp_ajax_tt_stats_create_backup', array($this, 'ajax_create_backup'));
        add_action('wp_ajax_tt_stats_restore_backup', array($this, 'ajax_restore_backup'));
    }
    
    /**
     * バックアップを作成（Ajax）
     */
    public function ajax_create_backup() {
        check_ajax_referer('tt_stats_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません');
        }
        
        try {
            $zip_path = $this->create_backup();
            
            if ($zip_path) {
                $zip_url = str_replace(
                    WP_CONTENT_DIR,
                    content_url(),
                    $zip_path
                );
                
                wp_send_json_success(array(
                    'message' => 'バックアップが作成されました',
                    'download_url' => $zip_url,
                    'filename' => basename($zip_path)
                ));
            } else {
                wp_send_json_error('バックアップの作成に失敗しました');
            }
        } catch (Exception $e) {
            wp_send_json_error('エラー: ' . $e->getMessage());
        }
    }
    
    /**
     * バックアップを作成
     */
    private function create_backup() {
        // 一時ディレクトリを作成
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/tt-stats-backup-temp-' . time();
        
        if (!wp_mkdir_p($temp_dir)) {
            throw new Exception('一時ディレクトリの作成に失敗しました');
        }
        
        // 各テーブルをCSVに出力
        $csv_files = array();
        
        // 1. 選手データ
        $csv_files[] = $this->export_table_to_csv(
            $this->tables['players'],
            $temp_dir . '/players.csv',
            array('player_id', 'name', 'name_kana', 'gender', 'prefecture', 'tactics', 'tactics_detail', 'photo_url', 'profile_text', 'created_at', 'updated_at')
        );
        
        // 2. 選手動画データ
        $csv_files[] = $this->export_table_to_csv(
            $this->tables['player_videos'],
            $temp_dir . '/player_videos.csv',
            array('video_id', 'player_id', 'video_url', 'video_title', 'video_description', 'display_order', 'created_at')
        );
        
        // 3. 試合データ
        $csv_files[] = $this->export_table_to_csv(
            $this->tables['matches'],
            $temp_dir . '/matches.csv',
            array('match_id', 'match_name', 'match_date', 'venue', 'match_type', 'description', 'created_at', 'updated_at')
        );
        
        // 4. 試合参加者データ
        $csv_files[] = $this->export_table_to_csv(
            $this->tables['match_participants'],
            $temp_dir . '/match_participants.csv',
            array('participant_id', 'match_id', 'player_id', 'final_rank', 'notes', 'created_at')
        );
        
        // 5. 対戦結果データ
        $csv_files[] = $this->export_table_to_csv(
            $this->tables['match_results'],
            $temp_dir . '/match_results.csv',
            array('result_id', 'match_id', 'round_info', 'player1_id', 'player2_id', 'player1_games', 'player2_games', 'winner_id', 'notes', 'result_date', 'created_at')
        );
        
        // ZIPファイルを作成
        $zip_filename = 'tt-stats-backup-' . date('Ymd-His') . '.zip';
        $zip_path = $upload_dir['basedir'] . '/' . $zip_filename;
        
        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE) !== true) {
            $this->cleanup_temp_dir($temp_dir);
            throw new Exception('ZIPファイルの作成に失敗しました');
        }
        
        foreach ($csv_files as $csv_file) {
            if (file_exists($csv_file)) {
                $zip->addFile($csv_file, basename($csv_file));
            }
        }
        
        $zip->close();
        
        // 一時ファイルを削除
        $this->cleanup_temp_dir($temp_dir);
        
        return $zip_path;
    }
    
    /**
     * テーブルをCSVにエクスポート
     */
    private function export_table_to_csv($table_name, $csv_path, $columns) {
        $rows = $this->wpdb->get_results("SELECT * FROM {$table_name}", ARRAY_A);
        
        $fp = fopen($csv_path, 'w');
        
        // BOMを追加（Excel対応）
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        
        // ヘッダー行を出力
        fputcsv($fp, $columns);
        
        // データ行を出力
        foreach ($rows as $row) {
            $data = array();
            foreach ($columns as $column) {
                $data[] = isset($row[$column]) ? $row[$column] : '';
            }
            fputcsv($fp, $data);
        }
        
        fclose($fp);
        
        return $csv_path;
    }
    
    /**
     * 一時ディレクトリをクリーンアップ
     */
    private function cleanup_temp_dir($temp_dir) {
        if (is_dir($temp_dir)) {
            $files = glob($temp_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            @rmdir($temp_dir);
        }
    }
    
    /**
     * バックアップを復元（Ajax）
     */
    public function ajax_restore_backup() {
        check_ajax_referer('tt_stats_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません');
        }
        
        if (empty($_FILES['backup_file']['tmp_name'])) {
            wp_send_json_error('ファイルが選択されていません');
        }
        
        $file = $_FILES['backup_file'];
        
        // ファイル検証
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_ext !== 'zip') {
            wp_send_json_error('ZIPファイルのみアップロード可能です');
        }
        
        try {
            $result = $this->restore_backup($file['tmp_name']);
            
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result['message']);
            }
        } catch (Exception $e) {
            wp_send_json_error('エラー: ' . $e->getMessage());
        }
    }
    
    /**
     * バックアップを復元
     */
    private function restore_backup($zip_path) {
        // 一時ディレクトリを作成
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/tt-stats-restore-temp-' . time();
        
        if (!wp_mkdir_p($temp_dir)) {
            throw new Exception('一時ディレクトリの作成に失敗しました');
        }
        
        // ZIPを展開
        $zip = new ZipArchive();
        if ($zip->open($zip_path) !== true) {
            $this->cleanup_temp_dir($temp_dir);
            throw new Exception('ZIPファイルの展開に失敗しました');
        }
        
        $zip->extractTo($temp_dir);
        $zip->close();
        
        // 必要なCSVファイルが存在するか確認
        $required_files = array(
            'players.csv',
            'player_videos.csv',
            'matches.csv',
            'match_participants.csv',
            'match_results.csv'
        );
        
        foreach ($required_files as $file) {
            if (!file_exists($temp_dir . '/' . $file)) {
                $this->cleanup_temp_dir($temp_dir);
                return array(
                    'success' => false,
                    'message' => "必要なファイルが見つかりません: {$file}"
                );
            }
        }
        
        // トランザクション開始
        $this->wpdb->query('START TRANSACTION');
        
        try {
            // 既存データを削除（外部キー制約の順序を考慮）
            $this->wpdb->query("DELETE FROM {$this->tables['match_results']}");
            $this->wpdb->query("DELETE FROM {$this->tables['match_participants']}");
            $this->wpdb->query("DELETE FROM {$this->tables['player_videos']}");
            $this->wpdb->query("DELETE FROM {$this->tables['matches']}");
            $this->wpdb->query("DELETE FROM {$this->tables['players']}");
            
            // 各CSVからデータをインポート
            $import_results = array();
            
            // 1. 選手データ
            $import_results['players'] = $this->import_csv_to_table(
                $temp_dir . '/players.csv',
                $this->tables['players']
            );
            
            // 2. 試合データ
            $import_results['matches'] = $this->import_csv_to_table(
                $temp_dir . '/matches.csv',
                $this->tables['matches']
            );
            
            // 3. 選手動画データ
            $import_results['player_videos'] = $this->import_csv_to_table(
                $temp_dir . '/player_videos.csv',
                $this->tables['player_videos']
            );
            
            // 4. 試合参加者データ
            $import_results['match_participants'] = $this->import_csv_to_table(
                $temp_dir . '/match_participants.csv',
                $this->tables['match_participants']
            );
            
            // 5. 対戦結果データ
            $import_results['match_results'] = $this->import_csv_to_table(
                $temp_dir . '/match_results.csv',
                $this->tables['match_results']
            );
            
            // コミット
            $this->wpdb->query('COMMIT');
            
            // 一時ファイルを削除
            $this->cleanup_temp_dir($temp_dir);
            
            return array(
                'success' => true,
                'message' => '復元が完了しました',
                'details' => $import_results
            );
            
        } catch (Exception $e) {
            // ロールバック
            $this->wpdb->query('ROLLBACK');
            $this->cleanup_temp_dir($temp_dir);
            
            throw new Exception('復元中にエラーが発生しました: ' . $e->getMessage());
        }
    }
    
    /**
     * CSVからテーブルにインポート
     */
    private function import_csv_to_table($csv_path, $table_name) {
        $fp = fopen($csv_path, 'r');
        
        // BOMをスキップ
        $bom = fread($fp, 3);
        if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            rewind($fp);
        }
        
        // ヘッダー行を取得
        $headers = fgetcsv($fp);
        
        if (!$headers) {
            fclose($fp);
            throw new Exception("CSVヘッダーの読み込みに失敗しました: {$csv_path}");
        }
        
        $count = 0;
        
        // データ行を読み込み
        while (($row = fgetcsv($fp)) !== false) {
            $data = array();
            
            foreach ($headers as $index => $column) {
                $value = isset($row[$index]) ? $row[$index] : null;
                
                // 空文字列をNULLに変換
                if ($value === '') {
                    $value = null;
                }
                
                $data[$column] = $value;
            }
            
            // データを挿入
            $result = $this->wpdb->insert($table_name, $data);
            
            if ($result === false) {
                throw new Exception("データの挿入に失敗しました: " . $this->wpdb->last_error);
            }
            
            $count++;
        }
        
        fclose($fp);
        
        return $count;
    }
    
    /**
     * 古いバックアップファイルを削除
     */
    public function cleanup_old_backups($days = 30) {
        $upload_dir = wp_upload_dir();
        $backup_files = glob($upload_dir['basedir'] . '/tt-stats-backup-*.zip');
        
        $deleted = 0;
        $cutoff_time = time() - ($days * 24 * 60 * 60);
        
        foreach ($backup_files as $file) {
            if (filemtime($file) < $cutoff_time) {
                if (@unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * バックアップファイル一覧を取得
     */
    public function get_backup_files() {
        $upload_dir = wp_upload_dir();
        $backup_files = glob($upload_dir['basedir'] . '/tt-stats-backup-*.zip');
        
        $files = array();
        
        foreach ($backup_files as $file) {
            $files[] = array(
                'filename' => basename($file),
                'size' => filesize($file),
                'date' => filemtime($file),
                'url' => str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $file)
            );
        }
        
        // 日付でソート（新しい順）
        usort($files, function($a, $b) {
            return $b['date'] - $a['date'];
        });
        
        return $files;
    }
}
