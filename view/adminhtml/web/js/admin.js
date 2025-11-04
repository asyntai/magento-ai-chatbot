/**
 * Asyntai Chatbot Admin JavaScript
 *
 * @category  Asyntai
 * @package   Asyntai_Chatbot
 * @author    Asyntai <hello@asyntai.com>
 * @copyright Copyright (c) Asyntai
 * @license   MIT License
 */

define([
    'jquery'
], function ($) {
    'use strict';

    var currentState = null;
    var config = {};

    function showAlert(msg, ok) {
        var el = $('#asyntai-alert');
        if (!el.length) return;
        el.show();
        el.removeClass('alert-success alert-error');
        el.addClass(ok ? 'alert-success' : 'alert-error');
        el.text(msg);
    }

    function generateState() {
        return 'magento_' + Math.random().toString(36).substr(2, 9);
    }

    function updateFallbackLink() {
        var fallbackLink = $('#asyntai-fallback-link');
        if (fallbackLink.length && currentState) {
            fallbackLink.attr('href', 'https://asyntai.com/wp-auth?platform=magento&state=' + encodeURIComponent(currentState));
        }
    }

    function openPopup() {
        currentState = generateState();
        updateFallbackLink();
        var base = 'https://asyntai.com/wp-auth?platform=magento';
        var url = base + (base.indexOf('?') > -1 ? '&' : '?') + 'state=' + encodeURIComponent(currentState);
        var w = 800, h = 720;
        var y = window.top.outerHeight / 2 + window.top.screenY - (h / 2);
        var x = window.top.outerWidth / 2 + window.top.screenX - (w / 2);
        var pop = window.open(url, 'asyntai_connect', 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=' + w + ',height=' + h + ',top=' + y + ',left=' + x);

        // Check if popup was blocked after a short delay
        setTimeout(function () {
            if (!pop || pop.closed || typeof pop.closed == 'undefined') {
                showAlert('Popup blocked. Please allow popups or use the link below.', false);
                return;
            }
            pollForConnection(currentState);
        }, 100);
    }

    function pollForConnection(state) {
        var attempts = 0;
        function check() {
            if (attempts++ > 60) return;
            var script = document.createElement('script');
            var cb = 'asyntai_cb_' + Date.now();
            script.src = 'https://asyntai.com/connect-status.js?state=' + encodeURIComponent(state) + '&cb=' + cb;
            window[cb] = function (data) {
                try { delete window[cb]; } catch (e) { }
                if (data && data.site_id) {
                    saveConnection(data);
                    return;
                }
                setTimeout(check, 500);
            };
            script.onerror = function () {
                setTimeout(check, 1000);
            };
            document.head.appendChild(script);
        }
        setTimeout(check, 800);
    }

    function saveConnection(data) {
        showAlert('Asyntai connected. Saving…', true);
        var payload = {
            site_id: data.site_id || '',
            form_key: window.FORM_KEY || ''
        };
        if (data.script_url) payload.script_url = data.script_url;
        if (data.account_email) payload.account_email = data.account_email;

        var saveUrl = config.saveUrl;

        $.ajax({
            url: saveUrl,
            type: 'POST',
            data: JSON.stringify(payload),
            contentType: 'application/json',
            dataType: 'json',
            success: function (json) {
                if (!json || !json.success) {
                    var errorMsg = 'Save failed';
                    if (json && json.message) {
                        errorMsg = json.message;
                    } else if (json && json.error) {
                        errorMsg = typeof json.error === 'string' ? json.error : JSON.stringify(json.error);
                    }
                    showAlert('Could not save settings: ' + errorMsg, false);
                    return;
                }
                showAlert('Asyntai connected. Chatbot enabled on all pages.', true);

                // Update status
                var statusHtml = 'Status: <span style="color:#28a745;font-weight:600;">Connected</span>';
                if (payload.account_email) {
                    statusHtml += ' as ' + $('<div>').text(payload.account_email).html();
                }
                statusHtml += ' <button type="button" id="asyntai-reset" class="action-default" style="margin-left:12px;">Reset</button>';
                $('#asyntai-status').html(statusHtml);

                // Show connected box
                var connectedBox = $('#asyntai-connected-box');
                if (connectedBox.length) {
                    connectedBox.show();
                    if (!connectedBox.html().trim()) {
                        connectedBox.html('<div style="padding:32px;border:1px solid #ddd;border-radius:8px;background:#fff;text-align:center;">' +
                            '<h2>Asyntai is now enabled</h2>' +
                            '<p style="font-size:16px;color:#666;">Set up your AI chatbot, review chat logs and more:</p>' +
                            '<a class="action-primary" href="https://asyntai.com/dashboard" target="_blank" rel="noopener">Open Asyntai Panel</a>' +
                            '<p style="margin:20px 0 0;color:#666;"><strong>Tip:</strong> If you want to change how the AI answers, please <a href="https://asyntai.com/dashboard#setup" target="_blank" rel="noopener" style="color:#2563eb;text-decoration:underline;">go here</a>.</p>' +
                            '</div>');
                    }
                }

                // Hide popup wrap
                $('#asyntai-popup-wrap').hide();
            },
            error: function (xhr) {
                var errorMsg = 'Could not save settings';
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response && response.error) {
                        errorMsg += ': ' + response.error;
                    }
                } catch (e) {
                    errorMsg += ': HTTP ' + xhr.status;
                }
                showAlert(errorMsg, false);
            }
        });
    }

    function resetConnection() {
        if (!confirm('Are you sure you want to reset the Asyntai connection?')) {
            return;
        }

        var resetUrl = config.resetUrl;

        $.ajax({
            url: resetUrl,
            type: 'POST',
            data: JSON.stringify({ action: 'reset' }),
            contentType: 'application/json',
            dataType: 'json',
            success: function (json) {
                if (json && json.success) {
                    window.location.reload();
                } else {
                    showAlert('Reset failed: ' + (json && json.error || 'Unknown error'), false);
                }
            },
            error: function (xhr) {
                showAlert('Reset failed: HTTP ' + xhr.status, false);
            }
        });
    }

    return {
        init: function (options) {
            config = options || {};

            // Initialize fallback link on page load
            currentState = generateState();
            updateFallbackLink();

            // Event handlers
            $(document).on('click', '#asyntai-connect-btn', function (e) {
                e.preventDefault();
                openPopup();
            });

            $(document).on('click', '#asyntai-reset', function (e) {
                e.preventDefault();
                resetConnection();
            });

            $(document).on('click', '#asyntai-fallback-link', function (e) {
                // Re-generate state and update link when clicked
                currentState = generateState();
                updateFallbackLink();
                // Let the link work normally (target="_blank")
                // Also start polling for this state
                setTimeout(function () {
                    pollForConnection(currentState);
                }, 1000);
            });
        }
    };
});
