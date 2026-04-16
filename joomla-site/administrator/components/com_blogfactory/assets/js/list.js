Joomla.orderTable = function () {
    var table = document.getElementById("sortTable");
    var direction = document.getElementById("directionTable");
    var order = table.options[table.selectedIndex].value;
    var dirn = direction.options[direction.selectedIndex].value;

    Joomla.tableOrdering(order, dirn, '');
}
