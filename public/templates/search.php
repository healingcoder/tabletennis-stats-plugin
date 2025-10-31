<?php
/**
 * 検索ページテンプレート
 * ショートコード: [tt_stats_search]
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="tt-stats-container tt-stats-search">
    <h2>🔍 選手・試合検索</h2>
    
    <div class="tt-stats-search-tabs">
        <button class="tt-stats-tab-button active" data-tab="tab-player-search">選手検索</button>
        <button class="tt-stats-tab-button" data-tab="tab-match-search">試合検索</button>
        <button class="tt-stats-tab-button" data-tab="tab-vs-search">対戦検索</button>
    </div>
    
    <!-- 選手検索タブ -->
    <div id="tab-player-search" class="tt-stats-tab-content">
        <form id="tt-stats-player-search-form" class="tt-stats-search-form">
            <div class="tt-stats-form-group">
                <label for="player-name">選手名</label>
                <input type="text" id="player-name" name="player-name" placeholder="選手名またはふりがな">
            </div>
            
            <div class="tt-stats-form-group">
                <label for="player-gender">性別</label>
                <select id="player-gender" name="player-gender">
                    <option value="">すべて</option>
                    <option value="male">男性</option>
                    <option value="female">女性</option>
                    <option value="other">その他</option>
                </select>
            </div>
            
            <div class="tt-stats-form-group">
                <label for="player-prefecture">出身</label>
                <select id="player-prefecture" name="player-prefecture">
                    <option value="">すべて</option>
                    <option value="北海道">北海道</option>
                    <option value="青森県">青森県</option>
                    <option value="岩手県">岩手県</option>
                    <option value="宮城県">宮城県</option>
                    <option value="秋田県">秋田県</option>
                    <option value="山形県">山形県</option>
                    <option value="福島県">福島県</option>
                    <option value="茨城県">茨城県</option>
                    <option value="栃木県">栃木県</option>
                    <option value="群馬県">群馬県</option>
                    <option value="埼玉県">埼玉県</option>
                    <option value="千葉県">千葉県</option>
                    <option value="東京都">東京都</option>
                    <option value="神奈川県">神奈川県</option>
                    <option value="新潟県">新潟県</option>
                    <option value="富山県">富山県</option>
                    <option value="石川県">石川県</option>
                    <option value="福井県">福井県</option>
                    <option value="山梨県">山梨県</option>
                    <option value="長野県">長野県</option>
                    <option value="岐阜県">岐阜県</option>
                    <option value="静岡県">静岡県</option>
                    <option value="愛知県">愛知県</option>
                    <option value="三重県">三重県</option>
                    <option value="滋賀県">滋賀県</option>
                    <option value="京都府">京都府</option>
                    <option value="大阪府">大阪府</option>
                    <option value="兵庫県">兵庫県</option>
                    <option value="奈良県">奈良県</option>
                    <option value="和歌山県">和歌山県</option>
                    <option value="鳥取県">鳥取県</option>
                    <option value="島根県">島根県</option>
                    <option value="岡山県">岡山県</option>
                    <option value="広島県">広島県</option>
                    <option value="山口県">山口県</option>
                    <option value="徳島県">徳島県</option>
                    <option value="香川県">香川県</option>
                    <option value="愛媛県">愛媛県</option>
                    <option value="高知県">高知県</option>
                    <option value="福岡県">福岡県</option>
                    <option value="佐賀県">佐賀県</option>
                    <option value="長崎県">長崎県</option>
                    <option value="熊本県">熊本県</option>
                    <option value="大分県">大分県</option>
                    <option value="宮崎県">宮崎県</option>
                    <option value="鹿児島県">鹿児島県</option>
                    <option value="沖縄県">沖縄県</option>
                </select>
            </div>
            
            <div class="tt-stats-form-group">
                <label for="player-tactics">戦術</label>
                <select id="player-tactics" name="player-tactics">
                    <option value="">すべて</option>
                    <option value="right_pen">右ペン</option>
                    <option value="left_pen">左ペン</option>
                    <option value="right_shake">右シェーク</option>
                    <option value="left_shake">左シェーク</option>
                    <option value="other">その他</option>
                </select>
            </div>
            
            <button type="submit" class="tt-stats-search-button">検索</button>
        </form>
    </div>
    
    <!-- 試合検索タブ -->
    <div id="tab-match-search" class="tt-stats-tab-content" style="display: none;">
        <form id="tt-stats-match-search-form" class="tt-stats-search-form">
            <div class="tt-stats-form-group">
                <label for="match-name">試合名</label>
                <input type="text" id="match-name" name="match-name" placeholder="試合名を入力">
            </div>
            
            <div class="tt-stats-form-group">
                <label for="match-date-from">開催日（開始）</label>
                <input type="date" id="match-date-from" name="match-date-from">
            </div>
            
            <div class="tt-stats-form-group">
                <label for="match-date-to">開催日（終了）</label>
                <input type="date" id="match-date-to" name="match-date-to">
            </div>
            
            <button type="submit" class="tt-stats-search-button">検索</button>
        </form>
    </div>
    
    <!-- 対戦検索タブ -->
    <div id="tab-vs-search" class="tt-stats-tab-content" style="display: none;">
        <form id="tt-stats-vs-search-form" class="tt-stats-search-form">
            <div class="tt-stats-form-group">
                <label for="vs-player1">選手1</label>
                <input type="text" id="vs-player1" name="vs-player1" placeholder="選手名を入力" required>
            </div>
            
            <div class="tt-stats-form-group">
                <label for="vs-player2">選手2</label>
                <input type="text" id="vs-player2" name="vs-player2" placeholder="選手名を入力" required>
            </div>
            
            <p style="color: #666; font-size: 14px; margin-top: -10px;">
                ※2名の選手名を入力すると、直接対決の結果を検索できます
            </p>
            
            <button type="submit" class="tt-stats-search-button">検索</button>
        </form>
    </div>
    
    <!-- 検索結果 -->
    <div id="tt-stats-search-results"></div>
</div>
