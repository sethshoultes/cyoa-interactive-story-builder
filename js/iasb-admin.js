jQuery(document).ready(function($) {
    $('select[name="iasb_parent_episodes[]"]').select2({
        width: '100%',
        placeholder: 'Select parent episodes',
    });
    $('select[name="iasb_child_episodes[]"]').select2({
        width: '100%',
        placeholder: 'Select child episodes',
    });
});
