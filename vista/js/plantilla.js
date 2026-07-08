$(document).ready(function () {
    // Alternar menú en click
    $("#btnToggleMenu").on("click", function (e) {
        e.preventDefault();
        if ($(window).width() <= 700) {
            $("body").toggleClass("sidebar-mobile-open");
        } else {
            $("body").toggleClass("sidebar-collapsed");
        }
    });

    // Cerrar menú móvil al hacer clic en el overlay de fondo
    $(".sidebar-overlay").on("click", function () {
        $("body").removeClass("sidebar-mobile-open");
    });

    // 3. 🔥 ¡CORREGIDO! Cerrar el menú en móviles SOLO si es un enlace final
    $('#sidebarMenu a').on('click', function (e) {
        // Detectamos si el enlace actual es un disparador de submenú
        window.isDropdown = $(this).hasClass('dropdown-toggle') ||
            $(this).attr('data-bs-toggle') === 'collapse' ||
            $(this).attr('data-toggle') === 'collapse' ||
            $(this).next('ul, .nav-treeview, .submenu').length > 0;

        // Si es un submenú, frenamos esta función para que NO cierre el sidebar
        if (window.isDropdown) {
            return;
        }

        // Si NO es un submenú (es un enlace real a otra página), cerramos en móviles
        if ($(window).width() <= 700) {
            $('body').removeClass('sidebar-mobile-open');
        }
    });

    // Monitorear redimensionado de pantalla para consistencia
    $(window).on("resize", function () {
        if ($(window).width() > 700) {
            $("body").removeClass("sidebar-mobile-open");
        }
    });
});