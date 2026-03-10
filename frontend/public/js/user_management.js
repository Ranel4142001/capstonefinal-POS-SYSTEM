// public/js/user_management.js

document.addEventListener("DOMContentLoaded", () => {
    const apiUrl = "../api/users.php";

    // DOM elements
    const userTableBody = document.querySelector("#userTable tbody");
    const addUserForm = document.querySelector("#addUserForm");
    const editUserForm = document.querySelector("#editUserForm");
    const deleteUserForm = document.querySelector("#deleteUserForm");

    const editUserModalEl = document.getElementById("editUserModal");
    const deleteUserModalEl = document.getElementById("deleteUserModal");

    // Bootstrap modals (safe init)
    const editUserModal = editUserModalEl ? new bootstrap.Modal(editUserModalEl) : null;
    const deleteUserModal = deleteUserModalEl ? new bootstrap.Modal(deleteUserModalEl) : null;

    /**
     * Load users and render table
     */
    async function loadUsers() {
        try {
            const response = await fetch(`${apiUrl}?action=list`);
            const data = await response.json();

            if (!userTableBody) return;

            userTableBody.innerHTML = "";

            if (Array.isArray(data) && data.length > 0) {
                data.forEach((user) => {
                    const row = document.createElement("tr");
                    row.innerHTML = `
                        <td>${user.id}</td>
                        <td>${user.username}</td>
                        <td>${user.email || ""}</td>
                        <td>${user.role}</td>
                        <td>${user.created_at}</td>
                        <td>
                            <button class="btn btn-sm btn-warning edit-btn" data-id="${user.id}" data-username="${user.username}" data-email="${user.email}" data-role="${user.role}">Edit</button>
                            <button class="btn btn-sm btn-danger delete-btn" data-id="${user.id}" data-username="${user.username}">Delete</button>
                        </td>
                    `;
                    userTableBody.appendChild(row);
                });
            } else {
                userTableBody.innerHTML = `<tr><td colspan="6" class="text-center">No users found.</td></tr>`;
            }
        } catch (error) {
            console.error("Error loading users:", error);
            if (userTableBody) {
                userTableBody.innerHTML = `<tr><td colspan="6" class="text-danger text-center">Failed to load users.</td></tr>`;
            }
        }
    }

    /**
     * Add user
     */
    if (addUserForm) {
        addUserForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const formData = new FormData(addUserForm);
            formData.append("action", "add");

            try {
                const response = await fetch(apiUrl, {
                    method: "POST",
                    body: formData,
                });
                const result = await response.json();

                if (result.status === "success") {
                    addUserForm.reset();
                    await loadUsers();
                    alert(result.message);
                } else {
                    alert("Error: " + result.message);
                }
            } catch (error) {
                console.error("Error adding user:", error);
                alert("Error adding user. Please try again.");
            }
        });
    }

    /**
     * Edit user
     */
    if (userTableBody && editUserForm) {
        userTableBody.addEventListener("click", (e) => {
            if (e.target.classList.contains("edit-btn")) {
                const btn = e.target;
                document.getElementById("edit_user_id").value = btn.dataset.id;
                document.getElementById("edit_username").value = btn.dataset.username;
                document.getElementById("edit_email").value = btn.dataset.email;
                document.getElementById("edit_role").value = btn.dataset.role;
                if (editUserModal) editUserModal.show();
            }
        });

        editUserForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const formData = new FormData(editUserForm);
            formData.append("action", "edit");

            try {
                const response = await fetch(apiUrl, {
                    method: "POST",
                    body: formData,
                });
                const result = await response.json();

                if (result.status === "success") {
                    if (editUserModal) editUserModal.hide();
                    await loadUsers();
                    alert(result.message);
                } else {
                    alert("Error: " + result.message);
                }
            } catch (error) {
                console.error("Error editing user:", error);
                alert("Error editing user. Please try again.");
            }
        });
    }

    /**
     * Delete user
     */
    if (userTableBody && deleteUserForm) {
        userTableBody.addEventListener("click", (e) => {
            if (e.target.classList.contains("delete-btn")) {
                const btn = e.target;
                document.getElementById("delete_user_id").value = btn.dataset.id;
                document.getElementById("delete_username").textContent = btn.dataset.username;
                if (deleteUserModal) deleteUserModal.show();
            }
        });

        deleteUserForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const formData = new FormData(deleteUserForm);
            formData.append("action", "delete");

            try {
                const response = await fetch(apiUrl, {
                    method: "POST",
                    body: formData,
                });
                const result = await response.json();

                if (result.status === "success") {
                    if (deleteUserModal) deleteUserModal.hide();
                    await loadUsers();
                    alert(result.message);
                } else {
                    alert("Error: " + result.message);
                }
            } catch (error) {
                console.error("Error deleting user:", error);
                alert("Error deleting user. Please try again.");
            }
        });
    }

    // Initial load
    loadUsers();
});
