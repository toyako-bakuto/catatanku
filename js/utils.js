export function showNotification(message, type = "success") {
    const container = document.querySelector("#notification-container");
    if (!container) return;

    const notification = document.createElement("div");
    notification.className = `notification ${type}`;

    const icon = type === "success" ? "fa-check-circle" : "fa-exclamation-circle";

    notification.innerHTML = `
        <i class="fas ${icon}"></i>
        <div class="notification-content">
            <div class="notification-title">
                ${type === "success" ? "Berhasil" : "Terjadi Kesalahan"}
            </div>
            <div class="notification-message"></div>
        </div>
        <button type="button" class="notification-close">
            <i class="fas fa-times"></i>
        </button>
    `;

    notification.querySelector(".notification-message").textContent = message;
    container.append(notification);

    notification.querySelector(".notification-close").addEventListener("click", () => {
        notification.remove();
    });

    setTimeout(() => notification.remove(), 4000);
}

function parseDateOnly(date) {
    if (typeof date !== "string") return null;

    const datePart = date.slice(0, 10);
    const match = datePart.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!match) return null;

    const [, year, month, day] = match;
    const parsedDate = new Date(Number(year), Number(month) - 1, Number(day));

    if (
        parsedDate.getFullYear() !== Number(year) ||
        parsedDate.getMonth() !== Number(month) - 1 ||
        parsedDate.getDate() !== Number(day)
    ) {
        return null;
    }

    return parsedDate;
}

export function formatDate(date) {
    const parsedDate = parseDateOnly(date);
    if (!parsedDate) return "-";

    return new Intl.DateTimeFormat("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric"
    }).format(parsedDate);
}

export function formatShortDate(date) {
    const parsedDate = parseDateOnly(date);
    if (!parsedDate) return "-";

    return new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric"
    }).format(parsedDate);
}

export function escapeHTML(value) {
    return String(value ?? "").replace(
        /[&<>"']/g,
        character => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#39;"
        })[character]
    );
}

export function setButtonLoading(button, loading, text = "Memproses...") {
    if (!button) return;

    if (loading) {
        button.dataset.originalText = button.innerHTML;
        button.disabled = true;
        button.textContent = text;
    } else {
        button.disabled = false;
        if (button.dataset.originalText) {
            button.innerHTML = button.dataset.originalText;
            delete button.dataset.originalText;
        }
    }
}

export function confirmAction(message) {
    return window.confirm(message);
}