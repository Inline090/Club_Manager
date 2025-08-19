document.addEventListener("DOMContentLoaded", function () {
    console.log("JavaScript Loaded!");

    // Example: Change button color on hover
    let buttons = document.querySelectorAll(".btn-custom");
    buttons.forEach(btn => {
        btn.addEventListener("mouseover", () => {
            btn.style.backgroundColor = "#bbb";
        });
        btn.addEventListener("mouseout", () => {
            btn.style.backgroundColor = "#d3d3d3";
        });
    });
});
