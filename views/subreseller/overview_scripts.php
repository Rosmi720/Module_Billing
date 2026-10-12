<script id="scripts">
(function() {
    $(function() {
        if ($.fn.dataTable) {
            $.fn.dataTable.ext.errMode = 'none';
        }

        if ($.fn.DataTable && $('#subRecentOrdersTable').length) {
            $('#subRecentOrdersTable').DataTable({
                responsive: true,
                order: [[6, 'desc']], // Sorted by Date column
                pageLength: 10,
                language: {
                    emptyTable: '<div class="d-flex flex-column align-items-center justify-content-center text-center py-4"><div class="avatar avatar-xl bg-label-secondary rounded-circle mb-3 d-flex align-items-center justify-content-center shadow-sm"><i class="ti tabler-inbox fs-1 text-secondary"></i></div><h5 class="fw-bold mb-1 text-dark">No Point Purchases Yet</h5><p class="text-muted mb-3" style="max-width: 480px;">You haven\'t made any point purchases yet. Request points from your parent distributor.</p><a href="billing_buy" class="btn btn-primary waves-effect waves-light"><i class="ti tabler-plus me-1"></i> Buy Points Now</a></div>'
                },
                layout: {
                    topStart: {
                        pageLength: {},
                        buttons: [
                            { extend: 'copy', className: 'btn btn-sm btn-label-secondary me-1', text: '<i class="ti tabler-copy me-1"></i>Copy' },
                            { extend: 'csv', className: 'btn btn-sm btn-label-primary me-1', text: '<i class="ti tabler-file-spreadsheet me-1"></i>CSV' },
                            { extend: 'excel', className: 'btn btn-sm btn-label-success me-1', text: '<i class="ti tabler-file-analytics me-1"></i>Excel' },
                            { extend: 'pdf', className: 'btn btn-sm btn-label-danger me-1', text: '<i class="ti tabler-file-text me-1"></i>PDF' },
                            { extend: 'print', className: 'btn btn-sm btn-label-info', text: '<i class="ti tabler-printer me-1"></i>Print' }
                        ]
                    },
                    topEnd: 'search'
                }
            });
        }
    });
})();
</script>
</body>
</html>
