{{-- Dialog bersama untuk meminta konfirmasi sebelum formulir transaksi dikirim. --}}
<div id="transaction-confirmation-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-gray-950/60 p-4 backdrop-blur-sm" aria-labelledby="transaction-confirmation-title" aria-describedby="transaction-confirmation-description" aria-hidden="true">
    <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-700 dark:bg-gray-800" role="dialog" aria-modal="true">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3l-8.5-14.1a2 2 0 0 0-3.4 0Z"/>
            </svg>
        </div>
        <h2 id="transaction-confirmation-title" class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Konfirmasi transaksi</h2>
        <p id="transaction-confirmation-description" class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">Pastikan barang sudah diperiksa atau disiapkan. Setelah dikonfirmasi, stok produk akan langsung diperbarui.</p>
        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button type="button" data-transaction-confirm-cancel class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-gray-700">Periksa kembali</button>
            <button type="button" data-transaction-confirm-approve class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">Ya, konfirmasi</button>
        </div>
    </div>
</div>

<script>
    (() => {
        const modal = document.getElementById('transaction-confirmation-modal');
        const cancelButton = modal.querySelector('[data-transaction-confirm-cancel]');
        const approveButton = modal.querySelector('[data-transaction-confirm-approve]');
        let pendingForm = null;
        let triggeringButton = null;

        function closeConfirmation() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.setAttribute('aria-hidden', 'true');
            pendingForm = null;

            if (triggeringButton) {
                triggeringButton.focus();
                triggeringButton = null;
            }
        }

        document.querySelectorAll('[data-transaction-confirmation]').forEach((button) => {
            button.addEventListener('click', () => {
                pendingForm = button.closest('form');
                triggeringButton = button;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.setAttribute('aria-hidden', 'false');
                cancelButton.focus();
            });
        });

        cancelButton.addEventListener('click', closeConfirmation);
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeConfirmation();
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeConfirmation();
            }
        });
        approveButton.addEventListener('click', () => {
            if (pendingForm) {
                pendingForm.requestSubmit();
            }
        });
    })();
</script>
