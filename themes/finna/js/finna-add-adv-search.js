function addSearch(group, _fieldValues) {
    var fieldValues = _fieldValues || {};
    // Build the new search
    var inputID = group + '_' + groupLength[group];
    var $newSearch = $($('#new_search_template').html());

    $newSearch.attr('id', 'search' + inputID);
    $newSearch.find('input.form-control')
        .attr('id', 'search_lookfor' + inputID)
        .attr('name', 'lookfor' + group + '[]')
        .val('');
    $newSearch.find('select.adv-term-type option:first-child').attr('selected', 1);
    $newSearch.find('select.adv-term-type')
        .attr('id', 'search_type' + inputID)
        .attr('name', 'type' + group + '[]');
    $newSearch.find('.adv-term-remove')
        .attr('onClick', 'return deleteSearch(' + group + ',' + groupLength[group] + ')');
    // Preset Values
    if (typeof fieldValues.term !== "undefined") {
        $newSearch.find('input.form-control').val(fieldValues.term);
    }
    if (typeof fieldValues.field !== "undefined") {
        $newSearch.find('select.adv-term-type option[value="' + fieldValues.field + '"]').attr('selected', 1);
    }
    if (typeof fieldValues.op !== "undefined") {
        $newSearch.find('select.adv-term-op option[value="' + fieldValues.op + '"]').attr('selected', 1);
    }
    // Insert it
    $("#group" + group + "Holder").before($newSearch);
    // Individual search ops (for searches like EDS)
    if (groupLength[group] === 0) {
        $newSearch.find('.first-op')
            .attr('name', 'op' + group + '[]')
            .removeClass('hidden');
        $newSearch.find('select.adv-term-op').remove();
    } else {
        $newSearch.find('select.adv-term-op')
            .attr('id', 'search_op' + group + '_' + groupLength[group])
            .attr('name', 'op' + group + '[]')
            .removeClass('hidden');
        $newSearch.find('.first-op').remove();
        $newSearch.find('label').remove();
        // Show x if we have more than one search inputs
        $('#group' + group + ' .adv-term-remove').removeClass('hidden');
    }
    groupLength[group]++;
    return false;
}
