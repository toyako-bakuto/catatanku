
import { authAPI, notesAPI, subnotesAPI, categoriesAPI } from "./api.js";
import {
    showNotification, formatDate, formatShortDate,
    escapeHTML, setButtonLoading, confirmAction
} from "./utils.js";

// ======================================
// STATE
// ======================================

const state = {
    user: null,
    notes: [],
    categories: [],
    filter: "all",
    sort: "desc",
    page: 1,
    totalPages: 1,
    totalNotes: 0
};

const NOTES_PER_PAGE = 5;

// ======================================
// DOM ELEMENTS
// ======================================

const userAvatar   = document.querySelector(".user-avatar");
const username     = document.querySelector(".username");
const userEmail    = document.querySelector(".user-email");
const logoutButton = document.querySelector(".logout-btn");

const noteForm       = document.querySelector(".note-form");
const noteInput      = noteForm?.querySelector(".note-input");
const subnoteInput   = noteForm?.querySelector(".subnote-textarea");
const dateInput      = noteForm?.querySelector('input[type="date"]');
const categorySelect = noteForm?.querySelector("select");

const notesList      = document.querySelector(".notes-list");
const filterControls = document.querySelector(".filter-controls");
const sortSelect     = document.querySelector(".sort-controls select");
const pagination     = document.querySelector(".page-numbers");

const notificationContainer = document.querySelector("#notification-container");

const modal     = document.querySelector(".modal");
const modalForm = modal?.querySelector("form");

// ======================================
// AUTH
// ======================================

async function checkSession() {
    try {
        const result = await authAPI.me();

        if (!result.data.logged_in) {
            window.location.replace("index.html");
            return false;
        }

        state.user = result.data.user;

        renderUserInfo();

        return true;
    } catch {
        window.location.replace("index.html");
        return false;
    }
}

function renderUserInfo() {
    if (!state.user) return;

    const name  = state.user.name || "Pengguna";
    const email = state.user.email || "";

    if (username) username.textContent = name;
    if (userEmail) userEmail.textContent = email;

    if (userAvatar) {
        userAvatar.textContent = name.charAt(0).toUpperCase();
    }
}

async function logout() {
    if (!confirmAction("Apakah kamu yakin ingin keluar?")) return;

    try {
        await authAPI.logout();
        window.location.replace("index.html");
    } catch (error) {
        showNotification(error.message, "error");
    }
}

// ======================================
// NOTES
// ======================================

async function loadNotes() {
    try {
        const result = await notesAPI.getAll({
            page: state.page,
            limit: NOTES_PER_PAGE,
            category: state.filter,
            sort: state.sort
        });

        const data = result.data;

        state.notes      = data.notes || [];
        state.totalPages = data.pagination?.total_pages || 1;
        state.totalNotes = data.pagination?.total || 0;

        renderNotes();
        renderStats(data.stats);
        renderPagination();

    } catch (error) {
        showNotification(error.message, "error");
    }
}

function renderNotes() {
    if (!notesList) return;

    const sortInfo = `
        <div class="sort-info">
            Urutan:
            <strong>${state.sort === "desc" ? "Terbaru ke Terlama" : "Terlama ke Terbaru"}</strong>
            | Total:
            <strong>${state.totalNotes}</strong> catatan
        </div>
    `;

    if (state.notes.length === 0) {
        notesList.innerHTML = `
            ${sortInfo}
            <p class="empty-message">Belum ada catatan.</p>
        `;
        return;
    }

    notesList.innerHTML = `
        ${sortInfo}
        ${state.notes.map(renderNote).join("")}
    `;
}

function renderNote(note) {
    const category = note.category || {};
    const subnotes = note.subnotes || [];
    const color = category.color || "#adb5bd";

    return `
        <article class="note-item"
                 data-note-id="${escapeHTML(note.id)}"
                 style="border-left-color: ${escapeHTML(color)};">

            <div class="note-header">
                <div class="note-date">
                    <i class="far fa-calendar"></i>
                    ${escapeHTML(formatDate(note.date))}
                </div>

                <div class="note-tags">
                    <span class="note-tag"
                          style="background-color: ${escapeHTML(color)}20;
                                 color: ${escapeHTML(color)};">
                        ${escapeHTML(category.name || "Tanpa kategori")}
                    </span>
                </div>
            </div>

            <div class="note-content">
                ${escapeHTML(note.content)}
            </div>

            <div class="subnotes-section">
                <div class="subnotes-list">
                    ${subnotes.map(renderSubnote).join("")}
                </div>
            </div>

            <div class="quick-add-subnote" hidden>
                <textarea maxlength="500"
                          placeholder="Tulis sub-note untuk catatan ini..."></textarea>

                <div class="char-counter">
                    <span>0</span>/500 karakter
                </div>

                <div class="quick-add-actions">
                    <button type="button" class="quick-cancel-btn">
                        Batal
                    </button>

                    <button type="button" class="quick-add-btn">
                        <i class="fas fa-plus"></i>
                        Tambah Sub-Note
                    </button>
                </div>
            </div>

            <div class="note-actions">
                <button type="button" class="action-btn edit-btn">
                    <i class="fas fa-edit"></i> Edit
                </button>

                <button type="button" class="action-btn add-quick-subnote-btn">
                    <i class="fas fa-plus-circle"></i> Sub-Note
                </button>

                <button type="button" class="action-btn toggle-subnotes-btn">
                    <i class="fas fa-chevron-up"></i>
                    Sub-Notes (${subnotes.length})
                </button>

                <button type="button" class="action-btn delete-btn">
                    <i class="fas fa-trash"></i> Hapus
                </button>
            </div>

        </article>
    `;
}

function renderSubnote(subnote) {
    return `
        <div class="subnote-item"
             data-subnote-id="${escapeHTML(subnote.id)}">

            <div class="subnote-content">
                ${escapeHTML(subnote.content)}
            </div>

            <div class="subnote-date">
                ${escapeHTML(formatShortDate(subnote.created_at))}
            </div>

            <div class="subnote-actions">
                <button type="button"
                        class="subnote-action-btn delete-subnote-btn"
                        title="Hapus sub-note">
                    <i class="fas fa-trash"></i>
                </button>
            </div>

        </div>
    `;
}

// ======================================
// CREATE NOTE
// ======================================

async function handleCreateNote(event) {
    event.preventDefault();

    const content    = noteInput.value.trim();
    const subnote    = subnoteInput.value.trim();
    const date       = dateInput.value;
    const categoryId = categorySelect.value;

    if (!content) {
        showNotification("Isi catatan terlebih dahulu.", "error");
        return;
    }

    if (content.length > 100000) {
        showNotification("Catatan maksimal 100000 karakter.", "error");
        return;
    }

    if (subnote.length > 500) {
        showNotification("Sub-note maksimal 500 karakter.", "error");
        return;
    }

    const button = noteForm.querySelector('button[type="submit"]');

    try {
        setButtonLoading(button, true, "Menyimpan...");

        await notesAPI.create({
            content,
            date,
            category_id: categoryId || null,
            subnote: subnote || null
        });

        noteForm.reset();

        dateInput.value = getToday();

        // Perbarui counter setelah form direset.
        initCharacterCounters();

        state.page = 1;

        await loadNotes();

        showNotification("Catatan berhasil disimpan.");

    } catch (error) {
        showNotification(error.message, "error");
    } finally {
        setButtonLoading(button, false);
    }
}

// ======================================
// UPDATE NOTE
// ======================================

async function editNote(noteId) {
    const note = state.notes.find(
        item => String(item.id) === String(noteId)
    );

    if (!note) return;

    const newContent = prompt("Edit catatan:", note.content);

    if (newContent === null) return;

    const content = newContent.trim();

    if (!content) {
        showNotification("Catatan tidak boleh kosong.", "error");
        return;
    }

    try {
        await notesAPI.update(noteId, {
            content,
            date: note.date
        });

        await loadNotes();

        showNotification("Catatan berhasil diperbarui.");

    } catch (error) {
        showNotification(error.message, "error");
    }
}

// ======================================
// DELETE NOTE
// ======================================

async function deleteNote(noteId) {
    if (!confirmAction("Hapus catatan ini?")) return;

    try {
        await notesAPI.delete(noteId);

        showNotification("Catatan berhasil dihapus.");

        await loadNotes();

    } catch (error) {
        showNotification(error.message, "error");
    }
}

// ======================================
// SUBNOTES
// ======================================

function toggleQuickSubnote(noteElement) {
    const form = noteElement.querySelector(".quick-add-subnote");

    form.hidden = !form.hidden;

    if (!form.hidden) {
        const textarea = form.querySelector("textarea");

        textarea.focus();

        updateCounter(textarea);
    }
}

async function addSubnote(noteElement) {
    const noteId = noteElement.dataset.noteId;

    const textarea = noteElement.querySelector(
        ".quick-add-subnote textarea"
    );

    const content = textarea.value.trim();

    if (!content) {
        showNotification("Isi sub-note terlebih dahulu.", "error");
        return;
    }

    if (content.length > 500) {
        showNotification("Sub-note maksimal 500 karakter.", "error");
        return;
    }

    try {
        await subnotesAPI.create({
            note_id: noteId,
            content
        });

        await loadNotes();

        showNotification("Sub-note berhasil ditambahkan.");

    } catch (error) {
        showNotification(error.message, "error");
    }
}

async function deleteSubnote(subnoteId) {
    if (!confirmAction("Hapus sub-note ini?")) return;

    try {
        await subnotesAPI.delete(subnoteId);

        await loadNotes();

        showNotification("Sub-note berhasil dihapus.");

    } catch (error) {
        showNotification(error.message, "error");
    }
}

// ======================================
// CATEGORIES
// ======================================

async function loadCategories() {
    try {
        const result = await categoriesAPI.getAll();

        state.categories = result.data.categories || [];

        renderCategories();

    } catch (error) {
        showNotification(error.message, "error");
    }
}

function renderCategories() {
    if (!categorySelect) return;

    categorySelect.innerHTML = `
        <option value="">-- Pilih Kategori --</option>

        ${state.categories.map(category => `
            <option value="${escapeHTML(category.id)}">
                ${escapeHTML(category.name)}
            </option>
        `).join("")}
    `;

    const currentFilter = state.filter;

    filterControls.innerHTML = `
        <button type="button"
                class="filter-btn ${currentFilter === "all" ? "active" : ""}"
                data-category="all">
            Semua
        </button>

        ${state.categories.map(category => `
            <button type="button"
                    class="filter-btn ${String(currentFilter) === String(category.id) ? "active" : ""}"
                    data-category="${escapeHTML(category.id)}">
                ${escapeHTML(category.name)}
            </button>
        `).join("")}
    `;
}

function openCategoryModal() {
    if (!modal) return;

    modal.hidden = false;
}

function closeCategoryModal() {
    if (!modal) return;

    modal.hidden = true;
}

async function createCategory(event) {
    event.preventDefault();

    const nameInput = modalForm.querySelector(
        'input[name="category_name"]'
    );

    const colorInput = modalForm.querySelector(
        'input[type="color"]'
    );

    const name = nameInput.value.trim();
    const color = colorInput.value;

    if (!name) {
        showNotification("Nama kategori wajib diisi.", "error");
        return;
    }

    const button = modalForm.querySelector('button[type="submit"]');

    try {
        setButtonLoading(button, true, "Menyimpan...");

        await categoriesAPI.create({
            name,
            color
        });

        await loadCategories();

        closeCategoryModal();

        modalForm.reset();

        showNotification("Kategori berhasil dibuat.");

    } catch (error) {
        showNotification(error.message, "error");
    } finally {
        setButtonLoading(button, false);
    }
}

// ======================================
// STATISTICS
// ======================================

function renderStats(stats = {}) {
    const numbers = document.querySelectorAll(".stat-box .number");

    if (numbers.length < 3) return;

    numbers[0].textContent = stats.total_notes ?? 0;
    numbers[1].textContent = stats.total_subnotes ?? 0;
    numbers[2].textContent = stats.this_month ?? 0;
}

// ======================================
// FILTER & SORT
// ======================================

function handleFilter(event) {
    const button = event.target.closest(".filter-btn");

    if (!button) return;

    state.filter = button.dataset.category;
    state.page = 1;

    filterControls.querySelectorAll(".filter-btn").forEach(item => {
        item.classList.toggle("active", item === button);
    });

    loadNotes();
}

function handleSort() {
    state.sort = sortSelect.value === "asc" ? "asc" : "desc";

    state.page = 1;

    loadNotes();
}

// ======================================
// PAGINATION
// ======================================

function renderPagination() {
    if (!pagination) return;

    if (state.totalPages <= 1) {
        pagination.innerHTML = "";
        return;
    }

    const total = state.totalPages;
    const current = state.page;
    const windowSize = 2;

    const start = Math.max(1, current - windowSize);
    const end = Math.min(total, current + windowSize);

    let html = "";

    if (current > 1) {
        html += `
            <button type="button"
                    class="page-number"
                    data-page="${current - 1}">
                ‹
            </button>
        `;
    }

    if (start > 1) {
        html += `
            <button type="button"
                    class="page-number"
                    data-page="1">
                1
            </button>
        `;

        if (start > 2) {
            html += `<span class="page-dots">…</span>`;
        }
    }

    for (let page = start; page <= end; page++) {
        html += `
            <button type="button"
                    class="page-number ${page === current ? "active" : ""}"
                    data-page="${page}">
                ${page}
            </button>
        `;
    }

    if (end < total) {
        if (end < total - 1) {
            html += `<span class="page-dots">…</span>`;
        }

        html += `
            <button type="button"
                    class="page-number"
                    data-page="${total}">
                ${total}
            </button>
        `;
    }

    if (current < total) {
        html += `
            <button type="button"
                    class="page-number"
                    data-page="${current + 1}">
                ›
            </button>
        `;
    }

    pagination.innerHTML = html;
}

function handlePagination(event) {
    const button = event.target.closest(".page-number");

    if (!button) return;

    const page = Number(button.dataset.page);

    if (
        !Number.isInteger(page) ||
        page < 1 ||
        page > state.totalPages
    ) {
        return;
    }

    state.page = page;

    loadNotes();
}

// ======================================
// SECTION TOGGLE
// ======================================

function handleSectionToggle(event) {
    const button = event.target.closest(".section-toggle-btn");

    if (!button) return;

    const section = button.closest("section");

    const content = Array.from(section.children).find(
        el => !el.classList.contains("section-header")
    );

    if (!content) return;

    content.hidden = !content.hidden;

    const label = button.querySelector("span");

    if (label) {
        label.textContent = content.hidden
            ? "Tampilkan"
            : "Minimalkan";
    }

    const icon = button.querySelector("i");

    if (icon) {
        icon.className = content.hidden
            ? "fas fa-window-maximize"
            : "fas fa-window-minimize";
    }
}

// ======================================
// CHARACTER COUNTER
// ======================================

function updateCounter(textarea) {
    if (!textarea) return;

    const container = textarea.parentElement;

    const counter = container?.querySelector(
        ".char-counter span"
    );

    if (!counter) return;

    counter.textContent = textarea.value.length;
}

function handleCharacterCounter(event) {
    const textarea = event.target.closest(
        ".note-input, .subnote-textarea, .quick-add-subnote textarea"
    );

    if (!textarea) return;

    updateCounter(textarea);
}

function initCharacterCounters() {
    const textareas = document.querySelectorAll(
        ".note-input, .subnote-textarea, .quick-add-subnote textarea"
    );

    textareas.forEach(updateCounter);
}

// ======================================
// DATE
// ======================================

function getToday() {
    const now = new Date();

    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, "0");
    const day = String(now.getDate()).padStart(2, "0");

    return `${year}-${month}-${day}`;
}

// ======================================
// EVENT DELEGATION
// ======================================

function handleNoteActions(event) {
    const noteElement = event.target.closest(".note-item");

    if (!noteElement) return;

    const noteId = noteElement.dataset.noteId;

    if (event.target.closest(".edit-btn")) {
        editNote(noteId);
        return;
    }

    if (event.target.closest(".delete-btn")) {
        deleteNote(noteId);
        return;
    }

    if (event.target.closest(".add-quick-subnote-btn")) {
        toggleQuickSubnote(noteElement);
        return;
    }

    if (event.target.closest(".quick-cancel-btn")) {
        const form = noteElement.querySelector(".quick-add-subnote");

        form.hidden = true;

        return;
    }

    if (event.target.closest(".quick-add-btn")) {
        addSubnote(noteElement);
        return;
    }

    if (event.target.closest(".toggle-subnotes-btn")) {
        const section = noteElement.querySelector(".subnotes-section");

        section.hidden = !section.hidden;

        return;
    }

    if (event.target.closest(".delete-subnote-btn")) {
        const subnoteElement = event.target.closest(".subnote-item");

        deleteSubnote(subnoteElement.dataset.subnoteId);
    }
}

// ======================================
// COLOR PRESETS
// ======================================

function handleColorPreset(event) {
    const preset = event.target.closest(".color-preset");

    if (!preset) return;

    const colorInput = modalForm.querySelector(
        'input[type="color"]'
    );

    if (!colorInput) return;

    const presetColor = preset.style.backgroundColor;

    if (/^#[0-9a-f]{6}$/i.test(presetColor)) {
        colorInput.value = presetColor;
    }
}

// ======================================
// INITIALIZATION
// ======================================

function registerEvents() {
    logoutButton?.addEventListener("click", logout);

    noteForm?.addEventListener("submit", handleCreateNote);

    filterControls?.addEventListener("click", handleFilter);

    sortSelect?.addEventListener("change", handleSort);

    pagination?.addEventListener("click", handlePagination);

    notesList?.addEventListener("click", handleNoteActions);

    document.addEventListener("click", handleSectionToggle);

    document.addEventListener("input", handleCharacterCounter);

    document.querySelector(".add-category-btn")
        ?.addEventListener("click", openCategoryModal);

    modalForm?.addEventListener("submit", createCategory);

    modal?.querySelector(".cancel-category-btn")
        ?.addEventListener("click", closeCategoryModal);

    modal?.addEventListener("click", event => {
        if (event.target === modal) {
            closeCategoryModal();
        }
    });

    modal?.querySelectorAll(".color-preset").forEach(preset => {
        preset.addEventListener("click", handleColorPreset);
    });
}

async function initDashboard() {
    if (!noteForm || !notesList) return;

    const authenticated = await checkSession();

    if (!authenticated) return;

    registerEvents();

    if (dateInput) {
        dateInput.value = getToday();
    }

    initCharacterCounters();

    notesList.innerHTML = "";

    if (notificationContainer) {
        notificationContainer.innerHTML = "";
    }

    if (modal) {
        modal.hidden = true;
    }

    await loadCategories();
    await loadNotes();
}

document.addEventListener("DOMContentLoaded", initDashboard);
