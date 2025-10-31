<?php
/**
 * 管理画面 - 試合管理
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$db_manager = new TT_Stats_DB_Manager();
$tables = $db_manager->get_table_names();

// アクション処理
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$match_id = isset($_GET['match_id']) ? intval($_GET['match_id']) : 0;
$message = '';
$error = '';

// 削除処理
if ($action === 'delete' && $match_id && check_admin_referer('delete_match_' . $match_id)) {
    $deleted = $wpdb->delete($tables['matches'], array('match_id' => $match_id), array('%d'));
    if ($deleted) {
        $message = '試合を削除しました。';
    } else {
        $error = '削除に失敗しました。';
    }
    $action = 'list';
}

// 保存処理
if (isset($_POST['save_match']) && check_admin_referer('save_match')) {
    $match_data = array(
        'match_name' => sanitize_text_field($_POST['match_name']),
        'match_date' => sanitize_text_field($_POST['match_date']),
        'venue' => sanitize_text_field($_POST['venue']),
        'match_type' => sanitize_text_field($_POST['match_type']),
        'description' => sanitize_textarea_field($_POST['description'])
    );
    
    $edit_id = isset($_POST['match_id']) ? intval($_POST['match_id']) : 0;
    
    if ($edit_id > 0) {
        // 更新
        $updated = $wpdb->update(
            $tables['matches'],
            $match_data,
            array('match_id' => $edit_id),
            array('%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
        if ($updated !== false) {
            $message = '試合情報を更新しました。';
            $match_id = $edit_id;
        } else {
            $error = '更新に失敗しました。';
        }
    } else {
        // 新規登録
        $inserted = $wpdb->insert(
            $tables['matches'],
            $match_data,
            array('%s', '%s', '%s', '%s', '%s')
        );
        if ($inserted) {
            $message = '試合を登録しました。';
            $match_id = $wpdb->insert_id;
        } else {
            $error = '登録に失敗しました。';
        }
    }
    
    if ($message) {
        $action = 'edit';
    }
}

// 参加者保存処理
if (isset($_POST['save_participants']) && check_admin_referer('save_participants')) {
    $edit_id = intval($_POST['match_id']);
    
    // 既存の参加者を削除
    $wpdb->delete($tables['match_participants'], array('match_id' => $edit_id), array('%d'));
    
    // 新しい参加者を追加
    if (isset($_POST['participant_ids']) && is_array($_POST['participant_ids'])) {
        foreach ($_POST['participant_ids'] as $index => $player_id) {
            if (!empty($player_id)) {
                $wpdb->insert(
                    $tables['match_participants'],
                    array(
                        'match_id' => $edit_id,
                        'player_id' => intval($player_id),
                        'final_rank' => isset($_POST['participant_ranks'][$index]) ? intval($_POST['participant_ranks'][$index]) : null,
                        'notes' => isset($_POST['participant_notes'][$index]) ? sanitize_textarea_field($_POST['participant_notes'][$index]) : ''
                    ),
                    array('%d', '%d', '%d', '%s')
                );
            }
        }
    }
    
    $message = '参加者情報を保存しました。';
    $match_id = $edit_id;
    $action = 'participants';
}

// 一覧表示
if ($action === 'list') {
    include TT_STATS_PLUGIN_DIR . 'admin/views/matches-list.php';
    
} elseif ($action === 'edit' || $action === 'add') {
    // 編集・新規追加フォーム
    include TT_STATS_PLUGIN_DIR . 'admin/views/matches-form.php';
    
} elseif ($action === 'participants') {
    // 参加者管理
    include TT_STATS_PLUGIN_DIR . 'admin/views/matches-participants.php';
}
