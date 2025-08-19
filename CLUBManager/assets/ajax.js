document.getElementById("renew-btn").addEventListener("click", function() {
    let email = document.getElementById("email").value;
    
    fetch("../backend/renew_process.php", {
        method: "POST",
        body: JSON.stringify({ email: email }),
        headers: { "Content-Type": "application/json" }
    })
    .then(response => response.text())
    .then(data => alert(data));
});
