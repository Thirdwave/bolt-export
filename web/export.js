;(function($, window, document, undefined) {
    $(function() {
        $('[data-sortable]').sortable({
            axis: 'y',
            handle: '[data-sortable-handle]',
            cursor: 'move'
        });

        $('[data-select]').select2({
            placeholder: '(geen)',
            allowClear: true
        });
        
        $('[data-export-contenttype]').change(function(e) {
            e.preventDefault();

            window.location = '?contenttype=' + $(this).val();
        });

        $('[data-export-add]').click(function(e) {
            e.preventDefault();

            var type = $(this).attr('data-export-add');

            $('[data-export-' + type + ']').append(
                $('[data-export-template="' + type + '"]')
                    .clone()
                    .attr('data-export-template', null)
                    .removeClass('hidden')
            );

            $('[data-export-remove-all="' + type + '"]').removeClass('hidden').show();
        });

        $('[data-export-remove-all]').click(function(e) {
            e.preventDefault();

            var type = $(this).attr('data-export-remove-all');

            $('[data-export-' + type + ']').empty();

            $('[data-export-remove-all="' + type + '"]').removeClass('hidden').hide();
        });

        $('body').on('click', '[data-export-remove]', function(e) {
            e.preventDefault();

            var type = $(this).attr('data-export-remove');

            $(this).closest('li').remove();

            if ( $('[data-export-' + type + ']').find('li').length === 0 ) {
                $('[data-export-remove-all="' + type + '"]').removeClass('hidden').hide();
            }
        });
    });
})(jQuery, window, document);