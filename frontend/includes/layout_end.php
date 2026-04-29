
<div class="modal fade" id="globalMessageModal" tabindex="-1" aria-labelledby="globalMessageModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="globalMessageModalTitle">Success</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="globalMessageModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="globalMessageModalBtn" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<div class="overlay" id="overlay"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= LEGACY_BASE_URL ?>/public/js/main.js?v=<?= filemtime(LEGACY_BASE_PATH . '/public/js/main.js') ?>"></script>






</body>
</html>

