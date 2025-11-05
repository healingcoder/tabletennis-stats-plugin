<?php
/**
 * 管理画面 - バックアップ＆復元
 */

if (!defined('ABSPATH')) {
    exit;
}

$backup_manager = new TT_Stats_Backup_Manager();
$backup_files = $backup_manager->get_backup_files();

// 古いバックアップの削除処理
if (isset($_POST['cleanup_backups']) && check_admin_referer('tt_stats_cleanup_backups')) {
    $deleted = $backup_manager->cleanup_old_backups(30);
    echo '<div class="notice notice-success is-dismissible"><p>' . $deleted . '件の古いバックアップファイルを削除しました。</p></div>';
    $backup_files = $backup_manager->get_backup_files();
}
?>

<div class="wrap">
    <h1>💾 バックアップ＆復元</h1>
    
    <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0;">
        <h2>⚠️ 重要な注意事項</h2>
        <ul style="line-height: 2; color: #d63638;">
            <li><strong>復元を実行すると、現在のすべてのデータが削除されます</strong></li>
            <li>復元前に必ずバックアップを作成してください</li>
            <li>復元中はブラウザを閉じないでください</li>
            <li>大量データの場合、処理に時間がかかることがあります</li>
        </ul>
    </div>
    
    <!-- バックアップ作成 -->
    <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0;">
        <h2>📦 バックアップ作成</h2>
        <p>以下の5つのテーブルをCSVファイルにエクスポートし、ZIPファイルとしてダウンロードします：</p>
        <ul style="line-height: 2;">
            <li>✅ 選手データ (players.csv)</li>
            <li>✅ 選手動画データ (player_videos.csv)</li>
            <li>✅ 試合データ (matches.csv)</li>
            <li>✅ 試合参加者データ (match_participants.csv)</li>
            <li>✅ 対戦結果データ (match_results.csv)</li>
        </ul>
        
        <p style="margin-top: 20px;">
            <button type="button" class="button button-primary button-large" id="create-backup-btn" style="background: #2271b1;">
                📥 バックアップを作成してダウンロード
            </button>
        </p>
        
        <div id="backup-progress" style="display: none; margin-top: 20px;">
            <div style="background: #f0f0f0; height: 30px; border-radius: 4px; overflow: hidden; margin-bottom: 10px;">
                <div id="backup-progress-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;"></div>
            </div>
            <p id="backup-progress-text"></p>
        </div>
        
        <div id="backup-result" style="display: none; margin-top: 20px;"></div>
    </div>
    
    <!-- バックアップ復元 -->
    <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0;">
        <h2>📂 バックアップ復元</h2>
        
        <div style="background: #fff3cd; border: 2px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <strong>⚠️ 警告：</strong> 復元を実行すると、<span style="color: #d63638; font-weight: bold;">現在のすべてのデータが削除</span>され、バックアップファイルのデータに置き換えられます。
        </div>
        
        <form id="restore-form" enctype="multipart/form-data">
            <?php wp_nonce_field('tt_stats_admin_nonce', 'nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="backup_file">バックアップファイル</label></th>
                    <td>
                        <input type="file" id="backup_file" name="backup_file" accept=".zip" required>
                        <p class="description">バックアップしたZIPファイルを選択してください（tt-stats-backup-XXXXXXXX.zip）</p>
                    </td>
                </tr>
            </table>
            
            <p style="margin-top: 20px;">
                <button type="submit" class="button button-primary button-large" style="background: #d63638; border-color: #d63638;">
                    ⚠️ データを復元する（全データ削除）
                </button>
            </p>
        </form>
        
        <div id="restore-progress" style="display: none; margin-top: 20px;">
            <div style="background: #f0f0f0; height: 30px; border-radius: 4px; overflow: hidden; margin-bottom: 10px;">
                <div id="restore-progress-bar" style="background: #d63638; height: 100%; width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;"></div>
            </div>
            <p id="restore-progress-text">復元中...</p>
        </div>
        
        <div id="restore-result" style="display: none; margin-top: 20px;"></div>
    </div>
    
    <!-- バックアップファイル一覧 -->
    <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0;">
        <h2>📋 バックアップファイル一覧</h2>
        
        <?php if (!empty($backup_files)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ファイル名</th>
                        <th>サイズ</th>
                        <th>作成日時</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backup_files as $file): ?>
                        <tr>
                            <td><?php echo esc_html($file['filename']); ?></td>
                            <td><?php echo size_format($file['size']); ?></td>
                            <td><?php echo date('Y-m-d H:i:s', $file['date']); ?></td>
                            <td>
                                <a href="<?php echo esc_url($file['url']); ?>" class="button button-small" download>
                                    📥 ダウンロード
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <form method="post" style="margin-top: 20px;">
                <?php wp_nonce_field('tt_stats_cleanup_backups'); ?>
                <p>
                    <button type="submit" name="cleanup_backups" class="button" onclick="return confirm('30日以上前のバックアップファイルを削除します。よろしいですか？');">
                        🗑️ 古いバックアップを削除（30日以上前）
                    </button>
                </p>
            </form>
        <?php else: ?>
            <p style="color: #999; padding: 40px; text-align: center;">バックアップファイルがありません</p>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // バックアップ作成
    $('#create-backup-btn').on('click', function() {
        if (!confirm('バックアップを作成しますか？')) {
            return;
        }
        
        const btn = $(this);
        const progressDiv = $('#backup-progress');
        const progressBar = $('#backup-progress-bar');
        const progressText = $('#backup-progress-text');
        const resultDiv = $('#backup-result');
        
        btn.prop('disabled', true);
        progressDiv.show();
        resultDiv.hide();
        
        progressBar.css('width', '30%').text('30%');
        progressText.text('バックアップを作成中...');
        
        $.post(ajaxurl, {
            action: 'tt_stats_create_backup',
            nonce: '<?php echo wp_create_nonce('tt_stats_admin_nonce'); ?>'
        }, function(response) {
            progressBar.css('width', '100%').text('100%');
            
            if (response.success) {
                progressText.text('バックアップが完成しました！');
                
                setTimeout(function() {
                    resultDiv.html(
                        '<div class="notice notice-success" style="padding: 15px;">' +
                        '<p><strong>✓ バックアップが作成されました</strong></p>' +
                        '<p>ファイル名: ' + response.data.filename + '</p>' +
                        '<p><a href="' + response.data.download_url + '" class="button button-primary" download>📥 ダウンロード</a></p>' +
                        '</div>'
                    ).show();
                    
                    progressDiv.hide();
                    btn.prop('disabled', false);
                    
                    // 自動ダウンロード
                    window.location.href = response.data.download_url;
                    
                    // 3秒後にページをリロード
                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                }, 500);
            } else {
                resultDiv.html(
                    '<div class="notice notice-error" style="padding: 15px;">' +
                    '<p><strong>✗ エラー</strong></p>' +
                    '<p>' + response.data + '</p>' +
                    '</div>'
                ).show();
                
                progressDiv.hide();
                btn.prop('disabled', false);
            }
        }).fail(function() {
            resultDiv.html(
                '<div class="notice notice-error" style="padding: 15px;">' +
                '<p><strong>✗ 通信エラー</strong></p>' +
                '<p>バックアップの作成に失敗しました</p>' +
                '</div>'
            ).show();
            
            progressDiv.hide();
            btn.prop('disabled', false);
        });
    });
    
    // バックアップ復元
    $('#restore-form').on('submit', function(e) {
        e.preventDefault();
        
        if (!confirm('⚠️ 警告：現在のすべてのデータが削除され、バックアップファイルのデータに置き換えられます。\n\n本当に復元しますか？')) {
            return;
        }
        
        if (!confirm('最終確認：本当に実行しますか？この操作は取り消せません。')) {
            return;
        }
        
        const formData = new FormData(this);
        formData.append('action', 'tt_stats_restore_backup');
        
        const progressDiv = $('#restore-progress');
        const progressBar = $('#restore-progress-bar');
        const progressText = $('#restore-progress-text');
        const resultDiv = $('#restore-result');
        const submitBtn = $(this).find('button[type="submit"]');
        
        submitBtn.prop('disabled', true);
        progressDiv.show();
        resultDiv.hide();
        
        progressBar.css('width', '0%').text('0%');
        progressText.text('復元中... ブラウザを閉じないでください');
        
        // プログレスバーのアニメーション
        let progress = 0;
        const progressInterval = setInterval(function() {
            progress += 2;
            if (progress <= 90) {
                progressBar.css('width', progress + '%').text(progress + '%');
            }
        }, 100);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                clearInterval(progressInterval);
                progressBar.css('width', '100%').text('100%');
                
                if (response.success) {
                    progressText.text('復元が完了しました！');
                    
                    let detailsHtml = '';
                    if (response.data.details) {
                        detailsHtml = '<ul style="margin-top: 10px;">';
                        for (let key in response.data.details) {
                            detailsHtml += '<li>' + key + ': ' + response.data.details[key] + '件</li>';
                        }
                        detailsHtml += '</ul>';
                    }
                    
                    resultDiv.html(
                        '<div class="notice notice-success" style="padding: 15px;">' +
                        '<p><strong>✓ ' + response.data.message + '</strong></p>' +
                        detailsHtml +
                        '</div>'
                    ).show();
                    
                    // 3秒後にページをリロード
                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                } else {
                    progressText.text('復元に失敗しました');
                    
                    resultDiv.html(
                        '<div class="notice notice-error" style="padding: 15px;">' +
                        '<p><strong>✗ エラー</strong></p>' +
                        '<p>' + response.data + '</p>' +
                        '</div>'
                    ).show();
                    
                    submitBtn.prop('disabled', false);
                }
            },
            error: function() {
                clearInterval(progressInterval);
                
                resultDiv.html(
                    '<div class="notice notice-error" style="padding: 15px;">' +
                    '<p><strong>✗ 通信エラー</strong></p>' +
                    '<p>復元に失敗しました</p>' +
                    '</div>'
                ).show();
                
                progressDiv.hide();
                submitBtn.prop('disabled', false);
            }
        });
    });
});
</script>

<style>
.button-large {
    font-size: 16px !important;
    height: auto !important;
    padding: 12px 24px !important;
}
</style>
