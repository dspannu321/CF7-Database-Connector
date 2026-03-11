/**
 * FormBridge admin scripts.
 *
 * @package FormBridge
 */

(function () {
    'use strict';

    // Toggle payload row visibility on Logs page.
    document.querySelectorAll('.formbridge-toggle-payload').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var logId = this.getAttribute('data-log-id');
            var row = document.getElementById('formbridge-payload-' + logId);
            if (!row) return;
            row.hidden = !row.hidden;
            var isExpanded = !row.hidden;
            this.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
            this.textContent = isExpanded ? 'Hide payload' : 'View payload';
        });
    });

    // Test connection from form (Connections page) — uses current form values, no save.
    if (typeof window.formbridgeAdmin !== 'undefined') {
        var testBtn = document.getElementById('formbridge-test-connection-btn');
        var form = document.getElementById('formbridge-connection-form');
        var resultEl = document.getElementById('formbridge-test-result');
        if (testBtn && form && resultEl) {
            testBtn.addEventListener('click', function () {
                var idInput = document.getElementById('formbridge-connection-id');
                var id = idInput ? parseInt(idInput.value, 10) || 0 : 0;
                var formData = new FormData();
                formData.append('action', 'formbridge_test_connection_draft');
                formData.append('_wpnonce', window.formbridgeAdmin.testConnectionNonce);
                formData.append('id', String(id));
                formData.append('db_host', form.querySelector('[name="db_host"]').value.trim());
                formData.append('db_port', form.querySelector('[name="db_port"]').value.trim() || '3306');
                formData.append('db_name', form.querySelector('[name="db_name"]').value.trim());
                formData.append('db_user', form.querySelector('[name="db_user"]').value.trim());
                formData.append('db_pass', form.querySelector('[name="db_pass"]').value);

                resultEl.hidden = true;
                resultEl.className = 'formbridge-test-result';
                resultEl.textContent = '';
                testBtn.disabled = true;
                testBtn.textContent = 'Testing…';

                var xhr = new XMLHttpRequest();
                xhr.open('POST', window.formbridgeAdmin.ajaxUrl);
                xhr.onload = function () {
                    testBtn.disabled = false;
                    testBtn.textContent = 'Test connection';
                    var data;
                    try {
                        data = JSON.parse(xhr.responseText);
                    } catch (e) {
                        resultEl.hidden = false;
                        resultEl.classList.add('formbridge-test-result-error');
                        resultEl.textContent = 'Invalid response from server.';
                        return;
                    }
                    resultEl.hidden = false;
                    if (data.success && data.data && data.data.message) {
                        resultEl.classList.add('formbridge-test-result-success');
                        resultEl.textContent = data.data.message;
                    } else {
                        resultEl.classList.add('formbridge-test-result-error');
                        resultEl.textContent = (data.data && data.data.message) ? data.data.message : 'Connection failed.';
                    }
                };
                xhr.onerror = function () {
                    testBtn.disabled = false;
                    testBtn.textContent = 'Test connection';
                    resultEl.hidden = false;
                    resultEl.classList.add('formbridge-test-result-error');
                    resultEl.textContent = 'Network error. Please try again.';
                };
                xhr.send(formData);
            });
        }
    }
})();
