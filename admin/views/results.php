<?php
/**
 * 管理画面 - 対戦結果管理
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$db_manager = new TT_Stats_DB_Manager();
$tables = $db_manager->get_table_names();

// アクション処理
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$result_id = isset($_GET['result_id']) ? intval($_GET['result_id']) : 0;
$match_id = isset($_GET['match_id']) ? intval($_GET['match_id']) : 0;
$message = '';
$error = '';

// 削除処理
if ($action === 'delete' && $result_id && check_admin_referer('delete_result_' . $result_id)) {
    $deleted = $wpdb->delete($tables['match_results'], array('result_id' => $result_id), array('%d'));
    if ($deleted) {
        $message = '対戦結果を削除しました。';
    } else {
        $error = '削除に失敗しました。';
    }
    $action = 'list';
}

// 保存処理
if (isset($_POST['save_result']) && check_admin_referer('save_result')) {
    $winner_id = null;
    $player1_games = intval($_POST['player1_games']);
    $player2_games = intval($_POST['player2_games']);
    
    // 勝者を判定
    if ($player1_games > $player2_games) {
        $winner_id = intval($_POST['player1_id']);
    } elseif ($player2_games > $player1_games) {
        $winner_id = intval($_POST['player2_id']);
    }
    
    $result_data = array(
        'match_id' => intval($_POST['match_id']),
        'round_info' => sanitize_text_field($_POST['round_info']),
        'player1_id' => intval($_POST['player1_id']),
        'player2_id' => intval($_POST['player2_id']),
        'player1_games' => $player1_games,
        'player2_games' => $player2_games,
        'winner_id' => $winner_id,
        'notes' => sanitize_textarea_field($_POST['notes']),
        'result_date' => !empty($_POST['result_date']) ? sanitize_text_field($_POST['result_date']) : null
    );
    
    $edit_id = isset($_POST['result_id']) ? intval($_POST['result_id']) : 0;
    
    if ($edit_id > 0) {
        // 更新
        $updated = $wpdb->update(
            $tables['match_results'],
            $result_data,
            array('result_id' => $edit_id),
            array('%d', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s'),
            array('%d')
        );
        if ($updated !== false) {
            $message = '対戦結果を更新しました。';
            $result_id = $edit_id;
        } else {
            $error = '更新に失敗しました。';
        }
    } else {
        // 新規登録
        $inserted = $wpdb->insert(
            $tables['match_results'],
            $result_data,
            array('%d', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s')
        );
        if ($inserted) {
            $message = '対戦結果を登録しました。';
            $result_id = $wpdb->insert_id;
            $match_id = $result_data['match_id'];
        } else {
            $error = '登録に失敗しました。';
        }
    }
    
    if ($message) {
        $action = 'list';
    }
}

// 一覧表示
if ($action === 'list') {
    include TT_STATS_PLUGIN_DIR . 'admin/views/results-list.php';
    
} elseif ($action === 'edit' || $action === 'add') {
    // 編集・新規追加フォーム
    include TT_STATS_PLUGIN_DIR . 'admin/views/results-form.php';
}
