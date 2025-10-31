<?php
/**
 * 試合参加者管理
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!$match_id) {
    echo '<p>試合が指定されていません。</p>';
    return;
}

$match = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$tables['matches']} WHERE match_id = %d",
    $match_id
));

if (!$match) {
    echo '<p>試合が見つかりませんでした。</p>';
    return;
}

// 参加者を取得
$participants = $wpdb->get_results($wpdb->prepare(
    "SELECT mp.*, p.name 
     FROM {$tables['match_participants']} mp
     INNER JOIN {$tables['players']} p ON mp.player_id = p.player_id
     WHERE mp.match_id = %d
     ORDER BY mp.final_rank ASC",
    $match_id
));

// 全選手を取得（選択用）
$all_players = $wpdb->get_results(
    "SELECT player_id, name, name_kana FROM {$tables['players']} ORDER BY name ASC"
);
?>

<div class="wrap">
    <h1>参加者管理: <?php echo esc_html($match->match_name); ?></h1>
    
    <?php if ($message): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error); ?></p>
        </div>
    <?php endif; ?>
    
    <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0;">
        <h3 style="margin-top: 0;">試合情報</h3>
        <p><strong>開催日:</strong> <?php echo esc_html($match->match_date); ?></p>
        <?php if ($match->venue): ?>
            <p><strong>会場:</strong> <?php echo esc_html($match->venue); ?></p>
        <?php endif; ?>
    </div>
    
    <div style="background: #e7f3ff; border-left: 4px solid #2271b1; padding: 12px 15px; margin: 20px 0;">
        <strong>順位の入力方法:</strong>
        <ul style="margin: 10px 0 0 20px;">
            <li>1 = 優勝</li>
            <li>2 = 準優勝</li>
            <li>3-4 = ベスト4（同率3位）</li>
            <li>5-8 = ベスト8</li>
            <li>9-16 = ベスト16</li>
            <li>99 = 予選敗退</li>
            <li>空欄 = 順位未確定</li>
        </ul>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('save_participants'); ?>
        <input type="hidden" name="match_id" value="<?php echo $match_id; ?>">
        
        <h2>参加選手と順位</h2>
        
        <table class="wp-list-table widefat fixed striped" id="participants-table">
            <thead>
                <tr>
                    <th style="width: 50px;">No.</th>
                    <th>選手名</th>
                    <th style="width: 150px;">順位</th>
                    <th style="width: 300px;">備考</th>
                    <th style="width: 80px;">操作</th>
                </tr>
            </thead>
            <tbody id="participants-list">
                <?php
                $row_count = !empty($participants) ? count($participants) : 3;
                for ($i = 0; $i < max($row_count, 3); $i++) {
                    $participant = isset($participants[$i]) ? $participants[$i] : null;
                    ?>
                    <tr class="participant-row">
                        <td><?php echo $i + 1; ?></td>
                        <td>
                            <select name="participant_ids[]" class="regular-text" style="width: 100%;">
                                <option value="">選手を選択...</option>
                                <?php foreach ($all_players as $player): ?>
                                    <option value="<?php echo $player->player_id; ?>" 
                                            <?php echo ($participant && $participant->player_id == $player->player_id) ? 'selected' : ''; ?>>
                                        <?php echo esc_html($player->name); ?>
                                        <?php if ($player->name_kana): ?>
                                            (<?php echo esc_html($player->name_kana); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="number" 
                                   name="participant_ranks[]" 
                                   value="<?php echo $participant ? esc_attr($participant->final_rank) : ''; ?>" 
                                   min="1" 
                                   max="999"
                                   placeholder="順位"
                                   style="width: 100%;">
                        </td>
                        <td>
                            <input type="text" 
                                   name="participant_notes[]" 
                                   value="<?php echo $participant ? esc_attr($participant->notes) : ''; ?>" 
                                   placeholder="備考"
                                   style="width: 100%;">
                        </td>
                        <td>
                            <button type="button" class="button remove-participant" onclick="removeParticipant(this)">削除</button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        
        <p style="margin-top: 15px;">
            <button type="button" id="add-participant" class="button">+ 参加者を追加</button>
        </p>
        
        <!-- 保存ボタン -->
        <p class="submit">
            <input type="submit" name="save_participants" class="button button-primary" value="保存">
            <a href="<?php echo admin_url('admin.php?page=tt-stats-results&match_id=' . $match_id); ?>" class="button">対戦結果の登録に進む →</a>
            <a href="<?php echo admin_url('admin.php?page=tt-stats-matches'); ?>" class="button">試合一覧に戻る</a>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    var participantIndex = <?php echo $row_count; ?>;
    var allPlayers = <?php echo json_encode($all_players); ?>;
    
    $('#add-participant').on('click', function() {
        participantIndex++;
        
        var playerOptions = '<option value="">選手を選択...</option>';
        allPlayers.forEach(function(player) {
            var kana = player.name_kana ? ' (' + player.name_kana + ')' : '';
            playerOptions += '<option value="' + player.player_id + '">' + player.name + kana + '</option>';
        });
        
        var html = '<tr class="participant-row">' +
            '<td>' + participantIndex + '</td>' +
            '<td><select name="participant_ids[]" class="regular-text" style="width: 100%;">' + playerOptions + '</select></td>' +
            '<td><input type="number" name="participant_ranks[]" min="1" max="999" placeholder="順位" style="width: 100%;"></td>' +
            '<td><input type="text" name="participant_notes[]" placeholder="備考" style="width: 100%;"></td>' +
            '<td><button type="button" class="button remove-participant" onclick="removeParticipant(this)">削除</button></td>' +
        '</tr>';
        
        $('#participants-list').append(html);
    });
});

function removeParticipant(button) {
    if (confirm('この参加者を削除しますか？')) {
        jQuery(button).closest('.participant-row').remove();
        // 番号を振り直す
        jQuery('#participants-list tr').each(function(index) {
            jQuery(this).find('td:first').text(index + 1);
        });
    }
}
</script>
