@extends('admin.layout')

@section('content')
    @include('admin.accounting.partials.topmenu')
    @include('admin.accounting.partials.pending-withdrawals-filter')
    @include('admin.accounting.partials.description-modal')
    @include('admin.accounting.partials.cancel-withdrawal-modal')
    @include('admin.accounting.partials.confirmation-modal')

    {{ loadCssFile("/phive/admin/customization/styles/css/pending-withdrawals.css") }}

    <div class="card card-primary">
        <div class="card-header">
            <ul class="list-inline card-title">
                <li><b>Pending Withdrawals</b></li>
            </ul>
            @if(!empty($paginator['data']) && p('accounting.section.pending-withdrawals.download.csv'))
                <a class="pending-withdrawal-csv-download-btn" href="javascript:void(0)" class="mr-2 float-right" data-export="1"><i class="fa fa-download"></i> Download</a>
            @endif
        </div>
        <div class="card-body">
            {{--
                TODO: This logic needs to be removed once we refactor the code on the server side (ajax.php).
                This will enable users to retry the pay pending action after reloading the page if the pending withdrawal failed in the last attempt.
            --}}
            <?php $_SESSION['paid_pending'] = array(); ?>

            <div class='table-responsive'>
                <table id="pending-withdrawals-table" class="table table-bordered dt-responsive w-100 border-collapse">
                    <thead>
                        <tr>
                            <th>Row Class</th>
                            <th>Actions</th>

                            @if(p('accounting.section.pending-withdrawals.aml-flags'))
                                <th>AML Flag(s)</th>
                            @endif

                            <th>Date</th>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Method</th>
                            <th>Details</th>
                            <th>Currency</th>
                            <th>Tot. Dep. Sum</th>
                            <th>Amount</th>
                            <th>Fee</th>
                            <th>Deducted</th>
                            <th>External ID</th>
                            <th>Internal ID</th>
                            <th>Country</th>
                            <th>Province</th>
                            <th>MTS ID</th>
                            <th>User Email</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($paginator['data'] as $element)
                            <tr>
                                <td>{{ $element->rowClass }}</td>
                                <td>{!! $element->actions !!}</td>

                                @if(p('accounting.section.pending-withdrawals.aml-flags'))
                                    <td>{!! $element->amlFlags !!}</td>
                                @endif

                                <td>{{ $element->date }}</td>
                                <td>{{ $element->user_id  }}</td>
                                <td>{{ $element->username}}</td>
                                <td>{!! $element->description !!}</td>
                                <td>{!! $element->status !!}</td>
                                <td>{{ $element->method }}</td>
                                <td>{{ $element->details }}</td>
                                <td>{{ $element->currency }}</td>
                                <td>{{ $element->totalDepositSum }}</td>
                                <td>{{ $element->amount }}</td>
                                <td>{{ $element->fee }}</td>
                                <td>{{ $element->deducted }}</td>
                                <td>{{ $element->ext_id }}</td>
                                <td>{{ $element->id }}</td>
                                <td>{{ $element->country }}</td>
                                <td>{{ $element->province }}</td>
                                <td>{{ $element->mts_id }}</td>
                                <td>{{ $element->user_email}}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script>
        const currentLoggedInUserId = <?php echo uid(); ?>;
        const isQPend = <?php echo phive()->getSetting('q-pend') ? 'true' : 'false'; ?>;

        doWs('<?php echo phive('UserHandler')->wsUrl('pendingwithdrawals', false) ?>', function(e) {
            const { id, msg, performedBy, action } = JSON.parse(e.data);

            const actionMap = {
                'approve': { idPrefix: 'pay-btn-', handler: handlePayActionResponse, defaultMessage: 'Pay' },
                'cancel': { idPrefix: 'cancel-btn-', handler: handleCancelActionResponse, defaultMessage: 'Cancel' }
            };

            const actionInfo = actionMap[action];
            if (!actionInfo) {
                console.error(`Invalid action: ${action}`);
                return;
            }

            const { idPrefix, handler, defaultMessage } = actionInfo;
            const currentElement = document.getElementById(`${idPrefix}${id}`);

            const notifyRes = (parseInt(performedBy) === currentLoggedInUserId);

            handler(currentElement, msg ?? defaultMessage, notifyRes);
            currentElement.disabled = true;
        });

        function payPending(currentElement, pID, amount, user_id, email) {
            const elementOriginalText = currentElement.innerHTML;
            setupAction(currentElement, { pID, amount, elementOriginalText}, 'pay', function (res) {
                handlePayActionResponse(currentElement, res, !isQPend);
            });
        }

        function handlePayActionResponse(currentElement, res, notifyRes) {
            if(notifyRes) {
                const isResponseFailed = res.includes('failed');
                const message = res === 'fail' ? 'The transaction failed.' : res;

                displayNotifyMessage(isResponseFailed ? 'danger' : 'success', message);
            }

            currentElement.innerHTML = res;
        }

        function unverifyPlayer(currentElement, pID, user_id, email) {
            const elementOriginalText = currentElement.innerHTML;
            setupAction(currentElement, { pID, elementOriginalText}, 'unverify', function (res) {
                displayNotifyMessage('success', res);
                currentElement.innerHTML = elementOriginalText;
            });
        }

        function verificationEmail(currentElement, userId, email, alreadySent) {
            const elementOriginalText = currentElement.innerHTML;
            setupAction(currentElement, { pID: userId, elementOriginalText}, 'verify', function(me, res){
                displayNotifyMessage('success', `Verify mail sent to #${userId}`);
                currentElement.innerHTML = elementOriginalText;
            });
        }

        function cancelPending(method, send_mail) {
            const pendingId = document.getElementById('pendingIdForCancel').value;
            const elementOriginalText = 'Cancel';
            var mainElement = document.getElementById(`cancel-btn-${pendingId}`);

            setupAction(mainElement, { pID: pendingId, elementOriginalText, send_mail}, method, function (res) {
                handleCancelActionResponse(mainElement, res, true);
            });
        }

        function handleCancelActionResponse(currentElement, res, notifyRes) {
            if (notifyRes) {
                if(res === "fail") {
                    displayNotifyMessage('danger', 'The entry could not be deleted.');
                } else if(res === 'ok') {
                    displayNotifyMessage('success', 'The entry was deleted.');
                } else {
                    displayNotifyMessage(res.includes('fail') ? 'danger' : 'success', res);
                }
            }

            currentElement.innerHTML = res;
        }

        function setupAction(currentElement, additionalParams, action, actionCallback) {
            const { elementOriginalText, pID: id, amount, send_mail } = additionalParams;

            currentElement.innerHTML = `${elementOriginalText} <i class="fa fa-spinner fa-spin"></i>`;
            currentElement.disabled = true;

            $.post('/phive/modules/Micro/ajax.php', {
                table: 'pending_withdrawals',
                id,
                action,
                ...(send_mail && { send_mail }),
                ...(amount && { amount: amount * 100 }),
            }, actionCallback);
        }

        function hideColumns(api, columnsToHide) {
            const totalColumns = api.columns().count();

            columnsToHide.forEach(function (columnIndex) {
                if (columnIndex >= 0 && columnIndex < totalColumns) {
                    api.column(columnIndex).visible(false);
                } else {
                    console.warn(`Skipping invalid column index: ${columnIndex}`);
                }
            });
        }

        /**
         * Converts a string of HTML attributes into a JavaScript object.
         *
         * @param {string} attributesString - A string containing HTML attributes.
         * @returns {Object} An object representing key-value pairs of attributes.
         *
         * @example
         * // Example Input:
         * const attributesString = "class='stuck-tr-line' onmouseover='show()' onmouseout='hide()' title='Status: stuck'";
         *
         * // Example Output:
         * // {
         * //     "class": "stuck-tr-line",
         * //     "onmouseover": "show()",
         * //     "onmouseout": "hide()",
         * //     "title": "Status: stuck"
         * // }
         */
        function stringToAttributes(attributesString) {
            const regex = /(\w+)='([^']*)'/g;
            const attributes = {};
            let match;
            while ((match = regex.exec(attributesString)) !== null) attributes[match[1]] = match[2];
            return attributes;
        }

        /**
         * Function to confirm an action with a modal prompt.
         * @param {string} originalFunction - The name of the original function.
         * @param {any[]} originalFunctionArgs - The original function arguments passed to the method.
         * @param {Object} extraArgsObject - Extra arguments passed as an object.
         * @returns {boolean} - True if the user confirms, otherwise false.
         */
        function confirmationModal(originalFunction, originalFunctionArgs) {
            const actionMessages = {
                'verificationEmail': `<strong>${originalFunctionArgs.alreadySent ? 'Resend' : 'Send'} verification email </strong>to:<br><br><strong>User:</strong> ${originalFunctionArgs.userId} (${originalFunctionArgs.email})`,
                'payPending': `<strong>Pay:</strong><br><br><strong>Transaction:</strong> ${originalFunctionArgs.pID}<br><strong>User:</strong> ${originalFunctionArgs.user_id} (${originalFunctionArgs.email})`,
                'cancelModal': `<strong>Cancel:</strong><br><br><strong>Transaction:</strong> ${originalFunctionArgs.pID}<br><strong>User:</strong> ${originalFunctionArgs.user_id} (${originalFunctionArgs.email})`,
                'unverifyPlayer': `<strong>Unverify:</strong><br><br><strong>User:</strong> ${originalFunctionArgs.user_id} (${originalFunctionArgs.email})`
            };

            const actionMessage = actionMessages[originalFunction.name] || `perform this action?`;
            const confirmationContent = `Are you sure you want to ${actionMessage}`;
            return showConfirmationModal(confirmationContent);
        }

        function setTextColorBasedOnBg(element) {
            const bgColor = window.getComputedStyle(element).backgroundColor;

            let textColor = 'black';

            if (bgColor && bgColor !== 'rgba(0, 0, 0, 0)' && bgColor !== 'transparent') {
                const rgb = bgColor.match(/\d+/g).map(Number);
                const luminance = (0.299 * rgb[0] + 0.587 * rgb[1] + 0.114 * rgb[2]) / 255;
                textColor = luminance > 0.5 ? 'black' : 'white';
            }

            element.style.color = textColor;

            const links = element.querySelectorAll('a');
            links.forEach(link => {
                link.style.color = textColor;
            });
        }

        $(function () {
            // Adding confirmation popup on the top of actions
            const funcsToAddConfirmation = ['payPending', 'verificationEmail', 'cancelModal', 'unverifyPlayer'];
            interceptor(funcsToAddConfirmation, confirmationModal);

            $('.pending-withdrawal-csv-download-btn').on('click', function (e) {
                e.preventDefault();
                var form = $('#filter-form-pending-withdrawals');
                var input_export = $('<input>', { type: 'hidden', name: 'export', value: '1' });

                if ($(this).data('export') === 1 || $(this).data('export') === 2) {
                    form.append(input_export);
                }

                form.submit();
            });

            // Initialise the pending withdrawals data-table
            const usernameUrl = "{{ \App\Helpers\URLHelper::generateUserProfileLink($app) }}";

            var columnsToHide = [0, 20];
            var tableColumns = [
                {
                    data: "rowClass",
                    orderable: false
                },
                {
                    data: "actions",
                    orderable: false,
                    render: function (data, type, row) {
                        const {id, amount, user_id, email: userEmail} = row;
                        const email = userEmail ?? row.user_email;
                        const isPending = row.status === 'pending';
                        const notAllowedIcon = `<span title="You are not allowed to perform any action">&#9888;&#65039;</span>`;

                        function generateButton(text, onClick = '', style = 'primary', id = '', applyStyle = '') {
                            return `<button id="${id}" class="btn btn-xs btn-${style}" style="${applyStyle}" onclick="${onClick}">${text}</button>`;
                        }

                        let html = '';
                        const parsedData = JSON.parse(data);

                        if (isPending && parsedData.permissions.payButton) {
                            html += generateButton('Pay', `payPending(this, ${id}, ${amount}, ${user_id}, '${email}')`, 'success', `pay-btn-${id}`, 'white-space:normal;');
                        }

                        if (isPending && parsedData.permissions.unverifyButton && parsedData.verifiedStatus === 'verified') {
                            html += generateButton('Unverify Player', `unverifyPlayer(this, ${id}, ${user_id}, '${email}')`, 'primary');
                        }

                        if (isPending && parsedData.permissions.verificationEmailButton) {
                            const alreadySent = (parsedData.verifiedStatus === 'verified' || parsedData.mailSent);
                            const verificationEmailBtnText = alreadySent ? 'Resend Email' : 'Send Email';
                            html += generateButton(verificationEmailBtnText, `verificationEmail(this, ${user_id}, '${email}', ${alreadySent})`, 'warning');
                        }

                        if (parsedData.permissions.canceledButton) {
                            html += generateButton('Cancel', `cancelModal(this, ${id}, ${user_id}, '${email}')`, 'danger', `cancel-btn-${id}`, 'white-space:normal;');
                        }

                        const buttonsContainer = '<div class="action-buttons-container">' + html + '</div>';

                        return html ? buttonsContainer : notAllowedIcon;
                    }
                },
                {
                    data: "aml_flags",
                    orderable: false,
                    render: function (data, type, row) {
                        return data || row.amlFlags;
                    }
                },
                { data: "date" },
                {
                    data: "user_id",
                    render: function(data) {
                        return `<a target="_blank" href="${usernameUrl}${data}/">${data}</a>`;
                    }
                },
                {
                    data: "username",
                    orderable: false,
                    render: function(data, type, row) {
                        const username = data || row.username;
                        return `<a target="_blank" href="${usernameUrl}${username}/">${username}</a>`;
                    }
                },
                {
                    data: "description",
                    orderable: false,
                    render: function(data, type, row) {
                        return `<span class="info-icon glyphicon glyphicon-info-sign" onclick="descriptionModal('${row.id}', '${row.description}')"></span>`;
                    }
                },
                { data: "status" },
                { data: "method" },
                {
                    data: "details",
                    orderable: false
                },
                { data: "currency" },
                {
                    data: "totalDepositSum",
                    orderable: false
                },
                { data: "amount" },
                { data: "fee" },
                { data: "deducted" },
                { data: "ext_id" },
                { data: "id" },
                { data: "country" },
                { data: "province" },
                { data: "mts_id" },
                {
                    data: "email",
                    orderable: false
                },
            ];

            if ('<?php echo !p('accounting.section.pending-withdrawals.aml-flags') ?>') {
                tableColumns = tableColumns.filter(function(item) {
                    return item.data !== "aml_flags";
                });
            }

            const table = $("#pending-withdrawals-table").DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ $app['url_generator']->generate('accounting-pending-withdrawals', ['user' => $user->id]) }}",
                    type: "POST",
                    data: function (d) {
                        d.form = $('#filter-form-pending-withdrawals').serializeArray();
                    }
                },
                columns: tableColumns,
                columnDefs: [
                    {"className": "center-content", "targets": "_all"},
                ],
                initComplete: function (settings, json) {
                    var api = this.api();
                    setTimeout(function () {
                        hideColumns(api, columnsToHide);
                    }, 10);
                },
                drawCallback: function (settings) {
                    var api = this.api();
                    hideColumns(api, columnsToHide);

                    api.rows().every(function (rowIdx, tableLoop, rowLoop) {
                        const rowNode = this.node();
                        setTextColorBasedOnBg(rowNode);
                    });
                },
                createdRow: function (row, data, dataIndex) {
                    var shouldAddStuckStatuses = <?php echo (p('accounting.section.pending-withdrawals.stuck-statuses')) ? 'true' : 'false'; ?>;

                    if (shouldAddStuckStatuses) {
                        const attributes = stringToAttributes(data.rowClass);

                        Object.entries(attributes).forEach(([key, value]) => {
                            if (key === 'class') {
                                row.classList.add(value);
                            } else if (key === 'style') {
                                row.setAttribute('style', (row.getAttribute('style') || '') + ' ' + value);
                            } else {
                                row.setAttribute(key, value);
                            }
                        });
                    }

                    setTextColorBasedOnBg(row);
                },
                searching: false,
                responsive: false,
                order: [[tableColumns.findIndex(col => col.data === "date"), 'desc']],

                language: {
                    emptyTable: "No results found.",
                    lengthMenu: "Display _MENU_ records per page"
                },
                deferLoading: parseInt("{{ $paginator['recordsTotal'] }}"),
                pageLength: 25
            });

            $('#pending-withdrawals-table thead th').each(function() {
                const columnIndex = table.column(this).index();
                if (!table.settings()[0].aoColumns[columnIndex].bSortable) {
                    $(this).css({
                        'padding-right': '8px',
                        'min-width': '72px'
                    });
                }
            });
        });
    </script>
@endsection
