// Slider
const loginForm = document.querySelector("form.login");
const signupForm = document.querySelector("form.signup");
const loginBtn = document.querySelector("label.login");
const signupBtn = document.querySelector("label.signup");

signupBtn.onclick = () => {
    loginForm.style.marginLeft = "-50%";
};

loginBtn.onclick = () => {
    loginForm.style.marginLeft = "0%";
};

// Regex
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const passwordRegex =
    /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{6,}$/;

// Popup
function showPopup(message) {
    const popup = document.getElementById("popup");
    const text = document.getElementById("popup-message");
    text.innerHTML = message.replace(/\n/g, "<br>");
    popup.classList.remove("hidden");
    setTimeout(() => popup.classList.add("hidden"), 3000);
}

// Login validation
loginForm.addEventListener("submit", e => {
    const email = e.target.email.value;
    const pass = e.target.password.value;

    if (!emailRegex.test(email)) {
        e.preventDefault();
        showPopup("Email i pavlefshëm");
    }
});

// Signup validation
signupForm.addEventListener("submit", e => {
    const email = e.target.email.value;
    const pass = e.target.password.value;
    const confirm = e.target.confirm_password.value;

    if (!emailRegex.test(email)) {
        e.preventDefault();
        showPopup("Email i pavlefshëm");
    } else if (!passwordRegex.test(pass)) {
        e.preventDefault();
        showPopup(
            "Password i pavlefshëm\n" +
            "- Minimumi 6 karaktere\n" +
            "- Shkronja të mëdha & të vogla\n" +
            "- Numra & simbole"
        );
    } else if (pass !== confirm) {
        e.preventDefault();
        showPopup("Passwordet nuk përputhen");
    }
});

// Show/hide password
document.querySelectorAll(".eye").forEach(eye => {
    eye.addEventListener("click", () => {
        const input = document.getElementById(eye.dataset.target);
        if (!input) return;

        input.type = input.type === "password" ? "text" : "password";
        eye.textContent = input.type === "password" ? "👁️" : "🙈";
    });
});
