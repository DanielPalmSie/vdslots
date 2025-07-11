<div class="modal fade" id="confirmationModal"  role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h5 class="modal-title"><strong>Action Confirmation:</strong></h5>
            </div>
            <div id="confirmationModalContent" class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelButton" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmButton">Confirm</button>
            </div>
        </div>
    </div>
</div>

@section('footer-javascript')
@parent
<script>
    /**
     * Displays a confirmation modal with the provided content.
     * @param {string} confirmationContent - The content to be displayed in the confirmation modal.
     * @returns {Promise<boolean>} A promise that resolves to a boolean indicating whether the confirmation was confirmed.
     */
    function showConfirmationModal(confirmationContent) {
        return new Promise(resolve => {
            $('#confirmationModalContent').html(confirmationContent);
            $('#confirmationModal').modal('show');

            const handleConfirm = () => {
                $('#confirmationModal').modal('hide');
                resolve(true);
            };

            const handleCancel = () => {
                $('#confirmationModal').modal('hide');
                resolve(false);
            };

            const cleanup = () => {
                $('#confirmButton').off('click', handleConfirm);
                $('#cancelButton').off('click', handleCancel);
            };

            $('#confirmationModal').on('hidden.bs.modal', cleanup);

            $('#confirmButton').on('click', handleConfirm);
            $('#cancelButton').on('click', handleCancel);
        });
    }

    /**
     * Function to intercept specified functions and execute a pre-trigger callback before invoking the original functions.
     * @param {string[]} functions - Array of function names to intercept.
     * @param {Function} preTriggerCallback - Callback function to execute before invoking the original functions.
     * @param {boolean} [forceExecuteOriginal=false] - Whether to force execute the original function even if preTriggerCallback returns false.
     * @param {Object} [extraArgsObject={}] - Extra arguments to pass to the preTriggerCallback as an object.
     */
    function interceptor(functions, preTriggerCallback, forceExecuteOriginal = false, extraArgsObject = {}) {
        functions.forEach(func => {
            const originalFunction = window[func];
            window[func] = async (...args) => {
                const argNames = originalFunction.toString().match(/\(([^)]*)\)/)[1].split(', ').map(arg => arg.trim());
                const originalFunctionArgs = Object.fromEntries(argNames.map((arg, i) => [arg, args[i]]));
                if (await preTriggerCallback(originalFunction, originalFunctionArgs, extraArgsObject) || forceExecuteOriginal) {
                    return originalFunction.apply(this, args);
                }
            };
        });
    }
</script>
@endsection
