/**
 * フロントエンド用JavaScript
 */

(function($) {
    'use strict';
    
    // 検索タブの切り替え
    $('.tt-stats-tab-button').on('click', function() {
        const target = $(this).data('tab');
        
        $('.tt-stats-tab-button').removeClass('active');
        $(this).addClass('active');
        
        $('.tt-stats-tab-content').hide();
        $(`#${target}`).show();
    });
    
    // 選手検索
    $('#tt-stats-player-search-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'tt_stats_search_players',
            nonce: ttStatsPublic.nonce,
            name: $('#player-name').val(),
            gender: $('#player-gender').val(),
            prefecture: $('#player-prefecture').val(),
            tactics: $('#player-tactics').val()
        };
        
        $('#tt-stats-search-results').html('<div class="tt-stats-loading">検索中...</div>');
        
        $.post(ttStatsPublic.ajaxUrl, formData, function(response) {
            if (response.success) {
                displayPlayerResults(response.data);
            } else {
                $('#tt-stats-search-results').html('<p>エラーが発生しました。</p>');
            }
        });
    });
    
    // 試合検索
    $('#tt-stats-match-search-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'tt_stats_search_matches',
            nonce: ttStatsPublic.nonce,
            match_name: $('#match-name').val(),
            date_from: $('#match-date-from').val(),
            date_to: $('#match-date-to').val()
        };
        
        $('#tt-stats-search-results').html('<div class="tt-stats-loading">検索中...</div>');
        
        $.post(ttStatsPublic.ajaxUrl, formData, function(response) {
            if (response.success) {
                displayMatchResults(response.data);
            } else {
                $('#tt-stats-search-results').html('<p>エラーが発生しました。</p>');
            }
        });
    });
    
    // 対戦検索
    $('#tt-stats-vs-search-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'tt_stats_search_vs',
            nonce: ttStatsPublic.nonce,
            player1_name: $('#vs-player1').val(),
            player2_name: $('#vs-player2').val()
        };
        
        $('#tt-stats-search-results').html('<div class="tt-stats-loading">検索中...</div>');
        
        $.post(ttStatsPublic.ajaxUrl, formData, function(response) {
            if (response.success) {
                displayVsResults(response.data);
            } else {
                $('#tt-stats-search-results').html('<p>エラーが発生しました。</p>');
            }
        });
    });
    
    // 選手検索結果を表示
    function displayPlayerResults(players) {
        if (players.length === 0) {
            $('#tt-stats-search-results').html('<p>検索結果が見つかりませんでした。</p>');
            return;
        }
        
        let html = '<div class="tt-stats-results">';
        players.forEach(function(player) {
            const tacticsLabel = getTacticsLabel(player.tactics);
            html += `
                <div class="tt-stats-result-item">
                    <h3><a href="?tt_stats_type=player&tt_stats_id=${player.player_id}">${escapeHtml(player.name)}</a></h3>
                    <p>ふりがな: ${escapeHtml(player.name_kana || '')}</p>
                    <p>性別: ${getGenderLabel(player.gender)}</p>
                    <p>出身: ${escapeHtml(player.prefecture || '')}</p>
                    <p>戦術: ${tacticsLabel}</p>
                </div>
            `;
        });
        html += '</div>';
        
        $('#tt-stats-search-results').html(html);
    }
    
    // 試合検索結果を表示
    function displayMatchResults(matches) {
        if (matches.length === 0) {
            $('#tt-stats-search-results').html('<p>検索結果が見つかりませんでした。</p>');
            return;
        }
        
        let html = '<div class="tt-stats-results">';
        matches.forEach(function(match) {
            html += `
                <div class="tt-stats-result-item">
                    <h3><a href="?tt_stats_type=match&tt_stats_id=${match.match_id}">${escapeHtml(match.match_name)}</a></h3>
                    <p>開催日: ${match.match_date}</p>
                    <p>会場: ${escapeHtml(match.venue || '')}</p>
                </div>
            `;
        });
        html += '</div>';
        
        $('#tt-stats-search-results').html(html);
    }
    
    // 対戦検索結果を表示
    function displayVsResults(results) {
        if (results.length === 0) {
            $('#tt-stats-search-results').html('<p>対戦結果が見つかりませんでした。</p>');
            return;
        }
        
        let html = '<div class="tt-stats-results">';
        results.forEach(function(result) {
            const score = `${result.player1_games}-${result.player2_games}`;
            html += `
                <div class="tt-stats-result-item">
                    <h3>${escapeHtml(result.match_name)}</h3>
                    <p>${escapeHtml(result.round_info || '')}</p>
                    <p><strong>${escapeHtml(result.player1_name)}</strong> vs <strong>${escapeHtml(result.player2_name)}</strong></p>
                    <p>スコア: ${score}</p>
                    <p>日時: ${result.match_date}</p>
                </div>
            `;
        });
        html += '</div>';
        
        $('#tt-stats-search-results').html(html);
    }
    
    // ヘルパー関数
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function getGenderLabel(gender) {
        const labels = {
            'male': '男性',
            'female': '女性',
            'other': 'その他'
        };
        return labels[gender] || gender;
    }
    
    function getTacticsLabel(tactics) {
        const labels = {
            'right_pen': '右ペン',
            'left_pen': '左ペン',
            'right_shake': '右シェーク',
            'left_shake': '左シェーク',
            'other': 'その他'
        };
        return labels[tactics] || tactics || '';
    }
    
})(jQuery);
