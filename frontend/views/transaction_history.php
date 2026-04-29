<?php
require_once __DIR__ . '/../includes/bootstrap.php';
include LEGACY_BASE_PATH . '/includes/auth_check.php';
include LEGACY_BASE_PATH . '/includes/layout_start.php';
include LEGACY_BASE_PATH . '/includes/functions.php';
$can_refund = (($_SESSION['role'] ?? '') === 'admin');
?>

<div class="container-fluid dashboard-page-content mt-5 pt-3">
    <h2 class="mb-4">Transaction History</h2>
    <input type="hidden" id="transactionCanRefund" value="<?= $can_refund ? '1' : '0' ?>">

    <div class="card mb-4">
        <div class="card-header">
            Filter Transactions
        </div>
        <div class="card-body">
            <form id="transactionFilterForm" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="transaction_start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="transaction_start_date" name="start_date" required>
                </div>
                <div class="col-md-3">
                    <label for="transaction_end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="transaction_end_date" name="end_date" required>
                </div>
                <div class="col-md-3">
                    <label for="transaction_records_per_page" class="form-label">Records per page</label>
                    <select class="form-select" id="transaction_records_per_page" name="records_per_page">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="30">30</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Recorded Transactions
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="transactionHistoryTable">
                    <thead>
                        <tr>
                            <th>Date &amp; Time</th>
                            <th>Transaction ID / Receipt No.</th>
                            <th>Cashier / User</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="transactionHistoryTableBody">
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Loading transactions...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <nav id="transactionPaginationNav" aria-label="Transaction Pagination" class="mt-4"></nav>
        </div>
    </div>
</div>
</div>
</div>

<div class="modal fade" id="transactionDetailsModal" tabindex="-1" aria-labelledby="transactionDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transactionDetailsModalLabel">Transaction Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div><strong>Receipt No.:</strong> <span id="transactionDetailReceiptNo">-</span></div>
                        <div><strong>Date &amp; Time:</strong> <span id="transactionDetailDateTime">-</span></div>
                        <div><strong>Cashier:</strong> <span id="transactionDetailCashier">-</span></div>
                    </div>
                    <div class="col-md-6">
                        <div><strong>Status:</strong> <span id="transactionDetailStatus">-</span></div>
                        <div><strong>Payment Method:</strong> <span id="transactionDetailPaymentMethod">-</span></div>
                        <div><strong>Cash Received:</strong> <span id="transactionDetailCashReceived">-</span></div>
                        <div id="transactionRefundDateWrapper" class="d-none"><strong>Refund Date:</strong> <span id="transactionDetailRefundDate">-</span></div>
                    </div>
                </div>

                <div id="transactionRefundReasonWrapper" class="alert alert-warning d-none">
                    <strong>Refund Reason:</strong> <span id="transactionDetailRefundReason">-</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="transactionDetailItemsBody">
                            <tr>
                                <td colspan="4" class="text-center text-muted">Loading transaction items...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="row justify-content-end">
                    <div class="col-md-5">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Discount</span>
                            <strong id="transactionDetailDiscount">-</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax</span>
                            <strong id="transactionDetailTax">-</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Change Due</span>
                            <strong id="transactionDetailChangeDue">-</strong>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-2">
                            <span>Total Amount</span>
                            <strong id="transactionDetailTotal">-</strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <?php if ($can_refund): ?>
                    <button type="button" class="btn btn-danger d-none me-auto" id="openRefundTransactionBtn">
                        <i class="fas fa-undo"></i> Refund
                    </button>
                <?php endif; ?>
                <button type="button" class="btn btn-outline-secondary" id="printTransactionReceiptBtn">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php if ($can_refund): ?>
    <div class="modal fade" id="refundTransactionModal" tabindex="-1" aria-labelledby="refundTransactionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="refundTransactionForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="refundTransactionModalLabel">Refund Transaction</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="refund_transaction_sale_id" name="sale_id">
                        <div class="mb-3">
                            <label for="refund_transaction_receipt_no" class="form-label">Receipt No.</label>
                            <input type="text" id="refund_transaction_receipt_no" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="refund_reason" class="form-label">Reason for Refund</label>
                            <textarea id="refund_reason" name="reason" class="form-control" rows="3" placeholder="Enter refund reason..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Confirm Refund</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
include LEGACY_BASE_PATH . '/includes/layout_end.php';
?>
<script src="<?= LEGACY_BASE_URL ?>/public/js/transaction_history.js?v=<?= filemtime(LEGACY_BASE_PATH . '/public/js/transaction_history.js') ?>"></script>
</body>
</html>
