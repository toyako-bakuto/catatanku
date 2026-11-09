const API_BASE = "../catatanku/api";

/*
 * FIX: Spread `options` dulu, lalu headers di-merge eksplisit,
 * agar Content-Type tidak hilang jika pemanggil mengirim headers.
 */
async function apiFetch(endpoint, options = {}) {
    const { headers, ...rest } = options;

    const response = await fetch(`${API_BASE}/${endpoint}`, {
        credentials: "same-origin",
        ...rest,
        headers: {
            "Content-Type": "application/json",
            ...(headers || {})
        }
    });

    let result;
    try {
        result = await response.json();
    } catch {
        throw new Error("Respons server bukan JSON yang valid.");
    }

    if (!response.ok || result.success === false) {
        throw new Error(result.message || "Terjadi kesalahan pada server.");
    }

    return result;
}

export const authAPI = {
    login(data) {
        return apiFetch("auth/login.php", {
            method: "POST",
            body: JSON.stringify(data)
        });
    },
    me() {
        return apiFetch("auth/me.php");
    },
    logout() {
        return apiFetch("auth/logout.php", { method: "POST" });
    }
};

export const notesAPI = {
    getAll(params = {}) {
        const query = new URLSearchParams(params);
        return apiFetch(`notes/index.php?${query}`);
    },
    create(data) {
        return apiFetch("notes/create.php", {
            method: "POST",
            body: JSON.stringify(data)
        });
    },
    update(id, data) {
        return apiFetch("notes/update.php", {
            method: "POST",
            body: JSON.stringify({ id, ...data })
        });
    },
    delete(id) {
        return apiFetch("notes/delete.php", {
            method: "POST",
            body: JSON.stringify({ id })
        });
    }
};

export const subnotesAPI = {
    create(data) {
        return apiFetch("subnotes/create.php", {
            method: "POST",
            body: JSON.stringify(data)
        });
    },
    delete(id) {
        return apiFetch("subnotes/delete.php", {
            method: "POST",
            body: JSON.stringify({ id })
        });
    }
};

export const categoriesAPI = {
    getAll() {
        return apiFetch("categories/index.php");
    },
    create(data) {
        return apiFetch("categories/create.php", {
            method: "POST",
            body: JSON.stringify(data)
        });
    }
};