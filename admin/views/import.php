<?php
/**
 * 管理画面 - データインポート
 */

if (!defined('ABSPATH')) {
    exit;
}

// ファイルアップロード処理
$message = '';
$error = '';

if (isset($_POST['upload_csv']) && check_admin_referer('tt_stats_upload_csv')) {
    $import_type = sanitize_text_field($_POST['import_type']);
    
    if (empty($_FILES['csv_file']['tmp_name'])) {
        $error = 'ファイルを選択してください。';
    } else {
        $file = $_FILES['csv_file'];
        
        // ファイル検証
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_ext !== 'csv') {
            $error = 'CSVファイルのみアップロード可能です。';
        } elseif ($file['size'] > 10 * 1024 * 1024) { // 10MB制限
            $error = 'ファイルサイズが大きすぎます（最大10MB）。';
        } else {
            // 一時ファイルとして保存
            $upload_dir = wp_upload_dir();
            $temp_file = $upload_dir['basedir'] . '/tt-stats-temp-' . time() . '.csv';
            
            if (move_uploaded_file($file['tmp_name'], $temp_file)) {
                // transientに保存（1時間有効）
                set_transient('tt_stats_import_' . $import_type . '_file', $temp_file, HOUR_IN_SECONDS);
                $message = 'ファイルをアップロードしました。インポートを開始してください。';
            } else {
                $error = 'ファイルのアップロードに失敗しました。';
            }
        }
    }
}
?>

<div class="wrap">
    <h1>📥 データインポート</h1>
    
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
        <h2>📋 CSVフォーマット</h2>
        
        <div style="margin-bottom: 30px;">
            <h3>1. 選手データ (players.csv)</h3>
            <p><strong>列:</strong> name, name_kana, gender, prefecture, tactics, tactics_detail, photo_url, profile_text</p>
            <ul>
                <li><strong>name</strong>: 選手名（必須）</li>
                <li><strong>name_kana</strong>: ふりがな</li>
                <li><strong>gender</strong>: 性別 (male/female/other)（必須）</li>
                <li><strong>prefecture</strong>: 都道府県</li>
                <li><strong>tactics</strong>: 戦術 (right_pen/left_pen/right_shake/left_shake/other)</li>
                <li><strong>tactics_detail</strong>: 戦術詳細</li>
                <li><strong>photo_url</strong>: 写真URL</li>
                <li><strong>profile_text</strong>: プロフィール</li>
            </ul>
            <p><strong>サンプル:</strong></p>
            <code style="display: block; background: #f5f5f5; padding: 10px; margin: 10px 0;">
                山田太郎,やまだたろう,male,東京都,right_shake,攻撃型,https://example.com/photo.jpg,2020年全日本優勝<br>
                佐藤花子,さとうはなこ,female,大阪府,left_pen,カット型,,
            </code>
        </div>
        
        <div style="margin-bottom: 30px;">
            <h3>2. 試合データ (matches.csv)</h3>
            <p><strong>列:</strong> match_name, match_date, venue, match_type, description</p>
            <ul>
                <li><strong>match_name</strong>: 試合名（必須）</li>
                <li><strong>match_date</strong>: 開催日 (YYYY-MM-DD形式)（必須）</li>
                <li><strong>venue</strong>: 会場</li>
                <li><strong>match_type</strong>: 種別 (tournament/league/other)</li>
                <li><strong>description</strong>: 説明</li>
            </ul>
            <p><strong>サンプル:</strong></p>
            <code style="display: block; background: #f5f5f5; padding: 10px; margin: 10px 0;">
                2025年全日本選手権,2025-01-15,東京体育館,tournament,全日本卓球選手権大会<br>
                春季リーグ戦,2025-03-20,大阪アリーナ,league,
            </code>
        </div>
        
        <div style="margin-bottom: 30px;">
            <h3>3. 試合参加者データ (participants.csv)</h3>
            <p><strong>列:</strong> match_name, player_name, final_rank, notes</p>
            <ul>
                <li><strong>match_name</strong>: 試合名（必須・事前登録済みの試合名）</li>
                <li><strong>player_name</strong>: 選手名（必須・事前登録済みの選手名）</li>
                <li><strong>final_rank</strong>: 最終順位（1=優勝, 2=準優勝, 3-4=ベスト4, 99=予選敗退）</li>
                <li><strong>notes</strong>: 備考</li>
            </ul>
            <p><strong>サンプル:</strong></p>
            <code style="display: block; background: #f5f5f5; padding: 10px; margin: 10px 0;">
                2025年全日本選手権,山田太郎,1,<br>
                2025年全日本選手権,佐藤花子,2,<br>
                2025年全日本選手権,鈴木一郎,3,
            </code>
        </div>
        
        <div style="margin-bottom: 30px;">
            <h3>4. 対戦結果データ (results.csv)</h3>
            <p><strong>列:</strong> match_name, round_info, player1_name, player2_name, player1_games, player2_games, notes, result_date</p>
            <ul>
                <li><strong>match_name</strong>: 試合名（必須・事前登録済みの試合名）</li>
                <li><strong>round_info</strong>: 回戦情報（決勝、準決勝など）</li>
                <li><strong>player1_name</strong>: 選手1名（必須・事前登録済みの選手名）</li>
                <li><strong>player2_name</strong>: 選手2名（必須・事前登録済みの選手名）</li>
                <li><strong>player1_games</strong>: 選手1のゲーム数（必須）</li>
                <li><strong>player2_games</strong>: 選手2のゲーム数（必須）</li>
                <li><strong>notes</strong>: 備考</li>
                <li><strong>result_date</strong>: 対戦日時 (YYYY-MM-DD HH:MM:SS形式)</li>
            </ul>
            <p><strong>サンプル:</strong></p>
            <code style="display: block; background: #f5f5f5; padding: 10px; margin: 10px 0;">
                2025年全日本選手権,決勝,山田太郎,佐藤花子,4,2,接戦,2025-01-15 14:00:00<br>
                2025年全日本選手権,準決勝,山田太郎,鈴木一郎,4,1,,
            </code>
        </div>
    </div>
    
    <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0;">
        <h2>⚠️ 注意事項</h2>
        <ul style="line-height: 2;">
            <li>CSVファイルは <strong>UTF-8 (BOM付き)</strong> で保存してください</li>
            <li>Googleスプレッドシートからエクスポートする場合: ファイル → ダウンロード → カンマ区切り形式(.csv)</li>
            <li>データは<strong>新規追加のみ</strong>です（既存データは上書きされません）</li>
            <li>インポート順序: <strong>①選手 → ②試合 → ③参加者 → ④対戦結果</strong></li>
            <li>エラー行はスキップされ、他の行の処理は続行されます</li>
            <li>最大ファイルサイズ: 10MB</li>
        </ul>
    </div>
    
    <!-- 選手インポート -->
    <div class="tt-stats-import-section" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0;">
        <h2>1. 選手データのインポート</h2>
        
        <form method="post" enctype="multipart/form-data" id="form-players">
            <?php wp_nonce_field('tt_stats_upload_csv'); ?>
            <input type="hidden" name="import_type" value="players">
            
            <table class="form-table">
                <tr>
                    <th><label for="csv_file_players">CSVファイル</label></th>
                    <td>
                        <input type="file" id="csv_file_players" name="csv_file" accept=".csv" required>
                        <p class="description">選手データのCSVファイルを選択してください</p>
                    </td>
                </tr>
            </table>
            
            <p>
                <input type="submit" name="upload_csv" class="button" value="ファイルをアップロード" id="upload-players">
                <button type="button" class="button button-primary" id="start-import-players" style="display: none;">インポート開始</button>
            </p>
        </form>
        
        <div id="progress-players" style="display: none;">
            <div style="margin: 20px 0;">
                <div style="background: #f0f0f0; height: 30px; border-radius: 4px; overflow: hidden;">
                    <div id="progress-bar-players" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;"></div>
                </div>
                <p id="progress-text-players" style="margin-top: 10px;"></p>
            </div>
            <div id="progress-log-players" style="max-height: 300px; overflow-y: auto; background: #f9f9f9; padding: 15px; border: 1px solid #ddd; font-family: monospace; font-size: 12px; white-space: pre-wrap;"></div>
        </div>
    </div>
    
    <!-- 試合インポート -->
    <div class="tt-stats-import-section" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0;">
        <h2>2. 試合データのインポート</h2>
        
        <form method="post" enctype="multipart/form-data" id="form-matches">
            <?php wp_nonce_field('tt_stats_upload_csv'); ?>
            <input type="hidden" name="import_type" value="matches">
            
            <table class="form-table">
                <tr>
                    <th><label for="csv_file_matches">CSVファイル</label></th>
                    <td>
                        <input type="file" id="csv_file_matches" name="csv_file" accept=".csv" required>
                        <p class="description">試合データのCSVファイルを選択してください</p>
                    </td>
                </tr>
            </table>
            
            <p>
                <input type="submit" name="upload_csv" class="button" value="ファイルをアップロード" id="upload-matches">
                <button type="button" class="button button-primary" id="start-import-matches" style="display: none;">インポート開始</button>
            </p>
        </form>
        
        <div id="progress-matches" style="display: none;">
            <div style="margin: 20px 0;">
                <div style="background: #f0f0f0; height: 30px; border-radius: 4px; overflow: hidden;">
                    <div id="progress-bar-matches" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;"></div>
                </div>
                <p id="progress-text-matches" style="margin-top: 10px;"></p>
            </div>
            <div id="progress-log-matches" style="max-height: 300px; overflow-y: auto; background: #f9f9f9; padding: 15px; border: 1px solid #ddd; font-family: monospace; font-size: 12px; white-space: pre-wrap;"></div>
        </div>
    </div>
    
    <!-- 参加者インポート -->
    <div class="tt-stats-import-section" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0;">
        <h2>3. 試合参加者データのインポート</h2>
        
        <form method="post" enctype="multipart/form-data" id="form-participants">
            <?php wp_nonce_field('tt_stats_upload_csv'); ?>
            <input type="hidden" name="import_type" value="participants">
            
            <table class="form-table">
                <tr>
                    <th><label for="csv_file_participants">CSVファイル</label></th>
                    <td>
                        <input type="file" id="csv_file_participants" name="csv_file" accept=".csv" required>
                        <p class="description">試合参加者データのCSVファイルを選択してください</p>
                    </td>
                </tr>
            </table>
            
            <p>
                <input type="submit" name="upload_csv" class="button" value="ファイルをアップロード" id="upload-participants">
                <button type="button" class="button button-primary" id="start-import-participants" style="display: none;">インポート開始</button>
            </p>
        </form>
        
        <div id="progress-participants" style="display: none;">
            <div style="margin: 20px 0;">
                <div style="background: #f0f0f0; height: 30px; border-radius: 4px; overflow: hidden;">
                    <div id="progress-bar-participants" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;"></div>
                </div>
                <p id="progress-text-participants" style="margin-top: 10px;"></p>
            </div>
            <div id="progress-log-participants" style="max-height: 300px; overflow-y: auto; background: #f9f9f9; padding: 15px; border: 1px solid #ddd; font-family: monospace; font-size: 12px; white-space: pre-wrap;"></div>
        </div>
    </div>
    
    <!-- 対戦結果インポート -->
    <div class="tt-stats-import-section" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0;">
        <h2>4. 対戦結果データのインポート</h2>
        
        <form method="post" enctype="multipart/form-data" id="form-results">
            <?php wp_nonce_field('tt_stats_upload_csv'); ?>
            <input type="hidden" name="import_type" value="results">
            
            <table class="form-table">
                <tr>
                    <th><label for="csv_file_results">CSVファイル</label></th>
                    <td>
                        <input type="file" id="csv_file_results" name="csv_file" accept=".csv" required>
                        <p class="description">対戦結果データのCSVファイルを選択してください</p>
                    </td>
                </tr>
            </table>
            
            <p>
                <input type="submit" name="upload_csv" class="button" value="ファイルをアップロード" id="upload-results">
                <button type="button" class="button button-primary" id="start-import-results" style="display: none;">インポート開始</button>
            </p>
        </form>
        
        <div id="progress-results" style="display: none;">
            <div style="margin: 20px 0;">
                <div style="background: #f0f0f0; height: 30px; border-radius: 4px; overflow: hidden;">
                    <div id="progress-bar-results" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;"></div>
                </div>
                <p id="progress-text-results" style="margin-top: 10px;"></p>
            </div>
            <div id="progress-log-results" style="max-height: 300px; overflow-y: auto; background: #f9f9f9; padding: 15px; border: 1px solid #ddd; font-family: monospace; font-size: 12px; white-space: pre-wrap;"></div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // インポート処理の汎用関数
    function startImport(type) {
        const progressDiv = $('#progress-' + type);
        const progressBar = $('#progress-bar-' + type);
        const progressText = $('#progress-text-' + type);
        const progressLog = $('#progress-log-' + type);
        const startButton = $('#start-import-' + type);
        
        progressDiv.show();
        startButton.prop('disabled', true);
        progressLog.html('インポートを開始しています...\n');
        
        function processImport(offset) {
            $.post(ajaxurl, {
                action: 'tt_stats_import_' + type,
                nonce: ttStatsAdmin.nonce,
                offset: offset
            }, function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // 進捗バーを更新
                    progressBar.css('width', data.progress + '%');
                    progressBar.text(data.progress + '%');
                    
                    // 進捗テキストを更新
                    progressText.html(
                        '処理中: ' + Math.min(data.offset, data.total) + ' / ' + data.total + ' 件<br>' +
                        '成功: <span style="color: green;">' + data.success_count + '</span> | ' +
                        'エラー: <span style="color: red;">' + data.error_count + '</span>'
                    );
                    
                    // エラーログを追加
                    if (data.errors.length > 0) {
                        data.errors.forEach(function(error) {
                            progressLog.append('<span style="color: red;">✗ ' + error + '</span>\n');
                        });
                        progressLog.scrollTop(progressLog[0].scrollHeight);
                    }
                    
                    // 完了チェック
                    if (data.is_complete) {
                        progressLog.append('\n<span style="color: green; font-weight: bold;">✓ インポート完了！</span>\n');
                        progressLog.append('成功: ' + data.success_count + '件 / エラー: ' + data.error_count + '件\n');
                        startButton.prop('disabled', false);
                        
                        // 5秒後にページをリロード
                        setTimeout(function() {
                            location.reload();
                        }, 5000);
                    } else {
                        // 次のバッチを処理
                        processImport(data.offset);
                    }
                } else {
                    progressLog.append('<span style="color: red;">エラー: ' + response.data + '</span>\n');
                    startButton.prop('disabled', false);
                }
            }).fail(function() {
                progressLog.append('<span style="color: red;">通信エラーが発生しました</span>\n');
                startButton.prop('disabled', false);
            });
        }
        
        // 最初のバッチを開始
        processImport(0);
    }
    
    // 各インポートボタンのイベント
    $('#start-import-players').on('click', function() {
        if (confirm('選手データのインポートを開始しますか？')) {
            startImport('players');
        }
    });
    
    $('#start-import-matches').on('click', function() {
        if (confirm('試合データのインポートを開始しますか？')) {
            startImport('matches');
        }
    });
    
    $('#start-import-participants').on('click', function() {
        if (confirm('試合参加者データのインポートを開始しますか？')) {
            startImport('participants');
        }
    });
    
    $('#start-import-results').on('click', function() {
        if (confirm('対戦結果データのインポートを開始しますか？')) {
            startImport('results');
        }
    });
    
    // アップロード後にインポートボタンを表示
    <?php if ($message && !empty($_POST['import_type'])): ?>
        $('#start-import-<?php echo sanitize_text_field($_POST['import_type']); ?>').show();
    <?php endif; ?>
});
</script>
