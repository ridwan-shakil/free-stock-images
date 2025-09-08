jQuery(document).ready(function ($) {
    console.log('FSI: admin script loaded.');

    // Basic test: sample AJAX search trigger if you want to test manually
    $(document).on('click', '#fsi-test-search', function (e) {
        e.preventDefault();
        $.post(fsi_ajax.ajax_url, {
            action: 'fsi_search',
            query: 'test',
            _ajax_nonce: fsi_ajax.nonce
        }, function (res) {
            console.log('fsi_search response:', res);
            alert('fsi_search response: ' + (res.data ? res.data.message : 'no data'));
        });
    });

    // Test import
    $(document).on('click', '#fsi-test-import', function (e) {
        e.preventDefault();
        $.post(fsi_ajax.ajax_url, {
            action: 'fsi_import',
            url: 'https://example.com/image.jpg',
            _ajax_nonce: fsi_ajax.nonce
        }, function (res) {
            console.log('fsi_import response:', res);
            alert('fsi_import response: ' + (res.data ? res.data.message : 'no data'));
        });
    });
});
