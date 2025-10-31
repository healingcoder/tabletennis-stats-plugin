<?php
/**
 * 管理画面 - 選手管理
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$db_manager = new TT_Stats_DB_Manager();
$tables = $db_manager->get_table_names();

// アクション処理
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$player_id = isset($_GET['player_id']) ? intval($_GET['player_id']) : 0;
$message = '';
$error = '';

// 削除処理
if ($action === 'delete' && $player_id && check_admin_referer('delete_player_' . $player_id)) {
    $deleted = $wpdb->delete($tables['players'], array('player_id' => $player_id), array('%d'));
    if ($deleted) {
        $message = '選手を削除しました。';
    } else {
        $error = '削除に失敗しました。';
    }
    $action = 'list';
}

// 保存処理
if (isset($_POST['save_player']) && check_admin_referer('save_player')) {
    $player_data = array(
        'name' => sanitize_text_field($_POST['name']),
        'name_kana' => sanitize_text_field($_POST['name_kana']),
        'gender' => sanitize_text_field($_POST['gender']),
        'prefecture' => sanitize_text_field($_POST['prefecture']),
        'tactics' => sanitize_text_field($_POST['tactics']),
        'tactics_detail' => sanitize_textarea_field($_POST['tactics_detail']),
        'photo_url' => esc_url_raw($_POST['photo_url']),
        'profile_text' => sanitize_textarea_field($_POST['profile_text'])
    );
    
    $edit_id = isset($_POST['player_id']) ? intval($_POST['player_id']) : 0;
    
    if ($edit_id > 0) {
        // 更新
        $updated = $wpdb->update(
            $tables['players'],
            $player_data,
            array('player_id' => $edit_id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
        if ($updated !== false) {
            $message = '選手情報を更新しました。';
            $player_id = $edit_id;
            
            // 動画の保存処理
            if (isset($_POST['video_urls']) && is_array($_POST['video_urls'])) {
                // 既存の動画を削除
                $wpdb->delete($tables['player_videos'], array('player_id' => $edit_id), array('%d'));
                
                // 新しい動画を追加
                foreach ($_POST['video_urls'] as $index => $url) {
                    if (!empty($url)) {
                        $wpdb->insert(
                            $tables['player_videos'],
                            array(
                                'player_id' => $edit_id,
                                'video_url' => esc_url_raw($url),
                                'video_title' => sanitize_text_field($_POST['video_titles'][$index]),
                                'video_description' => sanitize_textarea_field($_POST['video_descriptions'][$index]),
                                'display_order' => $index
                            ),
                            array('%d', '%s', '%s', '%s', '%d')
                        );
                    }
                }
            }
        } else {
            $error = '更新に失敗しました。';
        }
    } else {
        // 新規登録
        $inserted = $wpdb->insert(
            $tables['players'],
            $player_data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        if ($inserted) {
            $message = '選手を登録しました。';
            $player_id = $wpdb->insert_id;
            
            // 動画の保存処理
            if (isset($_POST['video_urls']) && is_array($_POST['video_urls'])) {
                foreach ($_POST['video_urls'] as $index => $url) {
                    if (!empty($url)) {
                        $wpdb->insert(
                            $tables['player_videos'],
                            array(
                                'player_id' => $player_id,
                                'video_url' => esc_url_raw($url),
                                'video_title' => sanitize_text_field($_POST['video_titles'][$index]),
                                'video_description' => sanitize_textarea_field($_POST['video_descriptions'][$index]),
                                'display_order' => $index
                            ),
                            array('%d', '%s', '%s', '%s', '%d')
                        );
                    }
                }
            }
        } else {
            $error = '登録に失敗しました。';
        }
    }
    
    if ($message) {
        $action = 'edit';
    }
}

// 一覧表示
if ($action === 'list') {
    // ページネーション
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    // 検索
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $where = '1=1';
    if ($search) {
        $where = $wpdb->prepare(
            '(name LIKE %s OR name_kana LIKE %s)',
            '%' . $wpdb->esc_like($search) . '%',
            '%' . $wpdb->esc_like($search) . '%'
        );
    }
    
    $total = $wpdb->get_var("SELECT COUNT(*) FROM {$tables['players']} WHERE {$where}");
    $players = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$tables['players']} WHERE {$where} ORDER BY player_id DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        )
    );
    $total_pages = ceil($total / $per_page);
    
    include TT_STATS_PLUGIN_DIR . 'admin/views/players-list.php';
    
} elseif ($action === 'edit' || $action === 'add') {
    // 編集・新規追加フォーム
    $player = null;
    $videos = array();
    
    if ($action === 'edit' && $player_id) {
        $player = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tables['players']} WHERE player_id = %d",
            $player_id
        ));
        
        if ($player) {
            $videos = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tables['player_videos']} WHERE player_id = %d ORDER BY display_order ASC",
                $player_id
            ));
        }
    }
    
    include TT_STATS_PLUGIN_DIR . 'admin/views/players-form.php';
}
