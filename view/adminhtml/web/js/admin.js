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

    var config = {};
    var busy = false;

    /** How long to keep asking Asyntai whether the merchant has signed in.
     *  150 tries two seconds apart is five minutes, which is what a merchant
     *  who has to create an account first actually needs. */
    var MAX_POLLS = 150;
    var POLL_DELAY = 2000;

    function showAlert(msg, ok) {
        var el = $('#asyntai-alert');
        if (!el.length) return;
        el.show();
        el.removeClass('alert-success alert-error');
        el.addClass(ok ? 'alert-success' : 'alert-error');
        el.text(msg);
    }

    function connectUrl(state) {
        return 'https://asyntai.com/wp-auth/?platform=magento&state=' + encodeURIComponent(state);
    }

    /**
     * Open the window during the click, before anything asynchronous, so the
     * browser does not treat it as an unrequested popup. Its address is set
     * once the server has handed back a state.
     */
    function openBlankWindow() {
        var w = 800, h = 720;
        var y = window.top.outerHeight / 2 + window.top.screenY - (h / 2);
        var x = window.top.outerWidth / 2 + window.top.screenX - (w / 2);
        return window.open('about:blank', 'asyntai_connect',
            'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width='
            + w + ',height=' + h + ',top=' + y + ',left=' + x);
    }

    /**
     * Ask the store to make a state and, where the store allows it, a
     * read-only catalogue token parked at Asyntai under that state.
     */
    function startConnect() {
        if (busy) return;
        busy = true;

        var pop = openBlankWindow();

        $.ajax({
            url: config.prepareUrl,
            type: 'POST',
            data: {
                form_key: config.formKey,
                include_orders: $('#asyntai-include-orders').is(':checked') ? 1 : 0
            },
            dataType: 'json'
        }).done(function (json) {
            busy = false;

            if (!json || !json.success || !json.state) {
                if (pop) pop.close();
                showAlert('Could not start the connection. Please try again.', false);
                return;
            }

            $('#asyntai-fallback-link').attr('href', connectUrl(json.state));

            if (json.message) {
                showAlert(json.message, json.catalog === 'staged');
            }

            if (!pop || pop.closed) {
                showAlert('Popup blocked. Please allow popups, or use the link below.', false);
                return;
            }

            pop.location.href = connectUrl(json.state);
            pollForConnection(json.state, 0);
        }).fail(function () {
            busy = false;
            if (pop) pop.close();
            showAlert('Could not start the connection. Please try again.', false);
        });
    }

    /**
     * Ask our own controller, which asks Asyntai. The browser never loads a
     * script from outside the store.
     */
    function pollForConnection(state, attempt) {
        if (attempt >= MAX_POLLS) {
            showAlert('Still waiting for the sign-in to finish. '
                + 'Reload this page once you are done in the other window.', false);
            return;
        }

        $.ajax({
            url: config.pollUrl,
            type: 'GET',
            data: { state: state },
            dataType: 'json'
        }).done(function (json) {
            if (json && json.ready && json.data && json.data.site_id) {
                saveConnection(json.data);
                return;
            }
            setTimeout(function () { pollForConnection(state, attempt + 1); }, POLL_DELAY);
        }).fail(function () {
            setTimeout(function () { pollForConnection(state, attempt + 1); }, POLL_DELAY);
        });
    }

    function saveConnection(data) {
        showAlert('Asyntai connected. Saving…', true);
        var payload = {
            site_id: data.site_id || '',
            form_key: window.FORM_KEY || ''
        };
        if (data.script_url) payload.script_url = data.script_url;
        if (data.account_email) payload.account_email = data.account_email;

        $.ajax({
            url: config.saveUrl,
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

        $.ajax({
            url: config.resetUrl,
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

            $(document).on('click', '#asyntai-connect-btn', function (e) {
                e.preventDefault();
                startConnect();
            });

            $(document).on('click', '#asyntai-reset', function (e) {
                e.preventDefault();
                resetConnection();
            });

            // The link is a way out when the window is blocked. Until a state
            // exists it has nothing to point at, so the click runs the same
            // flow instead of following a dead address.
            $(document).on('click', '#asyntai-fallback-link', function (e) {
                if (!$(this).attr('href')) {
                    e.preventDefault();
                    startConnect();
                }
            });
        }
    };
});
