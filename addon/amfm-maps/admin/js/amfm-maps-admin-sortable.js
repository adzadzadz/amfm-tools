(function($) {
  // Drag-and-drop for filter groups and options
  $(document).ready(function() {
    // Make filter groups sortable
    $('#filter-types-container').sortable({
      handle: '.filter-type-header',
      items: '.filter-type-item',
      update: function(event, ui) {
        updateFilterOrder();
      }
    });

    // Delegate: Make filter options sortable when expanded
    $(document).on('mouseenter', '.filter-type-settings', function() {
      const $optionsList = $(this).find('.options-list');
      if ($optionsList.data('ui-sortable')) return;
      $optionsList.sortable({
        items: '.option-tag',
        update: function(event, ui) {
          updateOptionOrder($(this));
        }
      });
    });

    // Save order to hidden fields or data attributes and update config for saving
    function updateFilterOrder() {
      $('#filter-types-container .filter-type-item').each(function(i) {
        $(this).attr('data-order', i);
        // If there is a hidden input for order, update it
        $(this).find('input.filter-type-order').val(i);
      });
      // Optionally, trigger change event for auto-save or enable save button
      $('#filter-types-container').trigger('order-updated');
    }

    function updateOptionOrder($optionsList) {
      $optionsList.find('.option-tag').each(function(i) {
        $(this).attr('data-order', i);
        // If there is a hidden input for order, update it
        $(this).find('input.option-order').val(i);
      });
      // Optionally, trigger change event for auto-save or enable save button
      $optionsList.trigger('order-updated');
    }
  });
})(jQuery);
