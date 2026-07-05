// Toggle del menú lateral para móviles y PC
$("#btnToggleMenu").on("click", function(e) {
    e.preventDefault();
    $("#sidebarMenu").toggle();
});