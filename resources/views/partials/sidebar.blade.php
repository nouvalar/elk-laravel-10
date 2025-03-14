<div class="sidebar" id="sidebar">
    <button id="toggleSidebar" class="menu-toggle">☰</button>
    <a href="#" class="toggle-menu" data-target="dwhMenu">DWH</a>
    <div id="dwhMenu" class="submenu">
        <a href="#">Submenu 1</a>
        <a href="#">Submenu 2</a>
    </div>
    <a href="#" class="toggle-menu" data-target="bridgingMenu">Bridging</a>
    <div id="bridgingMenu" class="submenu">
        <a href="#">Submenu 1</a>
        <a href="#">Submenu 2</a>
    </div>
    <a href="#" class="toggle-menu" data-target="schedulerMenu">Scheduler</a>
    <div id="schedulerMenu" class="submenu">
        <a href="#">Submenu 1</a>
        <a href="#">Submenu 2</a>
    </div>
</div>


<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Tombol toggle sidebar
        document.getElementById("toggleSidebar").addEventListener("click", function() {
            document.getElementById("sidebar").classList.toggle("collapsed");
        });

        // Toggle submenu
        document.querySelectorAll(".toggle-menu").forEach(item => {
            item.addEventListener("click", function(event) {
                event.preventDefault(); // Mencegah link reload
                let target = document.getElementById(this.getAttribute("data-target"));
                if (target) {
                    target.style.display = target.style.display === "block" ? "none" : "block";
                }
            });
        });
    });
</script>
