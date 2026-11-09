import { authAPI } from "./api.js";
import { showNotification, setButtonLoading } from "./utils.js";

const loginForm     = document.querySelector("form");
const emailInput    = document.querySelector("#email");
const passwordInput = document.querySelector("#password");
const rememberInput = document.querySelector("#remember");

async function checkLogin() {
    try {
        const result = await authAPI.me();
        if (result.data.logged_in) {
            window.location.replace("dashboard.html");
        }
    } catch {
        // Belum login.
    }
}

async function handleLogin(event) {
    event.preventDefault();

    const email    = emailInput.value.trim();
    const password = passwordInput.value;
    const remember = rememberInput.checked;

    if (!email || !password) {
        showNotification("Email dan password wajib diisi.", "error");
        return;
    }

    const submitButton = loginForm.querySelector('button[type="submit"]');

    try {
        setButtonLoading(submitButton, true, "Memproses...");

        const result = await authAPI.login({ email, password, remember });

        showNotification(result.message || "Login berhasil.");
        window.location.replace("dashboard.html");
    } catch (error) {
        showNotification(error.message, "error");
    } finally {
        setButtonLoading(submitButton, false);
    }
}

function initLogin() {
    if (!loginForm || !emailInput || !passwordInput) return;
    loginForm.addEventListener("submit", handleLogin);
    checkLogin();
}

document.addEventListener("DOMContentLoaded", initLogin);